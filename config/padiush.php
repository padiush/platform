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

];
