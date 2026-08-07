<?php

/**
 * @see https://github.com/artesaos/seotools
 */

/*
 * Absolute URL to the link-preview card, shared by the OpenGraph and Twitter
 * generators. APP_URL therefore has to be the real public origin in any
 * deployed environment, or previews point at a host nobody can reach.
 */
$ogImage = rtrim(env('APP_URL', 'http://localhost'), '/').'/images/site/og-card.png';

return [
    'meta' => [
        /*
         * The default configurations to be used by the meta generator.
         */
        'defaults' => [
            'title' => 'Padiush', // set false to total remove
            'titleBefore' => false, // Put defaults.title before page title, like 'It's Over 9000! - Dashboard'
            'description' => 'Software para investigaciones etnobotánicas', // set false to total remove
            'separator' => ' | ',
            'keywords' => [],
            'canonical' => false, // Set to null or 'full' to use Url::full(), set to 'current' to use Url::current(), set false to total remove
            'robots' => false, // Set to 'all', 'none' or any combination of index/noindex and follow/nofollow
        ],
        /*
         * Webmaster tags are always added.
         */
        'webmaster_tags' => [
            'google' => null,
            'bing' => null,
            'alexa' => null,
            'pinterest' => null,
            'yandex' => null,
            'norton' => null,
        ],

        'add_notranslate_class' => false,
    ],
    'opengraph' => [
        /*
         * The default configurations to be used by the opengraph generator.
         */
        'defaults' => [
            'title' => 'Padiush', // set false to total remove
            'description' => 'Software para investigaciones etnobotánicas', // set false to total remove
            'url' => null, // Set null for using Url::current(), set false to total remove
            'type' => 'website',
            'site_name' => 'Padiush',
            /*
             * The link-preview card. It must be an absolute URL to a static
             * file: crawlers fetch it long after reading the page and cache the
             * result, so a presigned or expiring URL previews as nothing. PNG
             * rather than WebP, which WhatsApp and some LinkedIn paths refuse.
             *
             * Regenerate with `npm run capture:screenshots`.
             */
            'images' => [
                $ogImage,
            ],
        ],
    ],
    'twitter' => [
        /*
         * The default values to be used by the twitter cards generator.
         */
        'defaults' => [
            // Without this X renders a small square thumbnail even when an
            // og:image is present.
            'card' => 'summary_large_image',
            'image' => $ogImage,
        ],
    ],
    'json-ld' => [
        /*
         * The default configurations to be used by the json-ld generator.
         */
        'defaults' => [
            'title' => 'Padiush', // set false to total remove
            'description' => 'Software para investigaciones etnobotánicas', // set false to total remove
            'url' => false, // Set to null or 'full' to use Url::full(), set to 'current' to use Url::current(), set false to total remove
            'type' => 'WebPage',
            'images' => [],
        ],
    ],
];
