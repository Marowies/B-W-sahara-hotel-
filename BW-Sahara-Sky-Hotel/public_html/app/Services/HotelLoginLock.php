<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\DB;

class HotelLoginLock
{
    public function run(string $email, Closure $callback): mixed
    {
        return DB::transaction(function () use ($email, $callback) {
            DB::table('hotel_login_locks')->insertOrIgnore(['email' => $email, 'updated_at' => now()]);
            DB::table('hotel_login_locks')->where('email', $email)->lockForUpdate()->first();
            DB::table('hotel_login_locks')->where('email', $email)->update(['updated_at' => now()]);
            return $callback();
        }, 3);
    }
}
