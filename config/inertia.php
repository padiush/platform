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

];
