<?php

namespace Botble\Theme\Supports;

class JsonLd
{
    /**
     * Plain text for a JSON-LD value. CMS and SEO helper values arrive HTML-escaped ("B&amp;W"),
     * and JSON-LD is not HTML, so entities are decoded exactly once here.
     */
    public static function text(mixed $value): ?string
    {
        if (! is_scalar($value) && ! $value instanceof \Stringable) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');

        return $value === '' ? null : $value;
    }

    // The one URL a site-level entity uses on every page and language.
    public static function root(string $siteUrl): string
    {
        return rtrim($siteUrl, '/');
    }

    // Stable node identifier: the same site root and fragment on every page and language.
    public static function id(string $siteUrl, string $fragment): string
    {
        return self::root($siteUrl) . '/#' . $fragment;
    }

    public static function website(string $siteUrl, mixed $name): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => self::id($siteUrl, 'website'),
            'name' => self::text($name),
            'url' => self::root($siteUrl),
        ], fn ($value) => $value !== null);
    }

    // One site-level organization rather than a new entity per page URL.
    public static function organization(string $siteUrl, mixed $name, ?string $logoUrl): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => self::id($siteUrl, 'organization'),
            'name' => self::text($name),
            'url' => self::root($siteUrl),
            'logo' => $logoUrl ? ['@type' => 'ImageObject', 'url' => $logoUrl] : null,
        ], fn ($value) => $value !== null);
    }

    /**
     * BreadcrumbList from the visible trail. Unnamed crumbs are skipped and positions renumbered; a trail of
     * fewer than two named crumbs (e.g. only "Home") is not a breadcrumb, so nothing is emitted.
     */
    public static function breadcrumbList(array $crumbs): ?array
    {
        $items = [];

        foreach ($crumbs as $crumb) {
            if (! $name = self::text($crumb['label'] ?? null)) {
                continue;
            }

            $url = $crumb['url'] ?? null;

            $items[] = array_filter([
                '@type' => 'ListItem',
                'position' => count($items) + 1,
                'name' => $name,
                'item' => is_string($url) && trim($url) !== '' ? $url : null,
            ], fn ($value) => $value !== null);
        }

        return count($items) < 2 ? null : [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    // Themes write scripts verbatim, so "<" and ">" in CMS text must not close the tag.
    public static function encode(array $schema): string
    {
        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
