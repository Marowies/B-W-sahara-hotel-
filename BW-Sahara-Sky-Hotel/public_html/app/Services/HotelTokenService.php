<?php

namespace App\Services;

use Botble\Hotel\Models\Customer;
use App\Notifications\HotelSessionReuse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

/**
 * Access token: HMAC-signed, 15 minutes, validated against persisted family state on every use,
 * so logout or a detected attack kills it immediately.
 * Refresh token: opaque random value in an HttpOnly cookie, stored hashed, rotated on every use.
 */
class HotelTokenService
{
    public const ACCESS_TTL = 900;
    public const GRACE_SECONDS = 5;
    public const ABSOLUTE_DAYS = 30;
    public const COOKIE = 'hotel_refresh';
    public const COOKIE_PATH = '/api/hotel/auth';
    private const TABLE = 'hotel_auth_tokens';

    /** @return array{access_token:string,expires_in:int,refresh_token:string,family_expires_at:Carbon} */
    public function issue(Customer $customer, Request $request): array
    {
        $familyId = (string) Str::uuid();
        $expires = now()->addDays(self::ABSOLUTE_DAYS);
        $refresh = DB::transaction(function () use ($customer, $familyId, $expires, $request) {
            Customer::query()->whereKey($customer->getKey())->lockForUpdate()->firstOrFail();
            return $this->insertRow($familyId, $customer->getKey(), $expires, $request);
        });

        return $this->pair($familyId, $customer->getKey(), $refresh, $expires);
    }

    /**
     * @return array{ok:true,access_token:string,expires_in:int,refresh_token:?string,family_expires_at:Carbon}|array{ok:false,reason:string}
     */
    public function refresh(string $token, Request $request): array
    {
        $alert = null;
        // Resolve only the immutable owner outside the transaction. State is read again after the common lock.
        $identity = DB::table(self::TABLE)->where('refresh_hash', $this->hash($token))->first();
        $result = DB::transaction(function () use ($identity, $request, &$alert): array {
            $alert = null;
            if (! $identity || ! Customer::query()->whereKey($identity->customer_id)->lockForUpdate()->first()) {
                return ['ok' => false, 'reason' => 'invalid'];
            }
            // Rotation and logout acquire the same customer lock before reading token state.
            $row = DB::table(self::TABLE)->where('id', $identity->id)->lockForUpdate()->first();
            if (! $row) {
                return ['ok' => false, 'reason' => 'invalid'];
            }
            $expires = Carbon::parse($row->family_expires_at);
            if ($expires->lessThanOrEqualTo(now())) {
                $this->revokeFamily($row->family_id, 'expired');

                return ['ok' => false, 'reason' => 'expired'];
            }
            $successor = DB::table(self::TABLE)->where('family_id', $row->family_id)->where('status', 'active')->lockForUpdate()->first();
            $familyActive = $successor !== null;

            if ($row->status === 'revoked') {
                if (! $familyActive || $row->revoke_reason !== 'rotated') {
                    return ['ok' => false, 'reason' => 'revoked'];
                }
                if (Carbon::parse($row->revoked_at)->gt(now()->subSeconds(self::GRACE_SECONDS))) {
                    // Return the same current successor, never a second branch. This also recovers a lost rotation response.
                    $refresh = $successor->refresh_ciphertext ? Crypt::decryptString($successor->refresh_ciphertext) : null;
                    $pair = $this->pair($row->family_id, $row->customer_id, $refresh, $expires);

                    return ['ok' => true] + $pair;
                }
                // A rotated token came back after the grace period: treat as theft and kill the whole family.
                $this->revokeFamily($row->family_id, 'reuse_detected');
                $alert = ['customer_id' => $row->customer_id, 'ip' => $request->ip(), 'ua' => (string) $request->userAgent()];

                return ['ok' => false, 'reason' => 'reuse'];
            }

            DB::table(self::TABLE)->where('id', $row->id)->update(['status' => 'revoked', 'revoke_reason' => 'rotated', 'refresh_ciphertext' => null, 'revoked_at' => now(), 'updated_at' => now()]);
            $next = $this->insertRow($row->family_id, $row->customer_id, $expires, $request);

            return ['ok' => true] + $this->pair($row->family_id, $row->customer_id, $next, $expires);
        }, 3);

        if ($alert) {
            $this->notifyReuse($alert);
        }

        return $result;
    }

    public function authenticate(?string $access): ?Customer
    {
        if (! $access || substr_count($access, '.') !== 2) {
            return null;
        }
        [$version, $body, $signature] = explode('.', $access);
        if ($version !== 'v1' || ! hash_equals($this->sign($version . '.' . $body), $signature)) {
            return null;
        }
        $claims = json_decode((string) base64_decode(strtr($body, '-_', '+/')), true);
        if (! is_array($claims) || ! is_int($claims['exp'] ?? null) || $claims['exp'] <= now()->timestamp || empty($claims['fid']) || empty($claims['cid'])) {
            return null;
        }
        $active = DB::table(self::TABLE)->where('family_id', $claims['fid'])->where('customer_id', $claims['cid'])->where('status', 'active')->where('family_expires_at', '>', now())->exists();

        return $active ? Customer::query()->find($claims['cid']) : null;
    }

    public function logout(?string $refresh, ?string $access): void
    {
        $family = null;
        if ($refresh) {
            $family = DB::table(self::TABLE)->where('refresh_hash', $this->hash($refresh))->value('family_id');
        }
        if (! $family && $access && substr_count($access, '.') === 2 && $this->authenticate($access)) {
            $body = json_decode((string) base64_decode(strtr(explode('.', $access)[1], '-_', '+/')), true);
            $family = $body['fid'] ?? null;
        }
        if ($family) {
            $this->revokeFamily($family, 'logout');
        }
    }

    public function revokeFamily(string $familyId, string $reason): void
    {
        DB::transaction(function () use ($familyId, $reason): void {
            $customerId = DB::table(self::TABLE)->where('family_id', $familyId)->value('customer_id');
            if (! $customerId || ! Customer::query()->whereKey($customerId)->lockForUpdate()->first()) {
                return;
            }
            DB::table(self::TABLE)->where('family_id', $familyId)->where('status', 'active')
                ->update(['status' => 'revoked', 'revoke_reason' => $reason, 'refresh_ciphertext' => null, 'revoked_at' => now(), 'updated_at' => now()]);
        }, 3);
    }

    /** Keep rotated token hashes until absolute family expiry so reuse remains detectable. */
    public function prune(): int
    {
        return DB::table(self::TABLE)->where('family_expires_at', '<=', now())->delete();
    }

    public function cookie(?string $refresh, ?Carbon $expires, Request $request): \Symfony\Component\HttpFoundation\Cookie
    {
        $secure = app()->isProduction() || $request->isSecure();
        if ($refresh === null) {
            return cookie()->forget(self::COOKIE, self::COOKIE_PATH)->withSecure($secure)->withSameSite('strict');
        }

        return cookie(self::COOKIE, $refresh, max(1, (int) now()->diffInMinutes($expires)), self::COOKIE_PATH, null, $secure, true, false, 'strict');
    }

    private function insertRow(string $familyId, int|string $customerId, Carbon $expires, Request $request): string
    {
        $refresh = Str::random(64);
        DB::table(self::TABLE)->insert([
            'family_id' => $familyId, 'customer_id' => $customerId, 'refresh_hash' => $this->hash($refresh), 'status' => 'active',
            'refresh_ciphertext' => Crypt::encryptString($refresh),
            'family_expires_at' => $expires, 'ip' => $request->ip(), 'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'last_used_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $refresh;
    }

    private function pair(string $familyId, int|string $customerId, ?string $refresh, Carbon $expires): array
    {
        $expiresIn = min(self::ACCESS_TTL, max(0, $expires->timestamp - now()->timestamp));
        $body = rtrim(strtr(base64_encode(json_encode(['fid' => $familyId, 'cid' => (int) $customerId, 'exp' => now()->timestamp + $expiresIn])), '+/', '-_'), '=');

        return [
            'access_token' => 'v1.' . $body . '.' . $this->sign('v1.' . $body), 'expires_in' => $expiresIn,
            'refresh_token' => $refresh, 'family_expires_at' => $expires,
        ];
    }

    protected function notifyReuse(array $alert): void
    {
        $customer = Customer::query()->find($alert['customer_id']);
        if (! $customer) {
            return;
        }
        try {
            $customer->notify(new HotelSessionReuse($alert['ip'], Str::limit($alert['ua'], 250, ''), now()->utc()->toDateTimeString()));
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    private function sign(string $data): string
    {
        return rtrim(strtr(base64_encode(hash_hmac('sha256', $data, (string) config('app.key'), true)), '+/', '-_'), '=');
    }
}
