# B&W Sahara Sky Hotel — working source

Source imported from the supplied hotel code folder on 2026-10-04. `public_html/` is the selected application for repairs and changes. Duplicate cleanup is documented in [docs/SOURCE_CLEANUP_2026-10-04.md](docs/SOURCE_CLEANUP_2026-10-04.md).

## Layout

- `public_html/`: current Laravel / Botble application, including the hotel plugin and Riorelax theme.
- `docs/`: requirements, code review, and cleanup records.

See [project structure](docs/PROJECT_STRUCTURE.md) for module ownership and editing conventions, and [security hardening](docs/SECURITY_HARDENING_2026-10-04.md) for repairs, test evidence, and outstanding risks.

The historical `bww/` and `public_html/old/` application copies were removed from this working branch. Their files remain in the verified local backup and in the original Git snapshot `8fedb18e398f1af67a5365ff4cf53efcbca34d4b` (also retained on local `main`).

The application includes the hotel plugin and theme source under `platform/`. Frontend assets supplied under `public/` are retained. This repository does not contain the separate design-review prototype.

## Excluded from the import

Production `.env` files and their values, application/vendor dependency installations, Node dependencies, uploaded media and other storage data, logs, compiled runtime caches, database exports, private keys, and compressed backups are excluded. Sanitized `.env.example` files contain configuration keys without production secrets and safe local defaults. No production database is included.

Mailgun examples in translated configuration placeholders were replaced with `YOUR_MAILGUN_API_KEY` to avoid publishing key-shaped example strings. The original supplied files were left unchanged.

## Local setup

Local backend fixes and verification limits are documented in [the backend report](docs/BACKEND_REFACTOR_2026-10-04.md). Source organization is documented in [PROJECT_STRUCTURE](docs/PROJECT_STRUCTURE.md).

Use `public_html/`. Check its `composer.json` and `package.json` for the exact requirements and scripts. The supplied PHP requirement is `^8.2|^8.3`.

1. Install Composer dependencies within the selected application directory (`composer install`). Some Botble packages may require authorized access.
2. Copy `.env.example` to `.env` and configure local settings and a local database.
3. Generate a local application key (`php artisan key:generate`).
4. Install frontend dependencies and run the appropriate build script if needed.
5. Point the web server document root at that application's `public/` directory.

Review migrations and seeders before applying them. Runtime and storage directories contain only scaffolding; hotel data and production uploads must be obtained separately through an appropriate private transfer.

This import was checked for file completeness and accidental secret inclusion. The application has not been installed or tested against a database as part of the upload.

Cleanup retained each module's original language files and removed only byte-identical published translation overrides. Standalone Laravel translation-loader checks passed for all 3,450 affected groups before and after deletion. Existing public runtime CSS, JS, images, and PDF fonts are retained. The redundant public theme screenshot uses the existing source fallback. No hotel business logic or security behavior was changed by cleanup.
