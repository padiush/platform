<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ActiveProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Switch the sidebar to another project, keeping the user in the section they
 * were in — from one project's field records to the other's — when that
 * project offers it to them, and on its overview when it does not.
 */
class ActiveProjectController extends Controller
{
    public function store(Request $request, Project $project, ActiveProject $active): RedirectResponse
    {
        $request->validate([
            'section' => ['nullable', Rule::in(ActiveProject::SECTIONS)],
        ]);

        $user = $request->user();

        if (! $active->accesses($user)->has($project->id)) {
            abort(403);
        }

        $active->remember($user, $project);

        $sections = $active->sections($user, $project);

        return redirect()->to(
            $sections[$request->input('section')] ?? $sections['overview']
        );
    }
}
