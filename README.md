# Trim — Laravel Link Shortener

A small, considered link shortener with built-in click analytics. Built on Laravel 10 + SQLite. No frameworks for the UI, no third-party tracking, no JavaScript chart libraries.

> **Stack:** Laravel 10 · PHP 8.1+ · SQLite · Blade · PHPUnit · Inter / JetBrains Mono (Google Fonts)

---

## Table of contents

- [Features](#features)
- [Screenshots / Pages](#screenshots--pages)
- [Requirements](#requirements)
- [Setup (local)](#setup-local)
- [Running the tests](#running-the-tests)
- [Project structure](#project-structure)
- [Design notes](#design-notes)
- [Performance notes](#performance-notes)
- [Deployment](#deployment)
- [Packaging a ZIP for delivery](#packaging-a-zip-for-delivery)
- [License](#license)

---

## Features

- **Six-character base62 short codes** — `[A-Za-z0-9]{6}` ≈ 56.8 billion combinations.
- **Collision-resistant code generation** — every candidate is checked for uniqueness before saving.
- **URL validation** — server-side, must be a valid `http(s)://` URL, max 2,048 characters.
- **Optional expiry** — links can be set to stop working after a chosen date.
- **Rate limiting** — 10 link creations per hour per client IP, using Laravel's built-in throttle.
- **Click analytics** — every visit logs IP, user agent, referer, and timestamp.
- **14-day bar chart** — pure CSS, proportional `<div>` widths, no JS chart library.
- **Top referrers + recent clicks table** — bonus analytics blocks on the stats page.
- **Deferred click logging** — visitors get the 302 redirect immediately; logging runs in a terminable middleware after the response is flushed.
- **PHPUnit feature suite** — 27 test methods covering the six required scenarios + edge cases.

---

## Screenshots / Pages

| Route | Description |
| --- | --- |
| `GET /` | **Home** — form to paste a URL with optional expiry |
| `POST /shorten` | Validates and creates a new short link (throttled) |
| `GET /r/{short_code}` | **Result** — confirms the new link with copy / open / view-stats actions |
| `GET /stats/{short_code}` | **Analytics** — KPIs, 14-day bar chart, top referrers, last 20 clicks |
| `GET /{short_code}` | 302-redirects to the destination, logs the click |

Plus state pages: `410 Gone` for expired links, `404` for unknown codes, `429` for rate-limit overruns.

Static HTML mockups of every screen live in `/design/` and can be opened directly in a browser — no build step.

---

## Requirements

- **PHP 8.1 or higher** with the following extensions:
  - `pdo_sqlite`, `sqlite3`
  - `openssl`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `curl`
- **Composer 2.x**
- ~10 MB of disk space (SQLite database)

A Wamp/Xampp install on Windows, or a fresh Linux box with `php-cli` and `php-sqlite3`, is sufficient.

---

## Setup (local)

```bash
# 1. Clone the project
git clone <your-repo-url> trim
cd trim

# 2. Install PHP dependencies
composer install

# 3. Copy the env file and generate an app key
cp .env.example .env          # on Windows PowerShell: Copy-Item .env.example .env
php artisan key:generate

# 4. Create the SQLite database file (if it doesn't already exist)
#    On *nix:
touch database/database.sqlite
#    On Windows PowerShell:
#    New-Item -ItemType File -Path database\database.sqlite -Force

# 5. Run migrations and seed demo data (5 links, 50 click logs)
php artisan migrate --seed --force

# 6. Start the local dev server
php artisan serve
```

Visit **http://127.0.0.1:8000** in your browser.

### Demo links seeded for you

| Short code | Destination | Notes |
| --- | --- | --- |
| `lvDocs` | https://github.com/laravel/laravel/blob/10.x/README.md | 25 clicks |
| `elQunt` | https://laravel.com/docs/10.x/eloquent | 15 clicks |
| `phpTyp` | https://www.php.net/manual/en/language.types.declarations.php | 6 clicks |
| `hnPost` | https://news.ycombinator.com/item?id=40123456 | 4 clicks, expires in 7 days |
| `expSpr` | https://example.com/old-promo | **Already expired**, demonstrates 410 page |

Try: http://127.0.0.1:8000/stats/lvDocs

---

## Running the tests

The test suite uses an in-memory SQLite database, so tests don't touch your dev database.

```bash
php artisan test
```

Expected output: **27 passed (230 assertions)**, ~12 seconds.

The 6 required scenarios live in:

| Test file | Scenario |
| --- | --- |
| `tests/Feature/LinkCreationTest.php`             | Link creation + URL validation |
| `tests/Feature/DuplicateDetectionTest.php`       | Collision retry in short-code generator |
| `tests/Feature/RedirectAndClickCountingTest.php` | Redirect + click counting + 404 handling |
| `tests/Feature/ExpiredLinkTest.php`              | Expired link handling (410 + no logging) |
| `tests/Feature/RateLimitTest.php`                | 10 / hour / IP throttle |
| `tests/Feature/StatsOutputTest.php`              | Stats page output + bucketing |

---

## Project structure

```
app/
  Http/
    Controllers/
      LinkController.php          # GET /, POST /shorten, GET /r/{code}
      RedirectController.php      # GET /{code} → 302 + queues click log
      StatsController.php         # GET /stats/{code}
    Middleware/
      LogClicks.php               # terminable middleware: writes click log after response
    Requests/
      CreateLinkRequest.php       # url validation, expires_at validation
  Models/
    Link.php                      # short_code, original_url, click_count, expires_at, isExpired
    ClickLog.php                  # ip_address, user_agent, referer, clicked_at
  Services/
    ShortCodeGenerator.php        # base62 random + collision check
    ClickRecorder.php             # transactional write of log + counter

database/
  database.sqlite                 # local SQLite DB (created by setup)
  migrations/
    2026_05_26_000001_create_links_table.php
    2026_05_26_000002_create_click_logs_table.php
  factories/
    LinkFactory.php
    ClickLogFactory.php
  seeders/
    DatabaseSeeder.php            # 5 demo links + ~50 click logs

resources/
  views/
    layouts/app.blade.php         # shared header/footer/css
    links/
      home.blade.php              # GET /
      result.blade.php            # GET /r/{code}
      stats.blade.php             # GET /stats/{code}
      expired.blade.php           # 410 view
    errors/
      404.blade.php               # unknown short code
      429.blade.php               # rate limit reached

design/                           # static HTML/CSS mockups (Phase 1 deliverable)
  assets/styles.css
  index.html, result.html, stats.html, expired.html, not-found.html
  README.md                       # design system documentation

public/
  css/app.css                     # same stylesheet, served by Laravel
  index.php                       # entry point

routes/web.php                    # all 6 web routes
tests/Feature/                    # 6 test files, 27 methods, 230 assertions
```

---

## Design notes

The UI is a polished product surface, not a generic SaaS template. See [`design/README.md`](design/README.md) for the full design system documentation. In short:

- **Two typefaces:** Inter for everything UI, JetBrains Mono for codes/IPs/timestamps.
- **Two accents:** indigo `#4F46E5` (brand, primary actions, bars), used surgically.
- **Restrained shadows and 8–12 px radii** — Linear/Vercel aesthetic.
- **No CSS framework**, no JavaScript framework. ~28 KB of hand-written CSS, two tiny inline scripts for the Copy buttons.

---

## Performance notes

The brief asks: *"click logging should not noticeably slow down the redirect — consider quick optimizations."* Here's what's done:

1. **Deferred logging via terminable middleware** — `App\Http\Middleware\LogClicks::terminate()` runs *after* the response is flushed to the visitor. Visitors get the 302 immediately; the DB write happens in the background.
2. **Single transaction per click** — the click log insert and `click_count` increment are wrapped in `DB::transaction()`, so the two are always consistent and only one round-trip is paid.
3. **Indexed lookups** — `links.short_code` is unique (= indexed), and `click_logs` has an index on `(link_id, clicked_at)` for the stats query and one on `clicked_at` for the 24-hour KPI.
4. **One query for the 14-day chart** — `StatsController::buildDailySeries()` issues a single `GROUP BY date(clicked_at)` query and pads missing days in PHP.
5. **Logging failures are swallowed** (and reported via `report()`) — a DB hiccup must never leak to a visitor whose redirect already succeeded.

In production with PHP-FPM, the terminable middleware fires *after* `fastcgi_finish_request()` is called by Symfony's HttpFoundation when the response goes out — so even the DB write doesn't block.

---

## Deployment

### Option A — Shared / WAMP hosting (simplest)

The project already runs under WAMP at `D:\wamp64\www\Link Shortner`. To deploy to typical shared hosting (cPanel, Plesk):

1. Upload all files (except `node_modules/` if you ever added any) to your host.
2. Set the **document root** to `public/`.
3. Make `storage/`, `bootstrap/cache/`, and `database/` writable by the web user (`chmod -R 775` on Linux).
4. Run `composer install --no-dev --optimize-autoloader` on the server.
5. Copy `.env.example` → `.env`, run `php artisan key:generate`, set `APP_URL` to your domain.
6. Run `php artisan migrate --seed --force`.
7. Optionally run `php artisan config:cache && php artisan route:cache`.

### Option B — Railway (free tier, fastest path to a public staging URL)

[Railway](https://railway.app) gives you a public HTTPS URL in ~3 minutes.

1. Push the repo to GitHub.
2. On Railway, *New Project → Deploy from GitHub*. Pick this repo.
3. Railway auto-detects Laravel via the `composer.json`. Confirm the build command:
   ```
   composer install --no-dev --optimize-autoloader && php artisan config:cache && php artisan migrate --force
   ```
4. Set environment variables in the Railway dashboard:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=         # use the output of `php artisan key:generate --show`
   APP_URL=https://your-app-name.up.railway.app
   DB_CONNECTION=sqlite
   ```
5. Add a **persistent volume** mounted at `/app/database` so the SQLite file survives redeploys. Set `DB_DATABASE=/app/database/database.sqlite`.
6. Click *Deploy*. Railway prints a public URL on success.

For multi-instance deployments, swap `DB_CONNECTION=sqlite` for MySQL or PostgreSQL — no code changes needed; just rerun `php artisan migrate --seed`.

### Option C — Render / Fly.io / DigitalOcean App Platform

Same recipe as Railway. Use the included `composer.json` build script. Mount a persistent volume for `database/database.sqlite`. Set `APP_KEY` and `APP_URL`.

---

## Packaging a ZIP for delivery

```bash
# 1. Make sure vendor/ is fresh and dev deps are excluded
composer install --no-dev --optimize-autoloader

# 2. (Optional) compile route + config cache so the recipient gets a warm start
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Clear the SQLite database file (if you don't want to ship live data)
rm database/database.sqlite
touch database/database.sqlite

# 4. Create the ZIP, excluding caches, IDE files, and your local .env
git archive --format zip --output ../trim.zip HEAD
#    Or, if not using git:
#    Compress-Archive -Path * -DestinationPath ..\trim.zip \
#      -ExclusionPath @('.git', '.env', 'storage\logs\*.log', 'node_modules')
```

Recipient setup is then just steps 2–6 of [Setup (local)](#setup-local).

> **Heads up:** `.env` is intentionally NOT included in the archive — the recipient must copy `.env.example → .env` and generate their own `APP_KEY`. The bundled `.env.example` is pre-configured for SQLite.

---

## License

MIT — do whatever you want with this. See LICENSE if present, or treat it as MIT by default.

---

*Built by [your name], May 2026.*
