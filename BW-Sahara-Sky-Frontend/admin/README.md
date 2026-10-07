# Admin panel files (reference copy)

Copied unchanged from branch `main` of github.com/Marowies/B-W-sahara-hotel-
(commit `8fedb18e`, application copy `bww/`). This is the Botble / Laravel admin panel that runs at `/admin`.

- `platform/core/*/resources/views` — admin layout, login, dashboard, settings, media, tables (Blade templates)
- `platform/packages/*/resources/views` — menus, pages, SEO, theme and widget screens
- `platform/plugins/*/resources/views` — plugin screens; the hotel plugin (bookings, rooms, coupons, customers) is in `platform/plugins/hotel`
- `platform/*/*/resources/{js,sass,css,assets}` — admin script and style sources
- `public/vendor/core` — compiled admin CSS, JS, icons and fonts served to the browser

These are server-rendered Laravel templates: they need the Laravel application to run and are not part of this static site's build (`tools/build.mjs` only reads `src/`).

## Website identity (brand layer)

The admin uses the website's colours (burgundy `#571c35`, night `#351020`, gold `#e5c194`, sand) and fonts (Cormorant Garamond, Manrope, Noto Sans Arabic) through one extra stylesheet. Botble's own files are untouched except for a single `<link>` line.

| File | Purpose |
|---|---|
| `public/vendor/core/core/base/css/bw-brand/bw-admin.css` | the brand layer (light theme, dark theme, RTL, login page) |
| `public/vendor/core/core/base/css/bw-brand/bw-fonts.css` + `fonts/` | self-hosted website fonts with their OFL licences |
| `public/vendor/core/core/base/css/bw-brand/img/` | website logo and login photo |
| `platform/core/base/resources/views/components/layouts/header.blade.php` | **one added line** that loads `bw-admin.css` after the core styles |
| `preview/login.html`, `preview/dashboard.html` | static previews (open in a browser; `dashboard.html?theme=dark`, `?dir=rtl`) |

### Put it on the server

Copy these into the Laravel application (same paths, relative to the app root):

1. `public/vendor/core/core/base/css/bw-brand/` (whole folder)
2. `platform/core/base/resources/views/components/layouts/header.blade.php`

Then clear the view cache (`php artisan view:clear`). To undo, remove the added `<link>` line.
