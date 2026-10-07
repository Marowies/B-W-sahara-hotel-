<?php

namespace Botble\Hotel\Supports;

class HotelSchema
{
    // Riorelax demo seed values (database/seeders/ThemeOptionSeeder.php); never publish them as hotel facts.
    public const DEMO_VALUES = [
        'info@webmail.com',
        '14/A, Riorelax City, NYC',
        '+908 987 877 09',
    ];

    /**
     * Hotel node built only from contact details the CMS already shows publicly.
     * Star rating, geo, check-in/out times, prices, amenities and ratings are deliberately omitted until verified.
     */
    public static function hotel(string $siteUrl, string $homeUrl, ?string $name, array $options = [], ?string $logoUrl = null): ?array
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
            'url' => $homeUrl,
            'logo' => $logoUrl,
            'image' => $logoUrl,
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
            'image' => array_values(array_unique(array_filter($imageUrls))),
            'containedInPlace' => ['@id' => self::hotelId($siteUrl)],
        ];

        return array_filter($schema, fn ($value) => $value !== null && $value !== []);
    }

    // The theme writes scripts verbatim, so "<" and ">" in CMS text must not close the tag.
    public static function toJson(array $schema): string
    {
        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
    }

    public static function hotelId(string $siteUrl): string
    {
        return rtrim($siteUrl, '/') . '/#hotel';
    }

    protected static function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $value === '' || in_array($value, self::DEMO_VALUES, true) ? null : $value;
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
