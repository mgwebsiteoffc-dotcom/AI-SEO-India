<!DOCTYPE html>
<html lang="en">
<head>
    @include('marketing.partials.head', [
        'title' => 'Terms of Service',
        'description' => 'Terms of Service for the AI Visibility Shopify app — AI SEO for Indian D2C brands.',
    ])
</head>
<body class="marketing">
    @include('marketing.partials.header')

    <section class="hero-bg relative overflow-hidden">
        <div class="grid-pattern absolute inset-0"></div>
        <div class="relative max-w-3xl mx-auto px-4 pt-16 pb-8">
            <div class="pill">Legal</div>
            <h1 class="font-display mt-5 text-4xl md:text-5xl font-extrabold">Terms of Service</h1>
            <p class="text-slate-400 mt-3 text-sm">Last updated: {{ date('d M Y') }}</p>
        </div>
    </section>

    <section class="pb-20">
        <div class="max-w-3xl mx-auto px-4 prose-dark">
            <h2>1. The Service</h2>
            <p>AI Visibility is a Shopify app that measures and improves a store's visibility in AI assistants (ChatGPT, Gemini, Perplexity and others) through audits, tracking, structured data, llms.txt and content generation.</p>

            <h2>2. Honest results — no guarantees</h2>
            <p>AI ranking depends on retrieval systems we do not control. We provide measurement and tools to improve real signals; we do <b>not</b> guarantee any specific position, mention rate, traffic or revenue. Our dashboards say so on purpose. By subscribing you accept that outcomes vary and that refunds are governed by Shopify's billing terms.</p>

            <h2>3. Your responsibilities</h2>
            <ul>
                <li>You own or have rights to the store and content you connect.</li>
                <li>You will not use the Service to spam, scrape at abusive volumes, or process data you lack rights to.</li>
                <li>Content published to your blog via Smart Blogger is your responsibility to review before publication.</li>
            </ul>

            <h2>4. Billing</h2>
            <p>Plans are billed by Shopify in INR (monthly or annual). The 3-day trial is free; you are charged only after it ends. Cancel anytime from Shopify Billing — access continues until the end of the paid period. Refunds follow Shopify's App Store refund policy.</p>

            <h2>5. AI provider keys</h2>
            <p>Features such as LLM-mode tracking, AI sentiment and AI-written articles require your own OpenAI/Gemini API key. You are responsible for usage and costs of those keys. Without a key, the Service operates in its free template/retrieval modes.</p>

            <h2>6. Availability &amp; liability</h2>
            <p>The Service is provided "as is" without warranties of any kind. To the maximum extent permitted by law, our total liability is limited to the amount you paid us in the 12 months before the claim. We are not liable for indirect or consequential losses, including lost revenue from search or AI platforms.</p>

            <h2>7. Termination</h2>
            <p>You may uninstall at any time. We may suspend accounts that violate these terms or abuse the Service. On uninstall, data is deleted per our Privacy Policy.</p>

            <h2>8. Governing law</h2>
            <p>These terms are governed by the laws of India, with courts in Bengaluru, Karnataka having exclusive jurisdiction.</p>

            <h2>9. Contact</h2>
            <p>WhatsApp 91 98765 43210 (English &amp; Hinglish) or reply to any of our emails.</p>
        </div>
    </section>

    @include('marketing.partials.footer')
</body>
</html>
