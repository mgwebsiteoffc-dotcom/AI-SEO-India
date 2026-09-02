# Shopify Public App Submission — Approval Checklist & Evidence Map

This file is the single source of truth for getting **AI Visibility** approved as a
**public app** on the Shopify App Store. Read it top to bottom before submitting;
each item maps to a real file or a Partner-dashboard step so the review team can
verify quickly.

> Current status markers: ✅ = implemented in this repo · ⚙️ = one-time step you do in
> the Partner dashboard · ☑️ = verify right before submitting.

---

## 1. Before you submit (non-negotiables)

| # | Requirement | Status | Where / how |
|---|---|---|---|
| 1.1 | Public HTTPS app URL (no `http`, no localhost) | ⚙️ | Deploy this repo to your PHP host. Every callback below must be reachable. |
| 1.2 | Shopify Partner account + app created | ⚙️ | [partners.shopify.com](https://partners.shopify.com) → **Apps → Create app**. |
| 1.3 | App listed as **Public** (not custom/unlisted) | ⚙️ | App setup → Distribution → "Public app". |
| 1.4 | GDPR webhooks registered (`customers/data_request`, `customers/redact`, `shop/redact`) | ✅ | `app/Webhooks/CustomerDataRequestHandler.php`, `CustomerRedactHandler.php`, `ShopRedactHandler.php`; registered in `app/Shopify/OAuthService.php` and `shopify.app.toml`. |
| 1.5 | Privacy policy + terms pages public, no login, from the app itself | ✅ | `/privacy`, `/terms` (`routes/web.php` → `MarketingController`). Set in Partner dashboard: App setup → Privacy policy URL. |
| 1.6 | App works unauthenticated on fresh installs (no hidden server errors) | ✅ | Graceful no-credentials path: `/install`, `/auth/install` explains instead of crashing (`AuthController`, `ShopifyService::init()`). |
| 1.7 | `app/uninstalled` webhook handled | ✅ | `app/Webhooks/AppUninstalledHandler.php` — token revoked, plan dropped to Free. |

## 2. Partner dashboard configuration (one-time, exact values)

| Field | Value |
|---|---|
| App URL | `https://<your-app-domain>/` |
| Allowed redirection URL(s) | `https://<your-app-domain>/auth/callback` |
| Embedded app | ✅ ON |
| App Proxy (optional for SEO proxies) | Subpath prefix `apps`, subpath `ai-visibility`, URL `https://<your-app-domain>/apps/ai-visibility` |
| Webhook delivery | `https://<your-app-domain>/webhooks` |
| Privacy policy URL | `https://<your-app-domain>/privacy` |
| App Store listing privacy page | Same `/privacy` URL (public) |

## 3. Access scopes — justified, minimal set

Shopify reviews scope requests and rejects apps requesting scopes they never use.

| Scope | Why AI Visibility needs it | Code evidence |
|---|---|---|
| `read_products` | Smart Blogger catalog references; llms.txt generator reads products | `SmartBlogger::catalogProducts()`, `LlmsGenerator::buildFromCatalog()` |
| `write_products` | Schema Builder writes Organization/Product/FAQ JSON-LD via `metafieldsSet` | `SchemaService` |
| `read_orders` | `orders/paid` webhook payload → AI traffic attribution | `OrdersPaidHandler`, `AttributionService` |
| `read_themes` / `write_themes` | Theme App Extension + storefront schema injection | `extensions/theme-app-extension/` |
| `read_content` / `write_content` | Blog/article create + read for publishing articles | `SmartBlogger::publish()` / `ensureBlog()` |

**Removed on purpose** (previously listed, never called): `read_customers`,
`read_locations`, `read_analytics`. Fewer scopes = fewer review questions.

## 4. GDPR — the three mandatory webhooks

Shopify sends these to **every** public app:

| Topic | Handler | Behaviour |
|---|---|---|
| `customers/data_request` | `CustomerDataRequestHandler` | Responds 200, logs the request with the customer's lead data (retrieval point for the support inbox). |
| `customers/redact` | `CustomerRedactHandler` | Deletes every `leads` row matching the customer email(s). |
| `shop/redact` | `ShopRedactHandler` | Deletes the store row + all derived analytics (snapshots, audits, content, orders, billing). |

Data map (keep in your App Store "data usage" answers):
- Merchant data: store domain, Shopify token (server-side, never shared), plan/billing state.
- Customer data: only public-scorecard leads (email, brand, optional shop URL, source, date).
- No payment card data, no bank details, no customer addresses/phone numbers — order webhooks persist only order id/amount/channel.

All three handlers respond with HTTP 200 after verification (SDK `Registry::process`),
which satisfies "acknowledge within seconds".

## 5. Embedded app UX rules that reviewers check

- ✅ Embedded via App Bridge (`app.blade.php` loads `app-bridge.js`, `data-api-key`, `data-host`).
- ✅ Session tokens used (`Bearer` JWT) via `resources/js/api.js`; API verifies with `VerifyShopifySession`.
- ✅ No login wall *inside* the iframe — OAuth happens before embedding; demo bypass only in non-production.
- ✅ No password collection. Support contact is a WhatsApp deep-link (configurable in Settings).
- ✅ Works without the merchant having a separate account.
- ☑️ Test the app on a **development store** in both desktop & mobile admin before submitting.

## 6. Billing (only if you list paid plans)

- Recurring INR charges via `appSubscriptionCreate` (`BillingService`); Free tier requires no charge.
- ✅ Demo fallback path is clearly labelled and only reachable without live credentials.
- ☑️ Set prices in the Partner dashboard listing to match `/admin/settings` defaults (Grow ₹999, Scale ₹1,999, Agency ₹4,999/mo, annual = 10×).
- ☑️ If you enable the 3-day trial (`trial_ends_at` on OAuth), the subscription screen shows the trial — confirm text on a dev store.

## 7. App Store listing assets (upload in Partner dashboard)

| Asset | Required size | This repo |
|---|---|---|
| App icon | Square PNG ≥ 200×200 (we ship 1024) | `docs/app-store/icon-ai-visibility.png` |
| Store card logo (optional) | 512×512 PNG | reuse icon |
| Screenshots | 1280×800 (up to 20) | **Draft mockups**: `docs/app-store/listing/*.png` (6). Replace each with a real capture before submit (see §9 + §11). |
| Demo video (optional) | ≤ 4 min | spec in `docs/APP-STORE-LISTING-COPY.md` §6 |
| Listing copy | — | `docs/APP-STORE-LISTING-COPY.md` |

## 8. Listing copy & data-usage answers

- Description copy + support replies: `docs/APP-STORE-LISTING-COPY.md` (short/long description, category, keywords).
- "Data usage" answer in listing:
  > *We collect: (1) shop domain + Shopify API token (server-side) to run the app;
  > (2) the email you enter on our public AI-readiness scorecard. We do not collect
  > or sell customer personal data from order webhooks. Deleting the app removes
  > your store data; GDPR webhooks handle customer erasure automatically.*
- Category suggestion: **Marketing → SEO** (or "Sales channels" is not correct here).

## 9. Pre-submission smoke test (do on a development store)

```bash
# 1. Deploy, then from the repo root (needs the Shopify CLI + partner login):
shopify app deploy --path .          # installs theme app extension for reviewers
# 2. Install on a dev store:
#    https://<your-domain>/auth/install?shop=<dev-store>.myshopify.com
# 3. Verify:
#    - App opens embedded, no console errors, tabs render.
#    - Schema Builder "Install" → metafields appear on the store.
#    - Smart Blogger → generate → publish → article appears in Online Store → Blog Posts.
#    - /apps/ai-visibility/llms.txt + /schema?path=/products/<handle> return content on the store domain.
#    - Uninstall the app → /webhooks/app/uninstalled fires → store row cleaned.
```

## 10. Final checklist (send with your submission notes)

- [ ] 1.1–1.7 pass · scopes match §3 · GDPR handlers registered & tested
- [ ] Privacy policy URL loads without login and mentions data categories above
- [ ] App icon & screenshots uploaded; screenshots don't show another store's data
- [ ] A dev-store install URL you can share with Shopify review is noted
- [ ] No hard-coded secrets/keys in the repo or screenshots
- [ ] Support email in the Partner dashboard is monitored
- [ ] App Store listing published as **Public** and submitted for review

## 11. One-pass review kit (run the morning you submit)

Order matters — do 11.1 → 11.8, then hit submit.

1. **Deploy + CLI:** `shopify app deploy --path .` (installs the theme app
   extension reviewers use) and push the latest code to the public HTTPS host.
2. **Fresh dev-store install:** create a new dev store in Partners → install →
   watch for console errors in the embedded admin; run one audit + one IndexNow
   flush + one Smart Blogger publish.
3. **Real screenshots:** capture the 6 screens listed in
   `docs/APP-STORE-LISTING-COPY.md` §6 from that dev store, crop to 1280×800,
   drop them over the drafts in `docs/app-store/listing/`.
4. **Secrets scan:** `grep -RInE "(api[_-]?key|secret|password|token)\s*[:=]\s*[\"']" --exclude-dir=vendor --exclude-dir=node_modules .`
   — nothing real may remain; env-only values are fine.
5. **Legal URLs:** load `/privacy` and `/terms` on the public domain, no login,
   and confirm both URLs are pasted into the Partner dashboard.
6. **Pricing sync:** listing plan prices == app `/api/billing/plans` values
   (Free/Grow ₹999/Scale ₹1,999/Agency ₹4,999, annual 10×) ==
   `/admin/settings` price fields.
7. **Support inbox:** reply-to address is monitored (Shopify emails the
   merchant-facing support contact during review).
8. **Submit notes:** include the dev-store URL + a 2-line summary of how the
   app is verified (fresh install → audit → schema → publish → proxy files).

**Common one-pass rejections we have pre-empted:**
- *Claimed features not present / misleading screenshots* → listing copy §4 is
  generated from implemented features; screenshots must be real (step 3).
- *Broken install/console errors* → step 2 before every submission.
- *Missing GDPR webhooks* → §4 registered + handlers tested.
- *Scope creep* → §3 minimal scope list, no unused scopes.
- *Logins/password walls inside the iframe* → OAuth-first; merchant has no app
  account; owner login lives only at `/admin` outside the app.
- *Pricing mismatch between listing and app* → step 6.
- *Unclear data usage* → §7 text is the exact answer to paste.
