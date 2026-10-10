<?php

namespace Botble\CookieConsent\Supports;

class LearnMoreUrl
{
    /**
     * Absolute http(s) links are kept; anything else is a path under the (localized) homepage URL,
     * joined with exactly one slash so "/cookie-policy" never becomes "/ar//cookie-policy".
     * An empty path returns null: the link is omitted rather than pointed at the homepage.
     */
    public static function resolve(mixed $configured, mixed $homepageUrl): ?string
    {
        $configured = is_string($configured) ? trim($configured) : '';

        if (preg_match('#^https?://#i', $configured)) {
            return $configured;
        }

        $path = ltrim($configured, '/');
        $homepageUrl = is_string($homepageUrl) ? $homepageUrl : '';

        if ($path === '' || $homepageUrl === '') {
            return null;
        }

        return rtrim($homepageUrl, '/') . '/' . $path;
    }
}
