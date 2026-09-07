<?php

return [

    'mail' => [
        'default' => env('MAIL_MAILER', 'log'),
        'mailers' => [
            'log' => ['transport' => 'log'],
        ],
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_MODEL', 'nvidia/nemotron-3.5-lightning:free'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
    ],

    'ga4' => [
        'property_id' => env('GA4_PROPERTY_ID'),
        'client_email' => env('GA4_CLIENT_EMAIL'),
        'private_key' => env('GA4_PRIVATE_KEY'),
        'service_account' => env('GA4_SERVICE_ACCOUNT'),
        'source_regex' => env('GA4_SOURCE_REGEX', 'chatgpt|openai|perplexity|gemini|bard|grok|claude|deepseek|copilot'),
    ],
];
