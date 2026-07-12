<?php

namespace App\Http\Controllers;

use App\Models\CatalogSpecies;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProjectCatalogController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        $accesses = Auth::user()
            ->projectAccesses()
            ->with([
                'capability',
                'project' => fn ($query) => $query->withCount('catalogSpecies'),
            ])
            ->get();

        $projects = collect();

        foreach ($accesses as $access) {
            $project = $access->project;

            if (! $project) {
                continue;
            }

            if ($access->capability->view_catalog) {
                $projects->push([
                    'id' => $project->id,
                    'name' => $project->name,
                    'catalog_species_count' => $project->catalog_species_count,
                    'linked_species_count' => $project
                        ->linkedSpecies()
                        ->count(),
                    'linked_families_count' => $project
                        ->linkedFamilies()
                        ->count(),
                    'can_edit_catalog' => (bool) $access->capability->edit_catalog,
                    'can_view_catalog' => true, // already verified
                ]);
            }
        }

        return Inertia::render('Catalog/Index', [
            'projects' => $projects,
        ]);
    }

    public function registerSpecies(Project $project): Response|RedirectResponse
    {
        if (! Auth::user()->can('editCatalog', $project)) {
            return redirect()
                ->route('catalogs.index')
                ->with('message', 'catalogs.no_access')
                ->with('message_type', 'error');
        }

        return Inertia::render('Catalog/Form', [
            'project' => $project,
        ]);
    }

    public function storeSpecies(
        Request $request,
        Project $project
    ): RedirectResponse {
        $request->validate([
            'family' => 'nullable|string',
            'genus' => 'required|string',
            'name' => 'required|string',
            'authority' => 'nullable|string',
        ]);

        if (! Auth::user()->can('editCatalog', $project)) {
            return redirect()
                ->route('catalogs.index')
                ->with('message', 'catalogs.no_access')
                ->with('message_type', 'error');
        }

        CatalogSpecies::create([
            'project_id' => $project->id,
            'family' => $request->family,
            'genus' => $request->genus,
            'name' => $request->name,
            'authority' => $request->authority,
        ]);

        return redirect()
            ->route('catalogs.index')
            ->with('message', 'catalogs.species_registered')
            ->with('message_type', 'success');
    }

    public function show(Project $project): Response|RedirectResponse
    {
        if (! Auth::user()->can('viewCatalog', $project)) {
            return redirect()
                ->route('catalogs.index')
                ->with('error', 'No tienes permisos para ver este catálogo.');
        }

        $speciesQuery = CatalogSpecies::withCount('answers')
            ->where('project_id', $project->id)
            ->orderBy('family', 'asc')
            ->orderBy('genus', 'asc')
            ->orderBy('name', 'asc');

        if ($speciesQuery->count() === 0) {
            return redirect()
                ->route('catalogs.index')
                ->with('error', 'Este catálogo no tiene especies registradas.');
        }

        $species = $speciesQuery->paginate(20)->through(
            fn ($sp) => [
                'id' => $sp->id,
                'family' => $sp->family,
                'genus' => $sp->genus,
                'name' => $sp->name,
                'authority' => $sp->authority,
                'answers' => [
                    'length' => $sp->answers_count,
                ],
            ]
        );

        return Inertia::render('Catalog/SpeciesIndex', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'species' => $species,
        ]);
    }

    public function showSpecies(
        Project $project,
        CatalogSpecies $species
    ): Response|RedirectResponse {
        if (! Auth::user()->can('viewCatalog', $project)) {
            return redirect()
                ->route('catalogs.index')
                ->with('error', 'No tienes permisos para ver este catálogo.');
        }

        if ($species->project_id !== $project->id) {
            return redirect()
                ->route('catalogs.index')
                ->with('message', 'catalogs.species_not_found')
                ->with('message_type', 'error');
        }

        return Inertia::render('Catalog/SpeciesShow', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'species' => [
                'id' => $species->id,
                'family' => $species->family,
                'genus' => $species->genus,
                'name' => $species->name,
                'authority' => $species->authority,
            ],
        ]);
    }

    public function destroySpecies(
        Project $project,
        CatalogSpecies $species
    ): RedirectResponse {
        if (! Auth::user()->can('editCatalog', $project)) {
            return redirect()
                ->route('catalogs.index')
                ->with('message', 'catalogs.no_access')
                ->with('message_type', 'error');
        }

        if ($species->project_id !== $project->id) {
            return redirect()
                ->route('catalogs.index')
                ->with('message', 'catalogs.species_not_found')
                ->with('message_type', 'error');
        }

        foreach ($species->photos as $photo) {
            $photo->delete();
        }

        foreach ($species->answers as $answer) {
            $answer->catalog_species_id = null;
            $answer->save();
        }

        $species->delete();

        return redirect()
            ->route('catalogs.show', ['project' => $project->id])
            ->with('message', 'catalogs.species_deleted')
            ->with('message_type', 'success');
    }
}
