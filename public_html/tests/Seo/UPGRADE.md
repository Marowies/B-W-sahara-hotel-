# Robots upgrade and missing-translation regression checks

Run the robots migration **before** deploying the change that deletes `public/robots.txt`. Export the actual legacy file first if the release tool has already removed it. Review its custom rules and sitemap URLs with the owner; extra sitemap directives are deliberately preserved, including their hosts.

From `public_html`, run:

```
php scripts/migrate-robots.php /absolute/legacy/public/robots.txt /absolute/shared/storage/app/robots.txt /absolute/backups/robots-before-seo.txt
```

Use existing writable directories outside the web root for storage and backup. The tool refuses existing destination or backup files, copies exact bytes, verifies both copies, then removes the legacy static override. A failed copy leaves the legacy file in place; inspect any newly created partial copies before retrying with fresh paths. If storage already has custom rules, stop and reconcile the two files manually. Reading the dynamic route before migration falls back to the legacy file without writing anything. A remaining static file may still bypass Laravel, so verify `/robots.txt` over HTTP after migration and after deployment, including custom directives, extra sitemaps, and the named main sitemap. Restore from the backup if needed. This tool is never invoked by an HTTP request or deployment hook.

SEO unit regressions:

```
php tests/Seo/run.php /absolute/vendor/autoload.php
```

For the Laravel missing-translation regression, prepare a separate testing app and a synthetic SQLite fixture with locales en_US/ar/zh_CN, a published room at `synthetic-room-1`, Arabic slug `synthetic-room-ar`, Chinese slug `synthetic-room-zh`, and text translations. Configure APP_ENV=testing, SQLite, array sessions/cache/mail; use an autoloader mapped to this tested source. Do not supply production environment files or databases.

```
pwsh -File tests/Seo/verify-missing-translations.ps1 -TestAutoload /absolute/test-autoload.php -TestApp /absolute/test-app -SyntheticSqlite /absolute/synthetic.sqlite
```

Each probe copies the fixture to a temporary database before removing either only the Chinese slug or both slug and room text translations. It checks HTTP 200, canonical targets and reciprocal hreflang from every language. Outbound PHP HTTP/socket functions are disabled. Advanced translations intentionally use the CMS original-slug/content fallback; standard separate-record translations without a target are omitted.
