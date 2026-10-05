<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The guided tours: which ones each user has been through. The tours
 * themselves — their steps and words — live in the frontend and the
 * translation files; the server only remembers who has seen what, so a tour
 * starts once per person whatever browser they use.
 */
class TourController extends Controller
{
    /** A tour was finished, or skipped: either way it does not start again. */
    public function complete(Request $request, string $tour): RedirectResponse
    {
        abort_unless(in_array($tour, User::TOURS, true), 404);

        $request->user()->completeTour($tour);

        return back();
    }

    /** Offer every tour again, from Mi cuenta. */
    public function reset(Request $request): RedirectResponse
    {
        $request->user()->resetTours();

        return back()->with([
            'message' => 'account.tours.reset_done',
            'message_type' => 'success',
        ]);
    }
}
