<?php

namespace Botble\SocialLogin\Supports;

class LoginRedirect
{
    public static function resolve(mixed $intended, string $originUrl, string $fallback): string
    {
        if (! is_string($intended) || preg_match('/[\x00-\x20\\\\]/', $intended)) {
            return $fallback;
        }

        $target = parse_url($intended);
        $origin = parse_url($originUrl);

        if ($target === false || isset($target['user']) || isset($target['pass'])) {
            return $fallback;
        }

        if (str_starts_with($intended, '/') && ! str_starts_with($intended, '//')) {
            return $intended;
        }

        if (isset($target['scheme'], $target['host'])
            && $target['scheme'] === ($origin['scheme'] ?? null)
            && $target['host'] === ($origin['host'] ?? null)
            && ($target['port'] ?? null) === ($origin['port'] ?? null)
        ) {
            return $intended;
        }

        return $fallback;
    }
}
