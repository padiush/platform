<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\InterviewFormController;

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
    Route::get('/project/{project}/accesses/invites', 'projectInvites')->middleware(['auth'])->name('projects.accesses.invites');
    Route::delete('/project/{project}/accesses/invites/{invite}/revoke', 'revokeInvite')->middleware(['auth'])->name('projects.accesses.invites.revoke');

    Route::get('/projects/accept/{invite}', 'acceptInvite')->middleware(['auth'])->name('projects.invites.accept');
    Route::get('/projects/decline/{invite}', 'declineInvite')->middleware(['auth'])->name('projects.invites.decline');
});

Route::controller(InterviewFormController::class)->group(function(){
    Route::get('/designer', 'index')->middleware(['auth'])->name('designer.index');
    Route::get('/designer/{project}/create', 'create')->middleware(['auth'])->name('designer.create');
    Route::post('/designer/{project}/create', 'store')->middleware(['auth']);

    Route::get('/designer/{project}/form/{form}', 'edit')->middleware(['auth'])->name('designer.form.edit');
    Route::delete('/designer/{project}/form/{form}/delete', 'destroy')->middleware(['auth'])->name('designer.form.delete');
    Route::get('/designer/{project}/form/{form}/toggle', 'toggle')->middleware(['auth'])->name('designer.form.toggle');
});

require __DIR__.'/auth.php';
