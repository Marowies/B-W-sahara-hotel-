<?php

namespace Botble\Hotel\Supports;

/**
 * Room card links: the clean room URL unless the visitor searched. Default dates and guests are never
 * written into links (they change every day and the room page computes the same defaults itself).
 */
class RoomSearchLink
{
    public const GUEST_KEYS = ['adults', 'children', 'rooms'];

    /**
     * @param array $input  the request input, used only to see which booking parameters the visitor sent
     * @param array $values the validated search values (RoomSearchParams), keyed like the request
     */
    public static function url(string $roomUrl, array $input, array $values): string
    {
        $sent = fn (string $key): bool => isset($input[$key]) && is_scalar($input[$key]) && trim((string) $input[$key]) !== '';
        $query = [];

        // Dates travel as the validated pair: the room page re-validates them together.
        if ($sent('start_date') || $sent('end_date')) {
            $query['start_date'] = $values['start_date'] ?? null;
            $query['end_date'] = $values['end_date'] ?? null;
        }

        foreach (self::GUEST_KEYS as $key) {
            if ($sent($key)) {
                $query[$key] = $values[$key] ?? null;
            }
        }

        $query = array_filter($query, fn ($value) => $value !== null && $value !== '');

        return $query ? $roomUrl . (str_contains($roomUrl, '?') ? '&' : '?') . http_build_query($query) : $roomUrl;
    }
}
