<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

use App\Imports\CatalogSpeciesImport;

use App\Models\ProjectAccess;
use App\Models\Project;
use App\Models\User;
use App\Models\CatalogSpecies;
use App\Models\CatalogSpeciesPhoto;

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

    public function uploadCatalog(Project $project){
        if(!Auth::user()->hasCapabilityOnProject($project, 'edit_catalog')){
            return redirect()->route('catalogs.index')->with('error', 'No tienes permisos para agregar especies a este catálogo.');
        }

        return view('catalogs.upload', compact('project'));
    }

    public function handleUploadRequest(Project $project, Request $request){
        if(!Auth::user()->hasCapabilityOnProject($project, 'edit_catalog')){
            return redirect()->route('catalogs.index')->with('error', 'No tienes permisos para agregar especies a este catálogo.');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx',
        ]);

        $file = $request->file('file');

        Excel::import(new CatalogSpeciesImport($project), $file);

        return redirect()->route('catalogs.index')->with('success', 'El catálogo ha sido actualizado.');
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

    public function addPhoto(Request $request, CatalogSpecies $species){
        if(!Auth::user()->hasCapabilityOnProject($project, 'view_catalog')){
            return redirect()->route('catalogs.index')->with('error', 'No tienes permisos para ver este catálogo.');
        }

        $this->validate($request, [
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
            'caption' => 'nullable|string',
            'author' => 'nullable|string',
            'license' => 'nullable|string',
            'license_url' => 'nullable|string',
        ]);

        $user = Auth::user();

        $photo = $request->file('photo');
        $name = $species->id . '-' . $user->id . '-' . time() . '.' . $photo->getClientOriginalExtension();
        $path = public_path('storage/images/species/' . $name);
        $photo->move(public_path('storage/images/species/'), $name);

        CatalogSpeciesPhoto::create([
            'catalog_species_id' => $species->id,
            'path' => $name,
            'caption' => $request->caption,
            'author' => $request->author,
            'license' => $request->license,
            'license_url' => $request->license_url,
        ]);

        return redirect()->back()->with('success', 'La foto ha sido agregada a la especie.');
    }
}
