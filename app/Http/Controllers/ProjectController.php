<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Project;

class ProjectController extends Controller
{
    public function index(){
        $projects = Auth::user()->projects;
        return view('projects.index', ['projects' => $projects]);
    }

    public function create(){
        return view('projects.form');
    }

    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'author' => 'nullable|string|max:255',
            'institution' => 'nullable|string|max:255',
            'author_email' => 'nullable|email|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        $project = Auth::user()->projects()->create([
            'name' => $request->name,
            'author' => $request->author,
            'institution' => $request->institution,
            'author_email' => $request->author_email,
            'country' => $request->country,
            'finished' => false,
            'published' => false,
            'shared' => false,
        ]);

        return redirect()->route('projects.index')->with('success', 'Se ha creado el proyecto exitosamente.');
    }

    public function edit(Project $project){
        if(!Auth::user()->projects->contains($project)){
            return redirect()->route('projects.index')->with('error', 'No tienes permiso para editar este proyecto.');
        }

        return view('projects.form', ['project' => $project]);
    }

    public function update(Request $request, Project $project){
        if(!Auth::user()->projects->contains($project)){
            return redirect()->route('projects.index')->with('error', 'No tienes permiso para editar este proyecto.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'author' => 'nullable|string|max:255',
            'institution' => 'nullable|string|max:255',
            'author_email' => 'nullable|email|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        $project->update([
            'name' => $request->name,
            'author' => $request->author,
            'institution' => $request->institution,
            'author_email' => $request->author_email,
            'country' => $request->country,
        ]);

        return redirect()->route('projects.index')->with('success', 'Se ha actualizado el proyecto exitosamente.');
    }
}
