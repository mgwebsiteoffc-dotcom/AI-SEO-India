# AI-SEO-India — Delivery Report

**Date:** 2026-09-01 (updated 2026-09-02) · **Branch:** `arena/01a05cd6-ai-seo-india`
**Live preview:** https://8123-iw4dbltg87lvou1h9cl2j.e2b.app (sandbox server on port 8123)
**Demo mode:** https://8123-ij9ba5t021hsj4rstrav6.e2b.app/app?demo=1 (store: `demo-brand.myshopify.com` / "Aurelia Naturals", domain `aurelianaturals.in`, plan **scale**)

---

## 1. What was missing / broken, and what was done

### A. It didn't run at all (framework level)
| Problem | Fix |
|---|---|
| **Laravel 12 command discovery:** `app/Console/Commands` is no longer auto-discovered. `demo:seed`, `visibility:track` and the scheduler silently didn't exist. | `bootstrap/app.php` now has `->withCommands([app_path('Console/Commands')])`. `artisan list` shows both commands; `schedule:list` shows the daily job. |
| **Autoloader root mapping:** the generated vendor autoloader had no root `App\ → app/` PSR-4 entry, so `App\...` classes failed to resolve. | Autoloader regenerated with the root mapping. |
| **No `.env` / APP_KEY / DB.** | `.env` created: APP_KEY set, SQLite database, APP_URL = the HTTPS preview host so every generated URL (assets, OG image, canonical) points at the public site — `URL::forceRootUrl()` + `forceScheme('https')` in `AppServiceProvider::boot()` (Laravel otherwise derives URL roots from the request Host, producing `127.0.0.1` links). Verified with curl. |
| **Sandbox reset killed the site again (2026-09-02).** The Arena sandbox (and its preview host) was recreated, wiping `.env`, `node_modules`, `vendor/`, `public/build/` and the DB — so the old preview URL died and pages rendered unstyled (no CSS). Now fully recoverable: | Added `scripts/bootstrap.sh` (one-command restore: npm ci → WASM PHP → fetch 78 composer packages straight from GitHub via `scripts/fetch-vendor.mjs` → generate a PSR-4 autoloader + Laravel `installed.json` → `.env` with APP_URL derived from the live sandbox id → migrate → `demo:seed` → vite build), `scripts/server-router.php` (in-repo dev-server router), and an `App\Support\Vite` helper that emits **root-relative** asset URLs from the Vite manifest — `@vite()` was replaced in every view, so CSS/JS load on *any* host regardless of APP_URL. |
| **`demo:seed` was not idempotent** — re-seeding the same day crashed on a UNIQUE constraint (`competitor_mentions`), aborting before blog posts were re-seeded. | Seed now wipes `CompetitorMention` rows for the store first; verified 2 consecutive runs both exit 0. |

### B. Real functional bugs
| Problem | Fix |
|---|---|
| **Demo mode was a blank dashboard:** the SPA called `/api/*` without the `?demo=1` flag that `VerifyShopifySession`/`VerifyProxyRequest` require to resolve the demo store → every call 401. | `resources/js/api.js` now appends `?demo=1` (or `&demo=1`) whenever `window.demoMode` is set. |
| **Demo badge never rendered:** `App.vue` read `window.demoMode` at module-eval time, but `app.js` sets it *after* importing `App.vue` → always `undefined`. | `App.vue` reads `el.dataset.demo === '1'` directly from the DOM. |
| **Audit crashed on unreachable domains:** `AuditService::has(string $haystack, …)` got `null` when a site can't be reached → 500 instead of a score. | Signature is now `has(?string $haystack, …)`. A live audit of an unreachable domain now completes honestly (score 53, issues: robots_missing, sitemap_missing, site_unreachable, no_faq, no_social_links, speed_unknown). |
| **Sessions reset on every request** (saw 419 "Page Expired" on the scorecard lead form): the file session driver relies on `flock()`, which is a no-op under the WASM PHP runtime → CSRF token never matched. | `SESSION_DRIVER=database` in `.env`. Lead capture now persists (verified row in `leads`). On native PHP either driver works; database is the more production-safe choice for this app anyway. |
| **Scheduler ran at the wrong time:** README promises "daily 6 AM IST", app timezone was UTC → 6 AM UTC = 11:30 AM IST. | `config/app.php` timezone → `Asia/Kolkata`. `schedule:list` now shows the snapshot at 06:00 IST. |
| **Billing demo response** exposed only `confirmationUrl` (camelCase) — the frontend needed both shapes. | `BillingService` demo response now exposes `confirmationUrl` + `confirmation_url`. |
| **Smart Blogger** threw a cryptic error when Shopify credentials were missing. | Friendly, actionable error message. |
| **API `summary` access** could hit null on a fresh store. | Null-safe access in `ApiController`. |

### C. Missing deliverables — added
- **Premium marketing site** — home, pricing, scorecard, blog index, and blog post all rebuilt on a dark premium shell (`resources/views/marketing/partials/{head,header,footer}.blade.php` + `resources/css/app.css`): brand tokens (#0a84ff), glass cards, gradient text, animated hero, grid pattern, dark prose, hover effects, "Start free" / "View live demo" CTAs.
- **Legal pages — `/privacy` and `/terms`** (required for the Shopify App Store listing). Terms include the "no guarantees about AI ranking" clause and Bengaluru/India jurisdiction; both contact via WhatsApp (English/Hinglish).
- **Brand assets** — `public/favicon.svg` (blue orb + spark) and `public/og-image.svg` (demo AI Readiness Score 74/100 + ChatGPT/Gemini tracker mockup); wired into the marketing head, the login page, and the embedded app shell.

---

## 2. Verified working (end-to-end smoke, 2026-09-01)

| Area | Result |
|---|---|
| Public pages: `/`, `/pricing`, `/scorecard`, `/blog`, `/blog/<post>`, `/privacy`, `/terms`, `/health` | all **200** |
| App shell `/app?demo=1` | **200**, renders `DEMO MODE` + demo store/plan data |
| GET APIs (`dashboard`, `audit/latest`, `tracker`, `tracker/competitors`, `llms`, `schema/status`, `billing/plans`, `attribution`, `attribution/ga4`, `content`, `settings`) | all **200** |
| App-proxy endpoints `robots.txt`, `llms.txt`, `sitemap.xml`, `schema?path=/` | all **200**; llms.txt content verified (Aurelia Naturals, products/collections) |
| `POST content/generate` | generates a full template article (404 words) |
| `POST billing/subscribe` (demo) | returns confirmation URL; callback persists plan `grow`/active with end date |
| `POST settings`, `POST tracker/query`, `POST tracker/run` | 200/201; tracker ran 6 web queries |
| `POST audit/run` | completes (no crash) with honest low score for unreachable demo domain |
| `POST schema/install`, `POST content/publish` | graceful errors without real Shopify creds |
| **Lead capture form** (scorecard) | CSRF now passes; lead persisted in DB |
| `artisan demo:seed` | idempotent — re-seeded pristine demo (plan `scale`, 6 queries) |
| Scheduler | `0 6 * * * php artisan visibility:track --all` (IST) |

---

## 3. How to run it

```bash
# Sandbox / fresh clone (one command — rebuilds .env, vendor/, node_modules, build/, DB)
scripts/bootstrap.sh

# Dev server
node_modules/.bin/php-wasm-cli -S 0.0.0.0:8123 -t public scripts/server-router.php

# Frontend build (Vite)
npm ci && npm run build

# Native PHP (production / any real host — composer.json requires PHP ^8.3)
composer install
cp .env.example .env && php artisan key:generate   # set DB + Shopify + AI keys
php artisan migrate --seed
php artisan demo:seed
php artisan serve                                    # http://127.0.0.1:8000

# Demo without Shopify: open /app?demo=1  (or /auth/demo)
# Cron (daily 6 AM IST AI snapshot):
#   0 6 * * * php /path/to/app/artisan schedule:run >> /dev/null 2>&1
```

---

## 4. Honest platform limits (could not be tested in this sandbox)

1. **No browser automation here** — there is no Playwright/Chromium in this environment, so verification was done at the HTTP level (status codes, JSON payloads, DB rows) plus one production-quality Vite build. Pixel-level layout check happens in your browser at the live preview URL above.
2. **No real Shopify OAuth** — without real Shopify app credentials, live install, real blog publishing, and real billing callbacks can't be exercised end-to-end. All of those paths were verified in their **demo/graceful modes**; the hooks (signed proxy, JWT auth, Theme App Extension) are in place per Shopify's documented formats.
3. **Sandbox runtime quirk** — PHP here runs via the project-local WASM PHP (`node_modules/.bin/php-wasm-cli`); vendor packages live under `/tmp/vendor-php` (sandbox-only, restored on demand from a tarball cache). This does not affect the codebase, which targets native PHP 8.3+.

---

## 5. Feature map vs. the plan (README "All built" section)

| Planned feature | Status |
|---|---|
| AI Readiness Score + Action Plan | ✅ built + verified |
| AI Visibility Tracker + competitor watch | ✅ built + verified |
| `llms.txt` / `robots.txt` / `sitemap.xml` (app proxy) | ✅ built + verified |
| Schema Builder (JSON-LD, theme extension) | ✅ built + graceful-mode verified |
| Smart Blogger + publish to Shopify blog + sentiment | ✅ built + graceful-mode verified |
| AI-traffic → orders attribution (incl. GA4 demo report) | ✅ built + verified |
| INR billing — Free / ₹999 / ₹1,999 / ₹4,999, monthly + annual (−17%) | ✅ built + verified (demo callback) |
| WhatsApp number + language settings | ✅ built + verified |
| Public marketing site (home / pricing / scorecard / blog) | ✅ **premium redesign** |
| Lead capture | ✅ built + verified (CSRF fixed) |
| Founder blog with JSON-LD | ✅ built + verified |
| Demo mode `/?demo=1` | ✅ built + verified (401 bug fixed) |
| Privacy + Terms pages (Shopify App Store requirement) | ✅ **added** |
