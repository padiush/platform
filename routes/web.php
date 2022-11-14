<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\InterviewFormController;
use App\Http\Controllers\InterviewDesignerController;
use App\Http\Controllers\ProjectCatalogController;
use App\Http\Controllers\InterviewInstancesController;

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
    Route::get('/designer/{project}/form/{form}/preview', 'preview')->middleware(['auth'])->name('designer.form.preview');
});

Route::controller(InterviewDesignerController::class)->group(function(){
    Route::post('/designer/{form}/section/create', 'createSection')->middleware(['auth'])->name('designer.section.create');
    Route::post('/designer/{form}/section/items', 'getSectionItems')->middleware(['auth'])->name('designer.section.items');

    Route::post('/designer/{form}/item/create', 'createItem')->middleware(['auth'])->name('designer.item.create');

    Route::post('/designer/{form}/item/data', 'getItem')->middleware(['auth'])->name('designer.item.data');
    Route::post('/designer/{form}/item/update', 'updateItem')->middleware(['auth'])->name('designer.item.update');

    Route::post('/designer/{form}/section/data', 'getSection')->middleware(['auth'])->name('designer.section.data');
    Route::post('/designer/{form}/section/update', 'updateSection')->middleware(['auth'])->name('designer.section.update');
});

Route::controller(ProjectCatalogController::class)->group(function(){
    Route::get('/catalogs', 'index')->middleware(['auth'])->name('catalogs.index');
    Route::get('/catalogs/{project}', 'show')->middleware(['auth'])->name('catalogs.show');

    Route::get('/catalogs/{project}/species/register', 'registerSpecies')->middleware(['auth'])->name('catalogs.species.register');
    Route::post('/catalogs/{project}/species/register', 'storeSpecies')->middleware(['auth']);
});

Route::controller(InterviewInstancesController::class)->group(function(){
    Route::get('/interviews', 'index')->middleware(['auth'])->name('interviews.index');
    Route::get('/interviews/{form}/create', 'create')->middleware(['auth'])->name('interviews.create');

    Route::get('/interviews/{form}/instances', 'list')->middleware(['auth'])->name('interviews.instances');
    Route::get('/interviews/instance/{instance}', 'show')->middleware(['auth'])->name('interviews.show');

    Route::post('/interviews/instance/{instance}/answer/store', 'storeAnswer')->middleware(['auth'])->name('interviews.answer');
    Route::post('/interviews/instance/{instance}/answer/get', 'getAnswer')->middleware(['auth'])->name('interviews.answer.get');
    Route::post('/interviews/instance/{instance}/repeating-section/populate', 'populateRepeatableSection')->middleware(['auth'])->name('interviews.repeating-section.populate');
});

require __DIR__.'/auth.php';
