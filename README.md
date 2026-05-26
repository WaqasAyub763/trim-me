# Trim — Static UI Mockups

Static HTML/CSS mockups for client review. No build step — open any `.html` file in a browser and it renders. All screens share a single stylesheet at `assets/styles.css` and use Google Fonts (Inter + JetBrains Mono).

## Screens

| File             | Purpose                                                              |
| ---------------- | -------------------------------------------------------------------- |
| `index.html`     | **Create** — form to paste a long URL with optional expiry           |
| `result.html`    | **Created** — confirmation screen with the new short link            |
| `stats.html`     | **Analytics** — full per-link dashboard (KPIs, chart, referrers, log)|
| `expired.html`   | **410 Gone** — shown when a visitor hits an expired short code       |
| `not-found.html` | **404** — shown when a short code doesn't exist                      |

All five screens share the same persistent app header (brand mark · primary nav · status pill · version chip) and footer.

## Design system

### Color tokens

| Token              | Value     | Use                                          |
| ------------------ | --------- | -------------------------------------------- |
| `--bg`             | `#FAFAFA` | Page background                              |
| `--bg-elevated`    | `#FFFFFF` | Cards, inputs, buttons                       |
| `--bg-subtle`      | `#F4F4F5` | Card footers, table heads, code blocks       |
| `--fg`             | `#09090B` | Primary text, headings                       |
| `--fg-muted`       | `#52525B` | Secondary text, helper copy                  |
| `--fg-subtle`      | `#71717A` | Tertiary text, captions                      |
| `--fg-faint`       | `#A1A1AA` | Placeholders, separators                     |
| `--border`         | `#E4E4E7` | Cards, separators                            |
| `--border-strong`  | `#D4D4D8` | Form controls                                |
| `--brand`          | `#4F46E5` | Primary buttons, links, focus rings, bars    |
| `--success`        | `#10B981` | Status indicators                            |
| `--warning`        | `#F59E0B` | Expired state                                |
| `--danger`         | `#EF4444` | 404 state, destructive actions               |

### Type

| Family               | Use                                          |
| -------------------- | -------------------------------------------- |
| **Inter**            | Everything — body, headings, buttons, labels |
| **JetBrains Mono**   | Short codes, URLs, IPs, timestamps, counters |

Scale (`--t-xs` → `--t-6xl`): `12 · 13 · 14 · 15 · 16 · 18 · 22 · 28 · 36 · 48 · 56`. Page titles use `--t-4xl` (36px). KPI values use `--t-3xl` (28px). All numeric runs use `font-variant-numeric: tabular-nums lining-nums` so columns align.

### Spacing & radius

4-pt scale: `4 · 8 · 12 · 16 · 20 · 24 · 32 · 40 · 56 · 72 · 96 · 128`. Cards use 8–12px radius, buttons 6px, inputs 6px, pills 999px.

### Shadows

Restrained — `0 1px 2px rgba(9,9,11,0.04)` on cards, focus rings use `0 0 0 3px rgba(79,70,229,0.15)`. No heavy drop shadows.

## Components

The mockups use a small kit, all defined in `styles.css`:

- **`.card`** with optional `.card__header`, `.card__body`, `.card__footer`
- **`.btn`** with `--primary`, `--secondary`, `--ghost`, `--danger`, `--sm`, `--lg` modifiers
- **`.input`** with focus ring, hover state, and `.input--mono` variant
- **`.alert`** with `--success`, `--warning`, `--info` flavors
- **`.kpi`** card with delta indicator (up/down/flat)
- **`.bar-row`** for the chart (3-column grid: label · track · value)
- **`.table`** with sticky-style head, hover rows, mono columns for IP/time
- **`.ref-list`** for referrer breakdown with progress bar underline
- **`.badge`** chip for "Direct" / "Bot" referrer flags
- **`.status-pill`** for "All systems normal" header indicator
- **`.state`** centered card for 404/expired pages

## Bar chart spec

```html
<div class="bar-row bar-row--peak">
  <span class="bar-row__label">
    <span class="dow">Thu</span><span class="date">May 21</span>
  </span>
  <div class="bar-row__track">
    <div class="bar-row__fill bar-row__fill--peak" style="width: 100%"></div>
  </div>
  <span class="bar-row__value">152</span>
</div>
```

Three modifiers:

- **`--peak`** — fills with `--fg` (near-black) instead of `--brand` (indigo) for the highest bar.
- **`--zero`** — renders an empty day as a 2px tick instead of nothing.
- **(default)** — indigo fill, darker on row hover.

This exact markup is portable straight into Blade — only the inline `width:` and label/value text become dynamic.

## Realistic content

Every screen uses representative real-world data:

- **Destination URL:** `https://github.com/laravel/laravel/blob/10.x/README.md`
- **Short code:** `x7Kn2P`
- **Click data:** 1,247 total clicks, varied weekday/weekend pattern peaking at 152 on May 21
- **Referrers:** GitHub, Hacker News, Twitter, Reddit, LinkedIn, dev.to, Stack Overflow + direct/bot traffic
- **IPs:** reserved documentation ranges (203.0.113.x, 198.51.100.x, 192.0.2.x)
- **User agents:** real browser/OS combinations (Safari/macOS, Chrome/Windows, Firefox/Linux, Mobile Safari/iOS, Slackbot, curl)

## Responsive

Three breakpoints:

- **`> 960px`** — full desktop: 4-column KPI grid, 360px-wide referrer card alongside table
- **`≤ 960px`** — KPIs become 2×2, table and referrers stack
- **`≤ 640px`** — mobile: single-column everything, app nav collapses, table hides referrer & user-agent columns

All transitions/animations respect `prefers-reduced-motion`.

## What's intentionally NOT here

- **No JavaScript** — Copy buttons and Export CSV are visual only; they'll be wired with ~15 lines of vanilla JS in Phase 2.
- **No QR code** — flagged optional; trivial to add with `simplesoftwareio/simple-qrcode` in Blade.
- **No dark mode** — a `prefers-color-scheme: dark` block can be added later, flipping the tokens.

## Phase 2 mapping

When approved, each screen ports 1:1 into Blade:

| Mockup           | Blade view                            |
| ---------------- | ------------------------------------- |
| `index.html`     | `resources/views/links/home.blade.php`     |
| `result.html`    | `resources/views/links/result.blade.php`   |
| `stats.html`     | `resources/views/links/stats.blade.php`    |
| `expired.html`   | `resources/views/links/expired.blade.php`  |
| `not-found.html` | `resources/views/errors/404.blade.php`     |

The shared header/footer markup becomes `resources/views/layouts/app.blade.php`. Dynamic values (short code, click counts, bar widths, table rows) come from the relevant Eloquent queries in the controllers.
