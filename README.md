# AI Visibility — Native Shopify App for AI SEO (India-first)

> Get recommended by ChatGPT, Gemini & Perplexity when Indian shoppers ask.
> Laravel 12 backend (MySQL) + native Shopify embedded app (App Bridge + Vue 3)
> + Theme App Extension + public marketing website.

Built from the research in `../ai-seo-shopify-india-plan.md`: honest AI-visibility
tooling for Indian D2C brands — tracking, schema, crawlability, content, attribution —
priced in INR (Free / ₹999 / ₹1,999 / ₹4,999 per month, monthly or annual).

---

## Run it locally (Laragon / XAMPP / `php artisan serve`)

> **Use the latest code first:** `git pull` (this repo's `main` now contains the full
> site — merged from `arena/01a05cd6-ai-seo-india`). If you only see the default
> Laravel welcome page, you are on old code.

1. **PHP 8.3+** — Laragon: *Menu → PHP → 8.3.x* (8.2 won't install dependencies).
2. `composer install`
3. Create `.env`: `copy .env.example .env`, then pick a database:
   - **SQLite (easiest, no server needed):**
     ```env
     DB_CONNECTION=sqlite
     ```
     then `type nul > database\database.sqlite` (Windows) / `touch database/database.sqlite` (mac/Linux)
   - **MySQL (Laragon default):** create a database in Laragon, then in `.env`:
     ```env
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=ai_visibility
     DB_USERNAME=root
     DB_PASSWORD=
     ```
4. `php artisan key:generate`
5. `php artisan migrate --force`
6. `php artisan demo:seed` — demo store + blog posts + owner-panel demo data
7. `npm install && npm run build` — **required or every page renders unstyled**
8. `php artisan serve` → open **http://127.0.0.1:8000**

Pages to check: `/` · `/pricing` · `/features` · `/how-it-works` · `/faq` · `/scorecard`
(live scan) · `/install` · `/demo-store` · `/blog` · `/admin` (owner panel, incl.
**`/admin/settings`** — engine toggles, scheduler, plan prices) · `/app?demo=1` (the app).

**Common issues**
- *Only the landing page works / rest 404* → you're on an old `main`; `git pull`.
- *Pages render but have no styling* → `npm run build` wasn't run.
- *500 errors on any page* → missing `.env` / `APP_KEY` (`php artisan key:generate`) or the database wasn't migrated.
- *Shopify features say "not connected"* → expected locally; real install needs Partner
  credentials. Use `/install` → **Install demo store**, or `/app?demo=1`.

> Note: `127.0.0.1:8123` is only the hosted preview sandbox's internal port — your
> browser cannot reach it. Use the public preview URL provided in the sandbox, or run
> locally on `127.0.0.1:8000` as above.

---

## Feature map (everything from the plan, built)

| Feature | Status | Where |
|---|---|---|
| AI Readiness Score + Action Plan | ✅ | `/app` → AI Score & Audit |
| AI Visibility Tracker (queries × engines, daily) | ✅ | `/app` → AI Visibility Tracker |
| Competitor watch (you vs competitors in AI answers) | ✅ | Tracker tab |
| llms.txt + robots.txt + sitemap.xml (App Proxy) | ✅ | `llms.txt` tab |
| Schema Builder (Org/Product/FAQ JSON-LD, ₹ offers) | ✅ | Schema tab |
| **Smart Blogger** — AI content engine (Indian English / Hinglish) | ✅ | Smart Blogger tab |
| **Publish to Shopify blog** (Admin API, one click) | ✅ | Smart Blogger tab |
| **AI Sentiment Analysis** (how AI answers feel about you) | ✅ | Smart Blogger tab |
| **AI Traffic → Orders attribution** (orders/paid webhook) | ✅ | AI Traffic tab |
| INR billing, monthly + **annual (−17%)** | ✅ | Plans & Billing tab |
| WhatsApp number + language (en/hinglish/hi) settings | ✅ | Settings tab |
| **Public marketing website** (landing, pricing, scorecard, blog) | ✅ | `/` `/pricing` `/scorecard` `/blog` |
| Lead capture (free scorecard email) | ✅ | `/scorecard` |
| Founder blog with BlogPosting JSON-LD | ✅ | `/blog` |
| Demo mode (no Shopify needed) | ✅ | `/?demo=1` |
| **SaaS owner panel** — stores, plans, MRR, leads, activity | ✅ | `/admin` |
| **Owner settings** — toggle AI engines, tracker schedule, plan prices, run now | ✅ | `/admin/settings` |
| **Instant indexing (IndexNow)** — product/blog changes pinged for fast AI-index freshness | ✅ | `/admin/settings` → IndexNow |
| **agent.md** — storefront AI-agent manual on the App Proxy | ✅ | `llms.txt` tab · `/apps/ai-visibility/agent.md` |
| **Shareable AI Readiness Scorecard** — public link + OG card per scan | ✅ | `/scorecard/{token}` |
| Plan limits enforced (tracked queries + competitors per plan) | ✅ | API + UI |
| Shopify **public-app approval kit** — GDPR webhooks, scopes, listing copy, CLI config | ✅ | `docs/SHOPIFY_APP_STORE_SUBMISSION.md` |

---

## Try it now (demo mode — no Shopify account needed)

```bash
# 1. Start MySQL (workspace datadir, MariaDB-compatible; standard MySQL in prod)
./scripts/db-start.sh          # first run initializes /home/user/mysql-data
mysql -h 127.0.0.1 -u ai_vis -paivisibility ai_visibility \
  -e "CREATE DATABASE IF NOT EXISTS ai_visibility"   # or run the SQL in scripts/setup-mysql.sql

# 2. App
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --force
php artisan demo:seed                     # demo store + blog + content + orders
npm install && npm run build
php artisan serve --host=0.0.0.0 --port=8123
```

Open:
- **`http://127.0.0.1:8123/`** → marketing website (landing / pricing / scorecard / blog)
- **`http://127.0.0.1:8123/app?demo=1`** → full embedded dashboard with seeded data
- `http://127.0.0.1:8123/health` → uptime check

Run a live check: `php artisan visibility:track --all` (honest retrieval-proxy mode;
add `OPENAI_API_KEY`/`GEMINI_API_KEY` for true LLM answer checking).

### MySQL setup (dev sandbox)

The dev datadir lives in the workspace (`/home/user/mysql-data`) so it persists.
The app user is `ai_vis` / password `aivisibility` on `ai_visibility` (see `.env`).
Production: use your platform's managed MySQL (see `.env.example`).

---

## Architecture

```
Shopify Admin (embedded iframe)
   │  App Bridge (session token → Bearer JWT)
   ▼
Laravel 12 app (this repo) — MySQL
   ├─ /auth/install, /auth/callback        OAuth (offline token → stores.shopify_token)
   ├─ /api/*                               JSON API for the SPA (JWT-protected)
   │   dashboard · audit · tracker (+competitors) · llms · schema · content (Smart Blogger)
   │   sentiment · attribution · billing (monthly/annual) · settings
   ├─ /apps/ai-visibility/*                App Proxy (signed) — served on store domain
   │   llms.txt · robots.txt · sitemap.xml · schema (JSON-LD for theme ext)
   ├─ /webhooks/{topic}                    app/uninstalled, products/update, orders/paid
   └─ /billing/callback                    returns from Shopify checkout (INR, 30d/12mo)
Shopify Storefront
   └─ Theme App Extension (extensions/theme-app-extension)
       └─ injects JSON-LD + <link rel="llms.txt"> on every page
Public web (no session)                    / (landing) /pricing /scorecard /blog
```

| Piece | Tech |
|---|---|
| Backend | Laravel 12 (PHP 8.4), **MySQL** (MariaDB-compatible), queues, scheduler |
| Shopify integration | `shopify/shopify-api` v6 (OAuth, GraphQL, webhooks, billing) |
| Embedded app | App Bridge (CDN) + Vue 3 SPA (Vite + Tailwind v4) |
| Storefront | Theme App Extension (`extensions/theme-app-extension`) |
| Smart Blogger | OpenAI / Gemini (LLM mode) or no-key template engine |
| Attribution | `orders/paid` webhook → landing/referring UTM detection |
| Billing | `appSubscriptionCreate`, INR, EVERY_30_DAYS / EVERY_12_MONTHS |

---

## Production setup (step by step)

### 1. Shopify Partner account + app
1. [partners.shopify.com](https://partners.shopify.com) → **Apps → Create app**.
2. Client ID / secret → `.env` (`SHOPIFY_API_KEY` / `SHOPIFY_API_SECRET`), host = your https domain.
3. App setup: App URL `https://your-domain.com/`, redirect `https://your-domain.com/auth/callback`,
   App Proxy subpath `apps/ai-visibility` → `https://your-domain.com/apps/ai-visibility`,
   embedded ON, webhook delivery `https://your-domain.com/webhooks`.
4. Scopes (minimum set the code actually calls — see `docs/SHOPIFY_APP_STORE_SUBMISSION.md` for the justification map):
   `read_products, write_products, read_orders, read_themes, write_themes, read_content, write_content`.

### 2. Deploy
- Any PHP 8.2+ host with MySQL; public **HTTPS** mandatory.
- `composer install --no-dev`, `php artisan migrate --force`, `npm ci && npm run build`.
- Scheduler cron (runs the daily 6 AM IST AI snapshot):
  ```
  * * * * * php /path/to/app/artisan schedule:run >> /dev/null 2>&1
  ```

### 3. Theme App Extension
```bash
npm i -g @shopify/cli && shopify app deploy --path .
```
Then in the store: **Online Store → Themes → Customize → Add block → Apps → AI Visibility**.

### 4. Verify
Visit `https://your-domain.com/auth/install?shop=test.myshopify.com` → approve → run your first audit.

---

## Inside the app

### Audit (`app/Services/AuditService.php`)
30+ checks across 5 weighted categories (crawlability 30 · schema 25 · content 25 · brand 15 · speed 5):
robots.txt reachable & AI crawlers allowed, sitemap, JSON-LD on home + Product schema on
product pages, H1/title quality, thin-content & FAQ detection, brand prominence, trust
signals, TTFB. Score 0–100 + grade + shareable action plan.

### Tracker (`app/Services/AiVisibilityService.php`)
- **LLM mode** (API key set): asks the real model per query whether your brand is
  mentioned/cited (strict JSON), per engine.
- **Retrieval-proxy mode** (no key): live web-result checks — the honest free proxy for
  "would an AI engine find you". Daily snapshot at 6 AM IST; competitor mention rates included.

### Smart Blogger (`app/Services/SmartBlogger.php`)
- LLM mode: India-flavoured comparison/FAQ articles (₹ pricing, catalog references,
  comparison table, 5 FAQs, final verdict) in Indian English or Hinglish.
- No-key mode: structured template engine from your real catalog.
- **Publish** → creates/updates your Shopify blog and article via Admin API (one click).

### Attribution (`app/Services/AttributionService.php`)
- Reads `orders/paid` webhook landing/referring data; detects ChatGPT (utm_source=chatgpt.com),
  Gemini, Perplexity, Grok, Claude, DeepSeek, Copilot; revenue, AOV, channel breakdown,
  14-day trend, recent orders + built-in GA4 guide for deeper reporting.

### Billing (`app/Services/BillingService.php`)
- Recurring INR: Free ₹0 · Grow ₹999 · Scale ₹1,999 · Agency ₹4,999; monthly or annual (−17%).
- 3-day trial via Shopify billing (add `trialDays` to the mutation when publishing).

### GA4 Data API (`app/Services/Ga4Service.php`)
The **AI Traffic** tab pulls AI-sourced sessions / users / transactions / purchase revenue
straight from Google Analytics 4 using a service-account (JWT) grant — no OAuth popup needed.

Setup (5 minutes):

1. **Google Cloud Console** → create a service account → download the JSON key
   (IAM & Admin → Service accounts → Create → Keys → Add key → JSON).
2. **GA4 Admin** → Property access management → grant the service account's email
   **Viewer** access to the property.
3. Configure credentials in `.env`:

   ```env
   GA4_PROPERTY_ID=123456789            # numeric property id from GA4 Admin → Property settings
   GA4_CLIENT_EMAIL=ai-vis@proj.iam.gserviceaccount.com
   GA4_PRIVATE_KEY="-----BEGIN PRIVATE KEY----- ..."
   # or point at the downloaded JSON instead of the two fields above:
   # GA4_SERVICE_ACCOUNT=/absolute/path/to/service-account.json
   GA4_SOURCE_REGEX=chatgpt|openai|perplexity|gemini|bard|grok|claude|deepseek|copilot
   ```

   (The private key must keep its `\n` line breaks — put the whole block in one quoted value.)

4. The app calls `runReport` (Data API v1) with a `sessionSource` regex dimension filter
   for AI platforms, grouped by source + day. Endpoint: `GET /api/attribution/ga4?days=30`
   (demo stores without credentials get a clearly-labelled `demoReport()` payload).

Notes: `sessionSource` is a **session-scoped** dimension — it matches the entry source of
each session, so ChatGPT visits that arrive as `Direct` (free-tier) won't be counted. The
webhook attribution (`orders/paid` → `AttributionService`) catches many of those instead,
so the two reports together give the full picture. Revenue assumes the `purchase` event's
`value`/`currency` parameters are sent (standard Shopify→GA4 flow).

### Webhooks
- `app/uninstalled` → revoke token, drop to free · `products/update` → llms.txt dirty flag
- `orders/paid` → AI attribution (verified end-to-end with signed webhook test).

---

## Project layout

```
app/
  Console/Commands/        SeedDemo, TrackVisibility
  Http/Controllers/        Auth, App, Api, Proxy, Webhook, Health, Content, Marketing
  Http/Middleware/         VerifyShopifySession (JWT), VerifyProxyRequest (signed)
  Services/                AuditService, AiVisibilityService, LlmsGenerator, SchemaService,
                           BillingService, LlmClient, SmartBlogger, AttributionService
  Shopify/                 ShopifyService (SDK init/session/HMAC), OAuthService
  Webhooks/                AppUninstalledHandler, ProductsUpdateHandler, OrdersPaidHandler
  Models/                  13 models (Store, AiSnapshot, Audit*, TrackedQuery, LlmsEntry,
                           ContentPost, Competitor*, AttributedOrder, Lead, Post, AppBilling, WebhookCall)
extensions/theme-app-extension/   native Theme App Extension (JSON-LD + llms.txt link)
resources/js/             Vue 3 SPA — Dashboard, Audit, Tracker, Content, Traffic, Llms,
                          Schema, Billing, Settings
resources/views/          app shell, auth, marketing (home, pricing, scorecard, blog, post)
scripts/db-start.sh       dev MySQL launcher (persistent workspace datadir)
routes/web.php            all routes (public, OAuth, API, proxy, webhooks, billing)
```

## Honest product notes (do not remove)
- Nobody can **guarantee** AI rankings — the app measures and improves real signals
  (schema, crawlability, content, brand). The UI says so, on purpose.
- llms.txt has no confirmed citation effect as of 2026 — shipped as cheap future-proofing
  hygiene; tracking + schema + content do the real work.
- Free-tier ChatGPT visits may arrive without referrer (show as Direct in GA4); the
  webhook attribution catches many of those anyway.

## Shopify public-app submission guide

Everything the review team needs (scope justification, GDPR handlers, Partner
dashboard values, listing copy, pre-submission smoke test) lives in:

- **`docs/SHOPIFY_APP_STORE_SUBMISSION.md`** — the approval checklist + evidence map.
- **`docs/APP-STORE-LISTING-COPY.md`** — ready-to-paste App Store copy.
- `shopify.app.toml` — CLI deploy config (`shopify app deploy --path .`).
- `extensions/theme-app-extension/shopify.extension.toml` — theme extension manifest.
- `docs/app-store/icon-ai-visibility.png` — 1024×1024 listing icon.

## Roadmap (next)
- [ ] Weekly delta email report (mention-rate trends per query/engine)
- [ ] Agency / white-label tier (multi-store dashboard + client reports)
- [ ] Hindi UI + regional content (phase 2)
- [ ] WooCommerce / standalone-site version
- [ ] Per-plan feature-gating switches in `/admin/settings` (map to BillingService plans)
