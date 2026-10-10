<?php

namespace Botble\Hotel\Supports;

use Botble\Language\Facades\Language;
use Botble\Theme\Facades\ThemeOption;

/**
 * Optional per-language copy for the /rooms listing, edited in Theme options → Hotel.
 * Theme options normally fall back to the default-language value; this copy does not, so an English
 * title or heading never appears on Arabic or Chinese pages. Unset values keep the existing output.
 */
class RoomsListingSeo
{
    public const TITLE = 'rooms_page_seo_title';

    public const DESCRIPTION = 'rooms_page_seo_description';

    public const HEADING = 'rooms_page_heading';

    public static function value(string $key): ?string
    {
        $languages = defined('LANGUAGE_MODULE_SCREEN_NAME');

        return self::resolve(
            $key,
            $languages ? Language::getCurrentLocaleCode() : null,
            $languages ? Language::getDefaultLocaleCode() : null,
            fn (string $key, ?string $suffix) => setting(ThemeOption::getOptionKey($key, $suffix))
        );
    }

    /**
     * Reads only the current language's stored option: the default language uses the unsuffixed key,
     * any other language its own "-{locale}" key, matching how ThemeOption stores per-language values.
     */
    public static function resolve(string $key, ?string $currentLocale, ?string $defaultLocale, callable $read): ?string
    {
        $suffix = $currentLocale && $currentLocale !== $defaultLocale ? '-' . $currentLocale : null;
        $value = $read($key, $suffix);
        $value = is_string($value) ? trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '') : '';

        return $value === '' ? null : $value;
    }
}
