<?php

namespace Botble\Hotel\Services;

use InvalidArgumentException;
use RuntimeException;

class SafeCalendarFetcher
{
    public const MAX_BYTES = 2 * 1024 * 1024;

    /** Resolve before connecting, then pin the checked address to prevent DNS rebinding. */
    public function validateUrl(string $url): array
    {
        $parts = parse_url($url);

        if (! $parts || strlen($url) > 2048 || preg_match('/[\x00-\x20\x7f]/', $url)
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
            || ($parts['port'] ?? 443) !== 443
        ) {
            throw new InvalidArgumentException('Use a direct HTTPS calendar URL on port 443 without credentials or fragments.');
        }

        $host = strtolower(trim($parts['host'] ?? '', '[]'));

        if (! $host || (! filter_var($host, FILTER_VALIDATE_IP)
            && (! filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
                || preg_match('/(^|\.)[0-9]+$/', $host)
                || preg_match('/^0x[0-9a-f]+$/i', $host)))
        ) {
            throw new InvalidArgumentException('Invalid calendar hostname.');
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : $this->resolveHost($host);

        if (! $addresses) {
            throw new InvalidArgumentException('Calendar hostname could not be resolved.');
        }

        foreach ($addresses as $address) {
            $packedAddress = @inet_pton($address);
            if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE)
                || str_contains($address, '%')
                // Reject IPv6 transition mechanisms that embed a different IPv4 destination.
                || (str_contains($address, ':') && (
                    str_contains($address, '.')
                    || str_starts_with(strtolower($address), '2002:')
                    || str_starts_with(strtolower($address), '2001:0:')
                    || str_starts_with(strtolower($address), '64:ff9b:')
                    || ($packedAddress !== false && substr($packedAddress, 0, 4) === hex2bin('0064ff9b'))
                ))
            ) {
                throw new InvalidArgumentException('Calendar URLs must resolve exclusively to public IP addresses.');
            }
        }

        return [$host, array_values($addresses)];
    }

    protected function resolveHost(string $host): array
    {
        $records = dns_get_record($host, DNS_A | DNS_AAAA);
        $addresses = [];

        foreach ($records ?: [] as $record) {
            if (isset($record['ip']) || isset($record['ipv6'])) {
                $addresses[] = $record['ip'] ?? $record['ipv6'];
            }
        }

        return array_values(array_unique($addresses));
    }

    public function fetch(string $url): string
    {
        [$host, $addresses] = $this->validateUrl($url);
        $address = str_contains($addresses[0], ':') ? '[' . $addresses[0] . ']' : $addresses[0];
        $content = '';
        $curl = curl_init($url);

        if ($curl === false) {
            throw new RuntimeException('Could not initialize calendar download.');
        }

        try {
            curl_setopt_array($curl, [
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROXY => '',
                CURLOPT_RESOLVE => ["{$host}:443:{$address}"],
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_LOW_SPEED_LIMIT => 100,
                CURLOPT_LOW_SPEED_TIME => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$content): int {
                    if (strlen($content) + strlen($chunk) > self::MAX_BYTES) {
                        return 0;
                    }

                    $content .= $chunk;

                    return strlen($chunk);
                },
            ]);

            if (curl_exec($curl) === false || curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200) {
                throw new RuntimeException('Calendar download failed; redirects are not accepted. Use the final HTTPS URL.');
            }

            if (! str_starts_with(ltrim($content, "\xEF\xBB\xBF\r\n\t "), 'BEGIN:VCALENDAR')) {
                throw new RuntimeException('The response is not an iCalendar document.');
            }

            return $content;
        } finally {
            curl_close($curl);
        }
    }
}
