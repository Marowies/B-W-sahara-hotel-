# B&W Sahara Hotel — source snapshot

Source imported from the supplied hotel code folder on 2026-10-04. The original folder layout is retained so the different copies can be reviewed before choosing a development baseline.

## Layout

- `bww/`: supplied Laravel / Botble application copy.
- `public_html/`: supplied hosting application copy.
- `public_html/old/`: historical application copy supplied inside the hosting folder.

The application includes the hotel plugin and theme source under `platform/`. Frontend assets supplied under `public/` are retained. This repository does not contain the separate design-review prototype.

## Excluded from the import

Production `.env` files and their values, application/vendor dependency installations, Node dependencies, uploaded media and other storage data, logs, compiled runtime caches, database exports, private keys, and compressed backups are excluded. Sanitized `.env.example` files contain configuration keys with blank values. No production database is included.

Mailgun examples in translated configuration placeholders were replaced with `YOUR_MAILGUN_API_KEY` to avoid publishing key-shaped example strings. The original supplied files were left unchanged.

## Local setup

Select the intended application copy first. Check its `composer.json` and `package.json` for the exact requirements and scripts. The supplied PHP requirement is `^8.2|^8.3`.

1. Install Composer dependencies within the selected application directory (`composer install`). Some Botble packages may require authorized access.
2. Copy `.env.example` to `.env` and configure local settings and a local database.
3. Generate a local application key (`php artisan key:generate`).
4. Install frontend dependencies and run the appropriate build script if needed.
5. Point the web server document root at that application's `public/` directory.

Review migrations and seeders before applying them. Runtime and storage directories contain only scaffolding; hotel data and production uploads must be obtained separately through an appropriate private transfer.

This import was checked for file completeness and accidental secret inclusion. The application has not been installed or tested against a database as part of the upload.
