<?php

namespace Botble\Theme\Supports;

use Botble\Theme\Facades\Theme;
use Illuminate\Support\Facades\Route;

/**
 * Preloads the homepage hero's background image. A CSS background is invisible to the browser's preload
 * scanner, so the likely LCP image would otherwise start downloading only after styles are applied.
 */
class HeroImagePreload
{
    // Called by hero templates with the image of the slide that is painted first.
    public static function homepageHero(?string $url): void
    {
        if (! self::applies($url, Route::currentRouteName())) {
            return;
        }

        Theme::set('heroImagePreload', $url);

        // theme_front_meta is printed before the stylesheets, so the download starts as early as possible.
        add_filter('theme_front_meta', fn (?string $html) => $html . self::tag($url), 1);
    }

    /**
     * Only on the homepage route, for the page's top hero: no breadcrumb banner H1 above it, no earlier hero
     * (which claims the hero heading) and no preload already registered for this request.
     */
    public static function applies(?string $url, ?string $routeName): bool
    {
        return $url
            && $routeName === 'public.index'
            && ! Theme::get('heroImagePreload')
            && ! Theme::get('heroHeading')
            && ! (Theme::get('breadcrumb', true) && Theme::get('pageTitle'));
    }

    // Same URL and request mode as the CSS background, so the browser reuses the response.
    public static function tag(string $url): string
    {
        return '<link rel="preload" as="image" href="' . e($url) . '" fetchpriority="high">';
    }
}
