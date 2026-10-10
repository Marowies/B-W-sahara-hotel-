<?php

namespace Botble\Theme\Supports;

use RuntimeException;

class RobotsTxt
{
    public const DEFAULT_CONTENT = "User-agent: *\nAllow: /\n";

    public static function path(): string
    {
        return storage_path('app/robots.txt');
    }

    public static function content(): string
    {
        // Reading the legacy file is safe before an explicitly scheduled migration.
        foreach ([self::path(), public_path('robots.txt')] as $path) {
            if (is_file($path)) {
                return file_get_contents($path);
            }
        }

        return self::DEFAULT_CONTENT;
    }

    public static function migrateLegacy(string $legacy, string $destination, string $backup): void
    {
        if (! is_file($legacy)) {
            throw new RuntimeException('Legacy robots.txt is missing; export it before deploying the deletion.');
        }
        foreach ([$backup, $destination] as $path) {
            if (file_exists($path) || is_link($path)) {
                throw new RuntimeException('Refusing to overwrite robots file: ' . $path);
            }
        }
        $content = file_get_contents($legacy);
        if ($content === false) {
            throw new RuntimeException('Cannot read legacy robots.txt.');
        }
        // Exclusive creation prevents overwriting either customized rules or a previous backup.
        foreach ([$backup, $destination] as $path) {
            $handle = @fopen($path, 'x');
            if (! $handle) {
                throw new RuntimeException('Cannot create robots file without overwriting: ' . $path);
            }
            try {
                if (fwrite($handle, $content) !== strlen($content)) {
                    throw new RuntimeException('Incomplete robots copy: ' . $path);
                }
            } finally {
                fclose($handle);
            }
        }
        // Only retire the static override after both copies have been verified.
        if (file_get_contents($backup) !== $content || file_get_contents($destination) !== $content || ! unlink($legacy)) {
            throw new RuntimeException('Robots migration verification or static-file retirement failed.');
        }
    }

    public static function render(string $content, string $sitemapUrl): string
    {
        // Keep intentionally supplied sitemap directives; add the installation sitemap once.
        if (! preg_match('/^[\t ]*Sitemap\s*:\s*' . preg_quote($sitemapUrl, '/') . '[\t ]*$/mi', $content)) {
            $content = rtrim($content) . "\n\nSitemap: " . $sitemapUrl;
        }

        return rtrim($content) . "\n";
    }
}
