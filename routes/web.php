<?php

use App\Http\Controllers\InterviewDataController;
use App\Http\Controllers\InterviewDesignerController;
use App\Http\Controllers\InterviewFormController;
use App\Http\Controllers\InterviewInstancesController;
use App\Http\Controllers\ProjectCatalogController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::middleware('system_admin')
        ->prefix('system')
        ->name('system.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\SystemController::class, 'index'])
                ->name('index');
            // Bulk delete must be defined before single delete to avoid
            // "bulk-delete" being treated as a {user} parameter.
            Route::delete('/users/bulk-delete', [\App\Http\Controllers\SystemController::class, 'destroyUsers'])
                ->name('users.bulk-delete');
            Route::delete('/users/{user}', [\App\Http\Controllers\SystemController::class, 'destroyUser'])
                ->name('users.delete');
        });

    Route::controller(ProjectController::class)
        ->prefix('projects')
        ->name('projects.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/create', 'store');
            Route::get('/{project}/edit', 'edit')->name('edit');
            Route::post('/{project}/edit', 'update');
            Route::delete('/{project}/delete', 'destroy')->name('delete');

            Route::get('/{project}/accesses', 'manageAccess')->name('accesses');
            Route::delete(
                '/{project}/accesses/{user}/revoke',
                'revokeAccess'
            )->name('accesses.revoke');
            Route::post('/{project}/accesses/invite', 'inviteUser')->name(
                'accesses.invite'
            );
            Route::get('/{project}/accesses/invites', 'projectInvites')->name(
                'accesses.invites'
            );
            Route::delete(
                '/{project}/accesses/invites/{invite}/revoke',
                'revokeInvite'
            )->name('accesses.invites.revoke');

            Route::get('/accept/{invite}', 'acceptInvite')->name(
                'invites.accept'
            );
            Route::get('/decline/{invite}', 'declineInvite')->name(
                'invites.decline'
            );
        });

    Route::prefix('designer')
        ->name('designer.')
        ->group(function () {
            Route::controller(InterviewFormController::class)->group(
                function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/{project}/create', 'create')->name('create');
                    Route::post('/{project}/create', 'store');
                    Route::get('/{project}/form/{form}', 'edit')->name(
                        'form.edit'
                    );
                    Route::put('/{project}/form/{form}', 'update')->name(
                        'form.update'
                    );
                    Route::delete(
                        '/{project}/form/{form}/delete',
                        'destroy'
                    )->name('form.delete');
                    Route::put('/{project}/form/{form}/toggle', 'toggle')->name(
                        'form.toggle'
                    );
                }
            );

            Route::controller(InterviewDesignerController::class)->group(
                function () {
                    Route::get(
                        '/{project}/form/{form}/wizard',
                        'designer'
                    )->name('form.wizard');
                    Route::get(
                        '/{project}/form/{form}/preview',
                        'preview'
                    )->name('form.preview');
                    Route::get(
                        '/{project}/form/{form}/sections',
                        'fetchSections'
                    )->name('form.sections');
                    Route::post(
                        '/{project}/form/{form}/section/create',
                        'createSection'
                    )->name('form.sections.create');
                    Route::put(
                        '/{project}/form/{form}/section/{section}/rename',
                        'renameSection'
                    )->name('form.sections.rename');
                    Route::put(
                        '/{project}/form/{form}/section/{section}/reorder',
                        'reorderSection'
                    )->name('form.sections.reorder');
                    Route::put(
                        '/projects/{project}/designer/{form}/section/{section}/repeatable',
                        'toggleRepeatable'
                    )->name('form.sections.repeatable');
                    Route::delete(
                        '/{project}/form/{form}/sections/{section}/delete',
                        'deleteSection'
                    )->name('form.sections.delete');
                    Route::get(
                        '/{project}/form/{form}/section/{section}/items',
                        'fetchItems'
                    )->name('form.section.items');
                    Route::post(
                        '/{project}/form/{form}/section/{section}/items/create',
                        'createItem'
                    )->name('form.section.items.create');
                    Route::put(
                        '/{project}/form/{form}/section/{section}/items/{item}',
                        'updateItem'
                    )->name('form.section.items.update');
                    Route::delete(
                        '/{project}/form/{form}/section/{section}/items/{item}/delete',
                        'deleteItem'
                    )->name('form.section.items.delete');
                    Route::put(
                        '/{project}/form/{form}/section/{section}/items/{item}/reorder',
                        'reorderItem'
                    )->name('form.section.items.reorder');
                }
            );
        });

    Route::controller(InterviewInstancesController::class)
        ->prefix('interviews')
        ->name('interviews.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{form}/create', 'create')->name('create');
            Route::get('/{form}/instances', 'list')->name('instances');
            Route::get('/instance/{instance}', 'show')->name('show');
            Route::post('/instance/{instance}/save', 'saveAnswer')->name(
                'save_answer'
            );
            Route::delete(
                '/instance/{instance}/sections/{section}',
                'destroyRepeatableSet'
            )->name('section.remove');

            Route::delete('/instance/{instance}/delete', 'destroy')->name(
                'destroy'
            );
        });

    Route::controller(ProjectCatalogController::class)->group(function () {
        Route::get('/catalogs', 'index')->name('catalogs.index');
        Route::get('/catalogs/{project}', 'show')->name('catalogs.show');

        Route::get(
            '/catalogs/{project}/species/register',
            'registerSpecies'
        )->name('catalogs.species.register');
        Route::post('/catalogs/{project}/species/register', 'storeSpecies');

        Route::get(
            '/catalogs/{project}/species/{species}',
            'showSpecies'
        )->name('catalogs.species.show');

        Route::delete(
            '/catalogs/{project}/species/{species}/delete',
            'destroySpecies'
        )->name('catalogs.species.delete');
    });

    Route::controller(InterviewDataController::class)->group(function () {
        Route::get('/data', 'index')->name('data.index');
        Route::get('/data/link/{project}', 'linkSpecies')->name('data.link');
        Route::post('/data/link/{project}/handle', 'handleLinkRequest')->name(
            'data.link.handle'
        );

        Route::get('/data/{project}/ethnobotanyR', 'prepareEthnobotanyR')->name(
            'data.ethnobotanyR'
        );
        Route::post(
            '/data/{project}/ethnobotanyR',
            'handleEthnobotanyRRequest'
        );
        Route::get('/data/{project}/custom', 'prepareCustom')->name(
            'data.custom'
        );
        Route::post('/data/{project}/custom', 'handleCustomRequest');
    });
});

Route::controller(PublicPageController::class)->group(function () {
    Route::get('/', 'index')->name('public.index');
    Route::get('/acerca', 'about')->name('public.about');
    Route::get('/contacto', 'contact')->name('public.contact');
    Route::post('/contacto', 'handleContactRequest')
        ->middleware(['honeypot'])
        ->name('public.contact.handle');
    Route::get('/privacidad', 'privacy')->name('public.privacy');
    Route::get('/terminos', 'terms')->name('public.terms');
});

require __DIR__.'/auth.php';
