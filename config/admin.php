<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SaaS-owner (super admin) area credentials
    |--------------------------------------------------------------------------
    |
    | HTTP Basic credentials guarding /admin in production. When both are left
    | empty in a non-production environment the admin area stays open so the
    | panel can be previewed in demo / local sandboxes.
    */

    'email' => env('ADMIN_EMAIL', ''),

    'password' => env('ADMIN_PASSWORD', ''),

];
