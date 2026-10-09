<?php

namespace Botble\Theme\Supports;

class RobotsTxt
{
    public const DEFAULT_CONTENT = "User-agent: *\nAllow: /\n";

    public static function path(): string
    {
        // Outside the web root so the web server cannot bypass the dynamic route.
        return storage_path('app/robots.txt');
    }

    public static function content(): string
    {
        return is_file(self::path()) ? file_get_contents(self::path()) : self::DEFAULT_CONTENT;
    }

    public static function render(string $content, string $sitemapUrl): string
    {
        // The sitemap always follows this installation's named route, including after a host change.
        $content = preg_replace('/^[\t ]*Sitemap\s*:[^\r\n]*(?:\r?\n|$)/mi', '', $content);

        return rtrim($content) . "\n\nSitemap: " . $sitemapUrl . "\n";
    }
}
