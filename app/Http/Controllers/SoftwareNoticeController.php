<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * The offer of source required by section 13 of the AGPL.
 *
 * Anyone interacting with Padiush over a network is entitled to the source of
 * the version they are using. This page is that offer, so it is reachable
 * without signing in and without the public marketing pages being enabled — a
 * deployment that publishes nothing else still publishes this.
 */
class SoftwareNoticeController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('SoftwareNotice', [
            'sourceUrl' => config('padiush.source_url'),
            'appName' => config('app.name'),
        ]);
    }
}
