<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "What's new": the notes of each release, as a dialog after an update and as
 * a page to come back to. The notes themselves are translations in
 * public/locales/whatsnew; the server only knows which release is running and
 * which one each user last saw (docs/releasing.md).
 */
class WhatsNewController extends Controller
{
    /** Every release's notes. Reading them here counts as having seen them. */
    public function index(Request $request): Response
    {
        if ($request->user()->hasUnseenRelease()) {
            $request->user()->markReleaseSeen();
        }

        return Inertia::render('WhatsNew/Index');
    }

    /** The user has read, or dismissed, the notes of the running release. */
    public function seen(Request $request): RedirectResponse
    {
        $request->user()->markReleaseSeen();

        return back();
    }
}
