<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\ProjectAccess;
use App\Models\Project;
use App\Models\User;
use App\Models\CatalogSpecies;

class ProjectCatalogController extends Controller{
    public function index(){
        $accesses = ProjectAccess::where('user_id', Auth::id())->get();

        $projects = collect();

        foreach($accesses as $access){
            $project = Project::find($access->project_id);

            if(Auth::user()->hasCapabilityOnProject($project, 'view_catalog')){
                $projects->push($project);
            }
        }

        if($projects->count() == 0){
            return redirect()->route('projects.index')->with('error', 'No has participado en ningún proyecto.');
        }

        return view('catalogs.index', compact('projects'));
    }

    public function registerSpecies(Project $project){
        if(!Auth::user()->hasCapabilityOnProject($project, 'edit_catalog')){
            return redirect()->route('catalogs.index')->with('error', 'No tienes permisos para agregar especies a este catálogo.');
        }

        return view('catalogs.register_species', compact('project'));
    }

    public function storeSpecies(Request $request, Project $project){
        $request->validate([
            'family' => 'required|string',
            'genus' => 'required|string',
            'name' => 'required|string',
            'authority' => 'required|string',
        ]);

        if(!Auth::user()->hasCapabilityOnProject($project, 'edit_catalog')){
            return redirect()->route('catalogs.index')->with('error', 'No tienes permisos para agregar especies a este catálogo.');
        }

        $species = CatalogSpecies::create([
            'project_id' => $project->id,
            'family' => $request->family,
            'genus' => $request->genus,
            'name' => $request->name,
            'authority' => $request->authority,
        ]);

        return redirect()->route('catalogs.index')->with('success', 'La especie ha sido registrada en el catálogo.');
    }

    public function show(Project $project){
        if(!Auth::user()->hasCapabilityOnProject($project, 'view_catalog')){
            return redirect()->route('catalogs.index')->with('error', 'No tienes permisos para ver este catálogo.');
        }

        if($project->catalogSpecies->count() == 0){
            return redirect()->route('catalogs.index')->with('error', 'Este catálogo no tiene especies registradas.');
        }

        $species = CatalogSpecies::where('project_id', $project->id)->orderBy('family', 'asc')->orderBy('genus', 'asc')->orderBy('name', 'asc')->paginate(20);

        return view('catalogs.list', compact('project', 'species'));
    }

    public function showSpecies(Project $project, CatalogSpecies $species){
        if(!Auth::user()->hasCapabilityOnProject($project, 'view_catalog')){
            return redirect()->route('catalogs.index')->with('error', 'No tienes permisos para ver este catálogo.');
        }

        return view('catalogs.show', compact('project', 'species'));
    }
}
