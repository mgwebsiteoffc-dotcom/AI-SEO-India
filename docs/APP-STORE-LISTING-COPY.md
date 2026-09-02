# AI Visibility — Shopify App Store Listing Kit (complete)

**Everything to paste into the Partner dashboard so the listing is complete,
accurate and approvable in one pass.** Amounts/prices are the repo defaults —
keep in sync with SaaS admin → Settings → Plan pricing.

> Generated listing-art drafts: `docs/app-store/listing/*.png`
> (mockups — replace with real captures before submitting, see §7).
> Submission checklist & evidence map: `docs/SHOPIFY_APP_STORE_SUBMISSION.md`.

---

## 1. Identity fields (Partner dashboard → App setup → App Store listing)

| Field | Recommended value | Notes |
|---|---|---|
| **App name** | `AI Visibility: AI SEO for Shopify` | Partner "app name" stays short: `AI Visibility`. Visible title ≤ 30–40 chars keeps the row tidy. |
| **Tagline** | `Get recommended by ChatGPT, Gemini & Perplexity` | Short, benefit-led. |
| **Category** | `Marketing` → **`SEO`** | "Marketing → SEO" is the correct hierarchy; do not pick Sales channels. |
| **Add-on category** (optional) | `Analytics` | Matches the AI Traffic & Orders reporting. |
| **Languages** | English (primary); Hinglish/English-India copy inside app | Listing language list: English only until Hindi UI ships. |
| **Supported regions** | All (INR billing + Indian D2C focus) | Pricing shown in ₹; list country of merchant base as India. |
| **Built for Shopify** | ✅ Standard app (embedded admin) | Shopify CLI 3.x project, App Bridge, App Proxy, webhooks, theme app extension. |
| **Works with** | Shopify Admin · ChatGPT · Gemini · Perplexity · Claude · Grok · DeepSeek · Copilot | Only surfaces we genuinely query or connect to. |

## 2. Short description (≤ 80 characters — paste as-is)

```
Get recommended by ChatGPT, Gemini & Perplexity — AI SEO for Shopify stores.
```

## 3. Feature handles / chips (pick all that apply)

`SEO` · `Reporting` · `Rank tracking` · `Analytics` · `AI content` · `Content optimization`
· `Automations` · `Audits` · `SEO score` · `Page indexing` · `robots.txt` · `sitemap`
· `JSON-LD / schema` · `llms.txt` · `Local signals` · `Webhooks` · `APIs`

**Do not check:** backlink building, paid ads, email marketing, reviews management
(we *monitor* review-platform presence; we do not post reviews).

## 4. Long description (rich text — paste as-is)

**Get found when shoppers ask AI.**

Search is turning into conversation. When a shopper asks “best vitamin C serum for
Indian skin under ₹1,000”, ChatGPT, Gemini and Perplexity answer with a shortlist.
AI Visibility gets your Shopify store onto that shortlist — with honest
measurement, not snake-oil guarantees.

**What the app does**

- **AI Readiness Score** — 30+ live checks across crawlability, schema, content
  and brand signals. Get a 0–100 score, a grade, and a fix-it list you can act on.
- **Brand Signals** — checks the third-party trust layer AIs weigh most: ratings
  in structured data, visible reviews, review-platform presence, off-site
  mentions and social profiles.
- **AI Visibility Tracker** — your mention rate per query, per engine (ChatGPT,
  Gemini, Perplexity and more), updated daily, with competitor comparison and a
  weekly email digest of what changed.
- **Instant Indexing (IndexNow)** — product and blog changes are pinged for fast
  freshness so AI crawlers and search engines see updates sooner.
- **Schema Builder** — one-click Organization, Product (₹ prices) and FAQ
  JSON-LD, injected through your theme.
- **Smart Blogger** — generates Indian-English / Hinglish articles from your real
  catalog and publishes them to your Shopify blog in one click — including FAQ
  blocks that answer questions directly.
- **AI Files on your storefront** — llms.txt, agent.md, robots.txt and
  sitemap.xml served automatically on your own domain via App Proxy.
- **AI Traffic → Orders** — which AI platform sent you paying customers
  (orders/paid webhook) and the revenue per channel.
- **Weekly AI Visibility Report** — every Monday, a digest of your mention-rate
  trend, per-engine deltas and citation samples, straight to your inbox.

**Built for Indian D2C**

- Priced in INR, with a free plan to start — no credit card required.
- WhatsApp-first support and Hinglish-friendly guidance.
- Works out of the box with Indian marketplaces, ₹ prices and local shopping
  queries.

**Honest by design**

We measure real signals and never promise a guaranteed “rank #1 in ChatGPT”.
Nobody can — anyone who does is selling you something.

## 5. Pricing tiers (set in Partner dashboard → Plans, mirrored by the app)

| Plan | ₹ monthly | ₹ annual (10×) | Headline features |
|---|---|---|---|
| Free | ₹0 | ₹0 | AI Readiness Score + action plan, 25 tracked queries/mo, 1 competitor, AI SEO guides |
| Grow | ₹999 | ₹9,990 | Everything in Free + 300 queries/mo, 5 competitors, llms.txt/robots/sitemap automation, Schema Builder, IndexNow, AI traffic attribution, weekly report email |
| Scale | ₹1,999 | ₹19,990 | Everything in Grow + 2,000 queries/mo, 10 competitors, Smart Blogger + publish, AI sentiment, FAQ JSON-LD, priority WhatsApp |
| Agency | ₹4,999 | ₹49,990 | Everything in Scale + 10,000 queries/mo, 100 competitors, multi-store dashboard, white-label client reports |

Trial: 3 days on paid plans (repo default; confirm the Partner billing screen
trial setting matches). Annual = 10× monthly (the app's "save ~17%" is vs a
12-month sum; keep the listing's own wording consistent with what the plan
screen says).

## 6. Screenshot shot list (capture real ones — drafts in `docs/app-store/listing/`)

All: 1280×800 PNG (min 640×400), no other stores' data, no emojis, no browser
chrome, no email addresses.

| # | Filename draft → real | Screen | Must show |
|---|---|---|---|
| 1 | `1-dashboard-overview.png` | Dashboard | AI Readiness Score card, engine mention cards, 7-day trend chart |
| 2 | `2-brand-signals.png` | Brand Signals | Score + per-check Found/Missing cards with fix hints |
| 3 | `3-visibility-tracker.png` | AI Visibility Tracker | Query table with per-engine % pills, add-query button |
| 4 | `4-schema-builder.png` | Schema Builder | Install toggles + JSON-LD preview |
| 5 | `5-smart-blogger.png` | Smart Blogger | Article brief on the left, generated article + Publish button |
| 6 | `6-indexing-llms.png` | Instant Indexing & AI Files | IndexNow submissions table + llms.txt/agent.md file cards |

Optional extras: Weekly email digest (inbox mock), Agency "My Clients" list,
scorecard share link card. Max 20 total.

**Demo video (optional, ≤ 4 min):** install on a dev store → run a score →
install schema → generate + publish an article → open llms.txt on the store
domain. No audio required; captions preferred.

## 7. Data-usage answers (listing questionnaire — paste trimmed)

> We collect: (1) the store domain and a server-side Shopify API token, needed
> to run the app; (2) the email a merchant optionally enters on our public AI
> readiness scorecard. Order webhooks are used only to attribute revenue to AI
> channels (order id, amount, channel — no customer personal data kept). We do
> not sell or share data. Uninstalling revokes the token and deletes store data;
> GDPR `customers/data_request`, `customers/redact` and `shop/redact` webhooks
> are implemented and acknowledged.

- Privacy policy URL → `https://<your-app-domain>/privacy` (public, no login).
- Terms of service URL → `https://<your-app-domain>/terms`.
- Support email → a monitored inbox; the app's in-UI support is a WhatsApp
  deep-link you configure.

## 8. Reviewer-proofing notes (do not skip)

- Screenshots must come from **your real app** — replace the drafts. Never show
  a competitor's or fictional store's private data.
- Demo login creds are shown only outside production; reviewers get a fresh
  dev-store install link, not a password.
- Keep pricing text identical between the Partner listing and the app's
  Plans & Billing tab (₹ + INR).
- No logos or trademarks of OpenAI/Google/Anthropic inside app assets.
- Copy does not claim guaranteed rankings, “#1 in ChatGPT”, or Google-approval.
- App must work embedded with **no console errors on a fresh dev-store install**
  before you hit submit.
