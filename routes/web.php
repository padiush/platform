<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::controller(ProjectController::class)->group(function(){
    Route::get('/projects', 'index')->middleware(['auth'])->name('projects.index');
    Route::get('/projects/create', 'create')->middleware(['auth'])->name('projects.create');
    Route::post('/projects/create', 'store')->middleware(['auth']);

    Route::get('/projects/{project}/edit', 'edit')->middleware(['auth'])->name('projects.edit');
    Route::post('/projects/{project}/edit', 'update')->middleware(['auth']);

    Route::get('/projects/{project}/accesses', 'manageAccess')->middleware(['auth'])->name('projects.accesses');
    Route::delete('/projects/{project}/accesses/{user}/revoke', 'revokeAccess')->middleware(['auth'])->name('projects.accesses.revoke');
    Route::post('/projects/{project}/accesses/invite', 'inviteUser')->middleware(['auth'])->name('projects.accesses.invite');
});

require __DIR__.'/auth.php';
