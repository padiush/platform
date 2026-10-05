<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * The guided tours: which ones each user has been through. The tours
 * themselves — their steps and words — live in the frontend and the
 * translation files; the server only remembers who has seen what, so a tour
 * starts once per person whatever browser they use.
 */
class TourController extends Controller
{
    /**
     * Tours finished, or skipped: either way they do not start again. More
     * than one when a tour folded another in, as the welcome does the
     * project steps.
     *
     * Sent in the background rather than as a page visit, which the next
     * click would cancel, so there is nothing to redirect to.
     */
    public function complete(Request $request): Response
    {
        $tours = $request->validate([
            'tours' => ['required', 'array', 'min:1'],
            'tours.*' => ['string', Rule::in(User::TOURS)],
        ])['tours'];

        foreach ($tours as $tour) {
            $request->user()->completeTour($tour);
        }

        return response()->noContent();
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
