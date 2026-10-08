<?php

namespace Botble\Hotel\Supports;

use Botble\Theme\Supports\JsonLd;

class HotelSchema
{
    // Riorelax demo seed values (database/seeders/ThemeOptionSeeder.php); never publish them as hotel facts.
    public const DEMO_VALUES = [
        'Hotel Riorelax',
        'info@webmail.com',
        '14/A, Riorelax City, NYC',
        '+908 987 877 09',
    ];

    /**
     * Hotel node built only from contact details the CMS already shows publicly.
     * Star rating, geo, check-in/out times, prices, amenities and ratings are deliberately omitted until verified.
     * One entity for every language: its @id and url are the site root; language versions are linked by hreflang.
     */
    public static function hotel(string $siteUrl, ?string $name, array $options = [], ?string $logoUrl = null): ?array
    {
        $name = self::text($name);

        if (! $name) {
            return null;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Hotel',
            '@id' => self::hotelId($siteUrl),
            'name' => $name,
            'url' => JsonLd::root($siteUrl),
            'logo' => self::imageUrl($logoUrl),
            'image' => self::imageUrl($logoUrl),
            'telephone' => self::text($options['hotline'] ?? null),
            'email' => self::text($options['email'] ?? null),
            'address' => self::text($options['address'] ?? null),
            'sameAs' => self::profiles($options['social_links'] ?? null),
        ];

        return array_filter($schema, fn ($value) => $value !== null && $value !== []);
    }

    /**
     * Room node with the content shown on the room page; occupancy, size, beds and offers need verified data.
     */
    public static function room(string $siteUrl, string $url, ?string $name, ?string $description = null, array $imageUrls = []): ?array
    {
        $name = self::text($name);

        if (! $name) {
            return null;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'HotelRoom',
            '@id' => $url . '#room',
            'name' => $name,
            'url' => $url,
            'description' => self::text($description),
            'image' => array_values(array_unique(array_filter(array_map(self::imageUrl(...), $imageUrls)))),
            'containedInPlace' => ['@id' => self::hotelId($siteUrl)],
        ];

        return array_filter($schema, fn ($value) => $value !== null && $value !== []);
    }

    /**
     * The generic page Organization describes the same business as the Hotel node, so with a verified hotel
     * name it becomes that entity: dropped on the homepage (the Hotel node is there) and pointed at the
     * hotel @id elsewhere. Without a verified name the generic Organization is left unchanged.
     */
    public static function organization(?array $schema, string $siteUrl, ?string $hotelName, bool $isHomepage): ?array
    {
        $hotelName = self::text($hotelName);

        if (! $schema || ! $hotelName) {
            return $schema;
        }

        return $isHomepage ? null : array_merge($schema, ['@id' => self::hotelId($siteUrl), 'name' => $hotelName]);
    }

    public static function toJson(array $schema): string
    {
        return JsonLd::encode($schema);
    }

    public static function hotelId(string $siteUrl): string
    {
        return JsonLd::id($siteUrl, 'hotel');
    }

    protected static function imageUrl(mixed $url): ?string
    {
        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true) ? $url : null;
    }

    protected static function text(mixed $value): ?string
    {
        $value = is_string($value) ? JsonLd::text($value) : null;

        return $value === null || in_array($value, self::DEMO_VALUES, true) ? null : $value;
    }

    // Social links are stored as JSON rows of key/value pairs; keep only real profile URLs.
    protected static function profiles(mixed $socialLinks): array
    {
        $rows = is_string($socialLinks) ? json_decode($socialLinks, true) : $socialLinks;
        $urls = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            foreach (is_array($row) ? $row : [] as $field) {
                $url = is_array($field) && ($field['key'] ?? null) === 'url' ? trim((string) ($field['value'] ?? '')) : null;
                $path = $url ? trim((string) parse_url($url, PHP_URL_PATH), '/') : '';

                if ($url && filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://') && $path !== '') {
                    $urls[] = $url;
                }
            }
        }

        return array_values(array_unique($urls));
    }
}
