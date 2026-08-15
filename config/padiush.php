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

    /*
    |--------------------------------------------------------------------------
    | Public marketing site
    |--------------------------------------------------------------------------
    |
    | The landing, about, contact, privacy and terms pages describe a specific
    | deployment — its operator, its data controller, its contact address. They
    | are off by default so that a fresh installation serves the application
    | only, and never someone else's legal claims. Turn this on once you have
    | written your own; see docs/deployment/public-site.md.
    |
    */

    'public_site_enabled' => (bool) env('PUBLIC_SITE_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Legal documents
    |--------------------------------------------------------------------------
    |
    | Where this deployment's privacy policy and terms live, relative to the
    | public directory. The repository ships none — they name a specific data
    | controller, so publishing ours would have every installation making our
    | claims as its own. The routes answer 404 until a document exists here.
    |
    */

    'legal_documents_path' => env('LEGAL_DOCUMENTS_PATH', 'locales/legal'),

    /*
    |--------------------------------------------------------------------------
    | Source code location
    |--------------------------------------------------------------------------
    |
    | Padiush is licensed under the AGPL. Section 13 requires that people using
    | it over a network can obtain the source of the version they are using, so
    | the application links here from every signed-in page. If you modify
    | Padiush and run it as a service, this MUST point at your own source.
    |
    */

    'source_url' => env('PADIUSH_SOURCE_URL', 'https://github.com/padiush/platform'),

];
