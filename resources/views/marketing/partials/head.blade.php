{{-- Shared <head> for the marketing site — premium meta + OG + fonts --}}
@props(['title' => '', 'description' => '', 'ogImage' => ''])
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title ? $title.' — AI Visibility' : 'AI Visibility — AI SEO for Indian D2C Brands' }}</title>
<meta name="description" content="{{ $description }}">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="canonical" href="{{ url()->current() }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="AI Visibility">
<meta property="og:title" content="{{ $title ? $title.' — AI Visibility' : 'AI Visibility — AI SEO for Indian D2C Brands' }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:image" content="{{ $ogImage ?: url('/og-image.svg') }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title ? $title.' — AI Visibility' : 'AI Visibility' }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="theme-color" content="#070d1a">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
@vite(['resources/css/app.css'])
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'AI Visibility',
    'url' => url('/'),
    'logo' => url('/favicon.svg'),
    'sameAs' => [],
    'areaServed' => 'IN',
], JSON_UNESCAPED_SLASHES) !!}</script>
