<?php

// A transport double prevents these regression tests from making network requests.
namespace Botble\Hotel\Services;

class CalendarTransportDouble
{
    public static array $options = [];
    public static int $status = 200;
    public static string $body = "BEGIN:VCALENDAR\r\nEND:VCALENDAR";
    public static int $calls = 0;
}

function curl_init($url)
{
    CalendarTransportDouble::$calls++;

    return new \stdClass();
}

function curl_setopt_array($handle, array $options): bool
{
    CalendarTransportDouble::$options = $options;

    return true;
}

function curl_exec($handle): bool
{
    $write = CalendarTransportDouble::$options[CURLOPT_WRITEFUNCTION];

    return $write($handle, CalendarTransportDouble::$body) === strlen(CalendarTransportDouble::$body);
}

function curl_getinfo($handle, $option): int
{
    return CalendarTransportDouble::$status;
}

function curl_close($handle): void
{
}
