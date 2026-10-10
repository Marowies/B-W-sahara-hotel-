<?php

namespace Botble\Hotel\Supports;

use Botble\Base\Enums\BaseStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SeoIndexability
{
    public const NOINDEX = 'noindex, nofollow';

    // Booking, checkout, account and internal search pages are crawlable but must not be indexed.
    public const PRIVATE_ROUTES = [
        'public.booking.form',
        'public.booking.information',
        'public.search',
        'customer.*',
    ];

    public static function isPrivateRoute(?string $routeName): bool
    {
        return $routeName !== null && Str::is(self::PRIVATE_ROUTES, $routeName);
    }

    public static function isUnpublished(mixed $object): bool
    {
        if (! $object instanceof Model || ! array_key_exists('status', $object->getAttributes())) {
            return false;
        }

        $status = $object->status;
        $status = $status instanceof BaseStatusEnum ? $status->getValue() : (string) $status;

        return $status !== BaseStatusEnum::PUBLISHED;
    }
}
