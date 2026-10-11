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
    | Public pages and the public site
    |--------------------------------------------------------------------------
    |
    | The contact, privacy and terms pages describe a specific deployment — its
    | operator, its data controller, its contact address. They are off by
    | default so that a fresh installation serves the application only, and
    | never someone else's legal claims. Turn them on once you have written
    | your own; see docs/deployment/public-site.md.
    |
    | A landing page and an about page are not part of the application. If the
    | deployment has a site of its own, PUBLIC_SITE_URL is its address, and the
    | public and sign-in pages link back to it; left unset, they do not.
    |
    */

    'public_site_enabled' => (bool) env('PUBLIC_SITE_ENABLED', false),

    'public_site_url' => rtrim((string) env('PUBLIC_SITE_URL', ''), '/') ?: null,

    /*
    |--------------------------------------------------------------------------
    | Field app store listings
    |--------------------------------------------------------------------------
    |
    | A store build of the companion is tied to the instance it was built for,
    | so only the deployment that publishes one links to it. The public pages'
    | footer shows a badge for each listing set here, and nothing when neither
    | is. COMPANION_PLAY_STORE_COMING_SOON announces a Google Play release
    | that is not public yet, in plain text: Google's badge may only point at
    | a live listing.
    |
    */

    'companion_stores' => [
        'app_store' => env('COMPANION_APP_STORE_URL') ?: null,
        'play_store' => env('COMPANION_PLAY_STORE_URL') ?: null,
        'play_store_coming_soon' => (bool) env('COMPANION_PLAY_STORE_COMING_SOON', false),
    ],

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
