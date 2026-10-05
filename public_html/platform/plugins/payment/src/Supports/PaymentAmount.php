<?php

namespace Botble\Payment\Supports;

final class PaymentAmount
{
    /** Compare decimal money as integer minor units, without float equality. */
    public static function minorUnits(mixed $amount, int $decimals = 2): ?int
    {
        if (! is_scalar($amount) || is_bool($amount)) {
            return null;
        }

        $value = (string) $amount;
        if (! preg_match('/\A([0-9]{1,12})(?:\.([0-9]+))?\z/', $value, $parts)) {
            return null;
        }

        $fraction = $parts[2] ?? '';
        if (trim(substr($fraction, $decimals), '0') !== '') {
            return null;
        }

        return (int) $parts[1] * (10 ** $decimals)
            + (int) str_pad(substr($fraction, 0, $decimals), $decimals, '0');
    }
}
