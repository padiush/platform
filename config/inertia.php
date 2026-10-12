<?php

/*
 * Only what differs from Inertia's defaults; everything else comes from the
 * package's own config/inertia.php, merged key by key.
 */
return [

    /*
     * Where the page components live. Inertia 3 looks in `resources/js/pages`
     * by default; this application's directory is capitalised, which matters
     * on a case-sensitive filesystem. Page tests check that every component a
     * response names exists here.
     */
    'pages' => [
        'ensure_pages_exist' => false,
        'paths' => [resource_path('js/Pages')],
        'extensions' => ['js', 'jsx'],
    ],

    /*
     * The interface renders in the browser only; there is no SSR entry point.
     * Inertia 3 enables SSR by default and, whenever Vite runs hot, posts every
     * page to the dev server to render it there, which costs a round trip on
     * each request, or a timeout where the app cannot reach that address.
     */
    'ssr' => [
        'enabled' => (bool) env('INERTIA_SSR_ENABLED', false),
    ],

];
