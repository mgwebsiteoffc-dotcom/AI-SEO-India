<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', [
        'title' => 'Privacy Policy',
        'description' => 'How AI Visibility collects, uses and protects data for Indian D2C brands on Shopify.',
    ])
</head>
<body class="marketing">
    @include('marketing.partials.header')

    <section class="hero-bg relative overflow-hidden">
        <div class="grid-pattern absolute inset-0"></div>
        <div class="relative max-w-3xl mx-auto px-4 pt-16 pb-8">
            <div class="pill">Legal</div>
            <h1 class="font-display mt-5 text-4xl md:text-5xl font-extrabold">Privacy Policy</h1>
            <p class="text-slate-400 mt-3 text-sm">Last updated: {{ date('d M Y') }}</p>
        </div>
    </section>

    <section class="pb-20">
        <div class="max-w-3xl mx-auto px-4 prose-dark">
            <h2>Who we are</h2>
            <p>AI Visibility ("we", "our") provides an AI SEO analytics and content app for Shopify merchants ("the Service"). This policy explains what data we process when you use the Service or our marketing website.</p>

            <h2>Data we collect</h2>
            <ul>
                <li><b>Shopify store data</b> — when you install the app we receive your shop domain, and (with your permission via OAuth) product, order and analytics data needed to power the Service: audit checks, visibility tracking, AI-attribution reports and content generation.</li>
                <li><b>Account &amp; billing</b> — plan, subscription status and charge identifiers from Shopify billing. We never see your card details; payments are processed by Shopify.</li>
                <li><b>Lead data</b> — email, brand name and store URL when you request a free AI Readiness Score on our website.</li>
                <li><b>Usage data</b> — anonymised request logs (IP, user agent, pages visited) to keep the Service reliable and secure.</li>
            </ul>

            <h2>How we use data</h2>
            <ul>
                <li>To run the audit, tracker and attribution features you explicitly activate.</li>
                <li>To generate and publish content to your own Shopify blog when you click "Publish".</li>
                <li>To send your scorecard email (once) and occasional product updates (unsubscribe anytime).</li>
            </ul>

            <h2>AI processing</h2>
            <p>When you add an OpenAI or Gemini API key, tracked queries and brand details are sent to that provider solely to answer the visibility question for your store. We do not train models on your data, and no third-party AI provider receives your customer data without your explicit action.</p>

            <h2>Data retention &amp; deletion</h2>
            <p>Store data is kept while the app is installed. If the app is uninstalled, we revoke access tokens and delete store records within 30 days. Lead emails are kept until you ask us to delete them — just reply to any email with "delete my data".</p>

            <h2>Data sharing</h2>
            <p>We never sell personal data. We share data only with (a) Shopify, to operate the app, (b) AI providers you have explicitly connected, and (c) processors (hosting, email) bound by GDPR-equivalent terms.</p>

            <h2>Security</h2>
            <p>All traffic is HTTPS. Tokens are encrypted at rest. Access to production systems is restricted and logged.</p>

            <h2>Your rights</h2>
            <p>You can request access, correction or deletion of your data at any time. Contact us on WhatsApp (English/Hinglish) or by replying to any of our emails.</p>

            <h2>Changes</h2>
            <p>We may update this policy; material changes will be announced via the app or email. Continued use of the Service means you accept the updated policy.</p>

            <p class="!mt-10 !text-slate-500">Questions? WhatsApp 91 98765 43210 — we reply in English &amp; Hinglish, usually within a few hours.</p>
        </div>
    </section>

    @include('marketing.partials.footer')
</body>
</html>
