<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Self-service registration
    |--------------------------------------------------------------------------
    |
    | When disabled, the registration routes redirect to the login page.
    |
    */

    'registration_enabled' => (bool) env('REGISTRATION_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Contact form recipient
    |--------------------------------------------------------------------------
    |
    | Address that receives public contact form submissions.
    |
    */

    'contact_email' => env('CONTACT_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    |
    | Self-hosted, cookieless Umami. The tracker only renders when both values
    | are set, so local and CI environments stay untracked with no extra
    | configuration. The privacy policy describes this collection — keep the
    | two in step if the provider ever changes.
    |
    */

    'analytics' => [
        'umami_src' => env('UMAMI_SRC'),
        'umami_website_id' => env('UMAMI_WEBSITE_ID'),
    ],

];
