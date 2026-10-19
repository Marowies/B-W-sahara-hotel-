<?php

use Botble\Hotel\Enums\BookingStatusEnum;
use Botble\Hotel\Models\Booking;
use Botble\Hotel\Models\BookingRoom;
use Botble\Hotel\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;

/*
 * The public site is the standalone frontend (/, /ar/, /zh/). It records the visitor's
 * language in its URL, the hotel_locale cookie and, for signed-in customers, ht_customers.ui_locale.
 * Botble's own language prefixes are not used on the account and booking pages, so without this
 * those pages always fell back to the default locale.
 */
const RIORELAX_GUEST_LOCALES = ['en', 'ar', 'zh'];

// Public frontend paths per language; mirrors BW-Sahara-Sky-Frontend/src/shared/locale.mjs.
const RIORELAX_GUEST_SITE_PATHS = [
    'home' => ['/', '/ar/', '/zh/'],
    'rooms' => ['/rooms/', '/ar/ghoraf/', '/zh/kefang/'],
    'experiences' => ['/experiences/', '/ar/tajarob-al-sahraa/', '/zh/shamo-tiyan/'],
    'gallery' => ['/galleries/', '/ar/sowar/', '/zh/xiangce/'],
    'about' => ['/about-us/', '/ar/aan-al-fondoq/', '/zh/guanyu-jiudian/'],
    'contact' => ['/contact-us/', '/ar/tawasul/', '/zh/lianxi/'],
    'privacy' => ['/privacy/', '/ar/khososiya/', '/zh/yinsi-zhengce/'],
    'terms' => ['/term-and-conditions/', '/ar/shorout/', '/zh/tiaokuan/'],
    'booking' => ['/booking/', '/ar/takhteet-eqama/', '/zh/zhusu-jihua/'],
    'login' => ['/login/', '/ar/dokhool/', '/zh/denglu/'],
];

function riorelax_is_guest_shell_request(?Request $request = null): bool
{
    $request ??= request();

    return $request->route() !== null && $request->routeIs('customer.*', 'public.booking.*');
}

/**
 * Explicit ?lang= first (links from the public site always carry it), then the language already
 * chosen in this session, the public site's cookie, and finally the customer's saved preference.
 */
function riorelax_guest_locale(?Request $request = null): ?string
{
    $request ??= request();

    if (! riorelax_is_guest_shell_request($request)) {
        return null;
    }

    $requested = $request->query('lang');
    if (is_string($requested) && in_array($requested, RIORELAX_GUEST_LOCALES, true)) {
        if ($request->hasSession()) {
            $request->session()->put('hotel_ui_locale', $requested);
        }

        return $requested;
    }

    $stored = $request->hasSession() ? $request->session()->get('hotel_ui_locale') : null;
    if (is_string($stored) && in_array($stored, RIORELAX_GUEST_LOCALES, true)) {
        return $stored;
    }

    // The cookie is written by frontend JavaScript and is not encrypted, so read the raw header.
    if (preg_match('/(?:^|;\s*)hotel_locale=(en|ar|zh)(?:;|$)/', (string) $request->headers->get('cookie'), $match)) {
        return $match[1];
    }

    $customer = auth('customer')->user();
    $saved = $customer?->getAttribute('ui_locale');

    return in_array($saved, RIORELAX_GUEST_LOCALES, true) ? $saved : null;
}

function riorelax_apply_guest_locale(): void
{
    if ($locale = riorelax_guest_locale()) {
        app()->setLocale($locale);
    }
}

function riorelax_guest_site_url(string $page, ?string $locale = null): string
{
    $locale ??= riorelax_guest_locale() ?: 'en';
    $paths = RIORELAX_GUEST_SITE_PATHS[$page] ?? RIORELAX_GUEST_SITE_PATHS['home'];

    // Same-origin path as published by the frontend; url() would drop the trailing slash those routes use.
    return $paths[array_search($locale, RIORELAX_GUEST_LOCALES, true) ?: 0];
}

/** The current page in another language; the theme stores the choice for the following pages. */
function riorelax_guest_switch_url(string $locale): string
{
    return request()->fullUrlWithQuery(['lang' => $locale]);
}

/**
 * Read-only figures for the account overview, from the customer's own bookings: two counts and at
 * most two single-row lookups, so the cost does not grow with the customer's booking history.
 * Upcoming means not cancelled and checking in today or later; the earliest check-in comes first.
 * The latest booking (newest created) is only looked up when there is no upcoming one, because the
 * overview shows it only in that case.
 *
 * @return array{total: int, upcoming_count: int, upcoming: ?Booking, latest: ?Booking}
 */
function riorelax_customer_dashboard(Customer $customer): array
{
    $bookings = fn () => Booking::query()->where('customer_id', $customer->getKey());
    $today = Carbon::today()->toDateString();
    $upcoming = fn () => $bookings()
        ->where('status', '!=', BookingStatusEnum::CANCELLED)
        ->whereHas('room', fn ($query) => $query->whereNotNull('start_date')->whereDate('start_date', '>=', $today));

    $next = $upcoming()
        ->with(['room.room', 'payment'])
        ->orderBy(
            BookingRoom::query()->select('start_date')->whereColumn('booking_id', (new Booking())->qualifyColumn('id'))->orderBy('id')->limit(1)
        )
        ->latest()
        ->first();

    return [
        'total' => $bookings()->count(),
        'upcoming_count' => $upcoming()->count(),
        'upcoming' => $next,
        'latest' => $next ? null : $bookings()->with(['room.room', 'payment'])->latest()->first(),
    ];
}

/** CSS modifier for a booking or payment status value; the label itself comes from the enum. */
function riorelax_status_tone(?string $value): string
{
    return match ($value) {
        'completed' => 'positive',
        'processing', 'refunded' => 'info',
        'cancelled', 'canceled', 'failed', 'fraud' => 'negative',
        default => 'pending',
    };
}

add_filter(BASE_FILTER_SITE_LANGUAGE_DIRECTION, function ($direction) {
    $locale = riorelax_guest_locale();

    return $locale ? ($locale === 'ar' ? 'rtl' : 'ltr') : $direction;
}, 120);
