<?php

namespace Botble\SocialLogin\Services;

use Illuminate\Database\Eloquent\Model;
use Laravel\Socialite\AbstractUser;
use RuntimeException;

class GoogleAccountResolver
{
    public function __construct(protected SocialLoginService $socialLoginService)
    {
    }

    public function resolve(AbstractUser $googleUser, string $modelClass): ?Model
    {
        // Google supplies verified_email through its userinfo endpoint.
        $verified = $googleUser->user['verified_email'] ?? $googleUser->user['email_verified'] ?? false;

        if (! $googleUser->getId() || ! $googleUser->getEmail() || ! filter_var($verified, FILTER_VALIDATE_BOOLEAN)) {
            throw new RuntimeException('Google did not supply a verified identity.');
        }

        $identity = $this->socialLoginService->findSocialLoginByProviderAndId('google', $googleUser->getId());

        if ($identity) {
            $account = $identity->user;

            if (! $account || $account::class !== $modelClass) {
                throw new RuntimeException('Google identity belongs to a different account type.');
            }

            // A changed email must never move an existing identity or its bookings.
            return $account;
        }

        $account = $this->socialLoginService->findUserByEmail($googleUser->getEmail(), $modelClass);

        if ($account && $this->socialLoginService->hasSocialLogin($account, 'google')) {
            throw new RuntimeException('The account is already linked to another Google identity.');
        }

        return $account;
    }
}
