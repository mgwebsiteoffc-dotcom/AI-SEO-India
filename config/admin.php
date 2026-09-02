<?php

return [
    // Super-admin (SaaS owner) account — seeded by AdminSeeder / demo:seed.
    // Always override the password via ADMIN_PASSWORD in production.
    'email' => env('ADMIN_EMAIL', 'owner@aivisibility.app'),
    'password' => env('ADMIN_PASSWORD', 'aivisibility-demo'),
    'name' => env('ADMIN_NAME', 'SaaS Owner'),

    // Set true to never echo demo credentials on the login page even in
    // local/demo environments (checked at runtime, not in this file).
    'hide_demo_hint' => env('ADMIN_HIDE_DEMO_HINT', false),
];
