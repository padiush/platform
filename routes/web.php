<?php

use App\Http\Controllers\ActiveProjectController;
use App\Http\Controllers\CollectingPermitController;
use App\Http\Controllers\FieldRecordController;
use App\Http\Controllers\FieldRecordMediaController;
use App\Http\Controllers\InterviewDataController;
use App\Http\Controllers\InterviewDesignerController;
use App\Http\Controllers\InterviewFormController;
use App\Http\Controllers\InterviewInstancesController;
use App\Http\Controllers\InterviewMediaController;
use App\Http\Controllers\LegacyCatalogRedirectController;
use App\Http\Controllers\ProjectCatalogController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectOverviewController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\SoftwareNoticeController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\WfoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Signing in lands on the project the user works in; with none yet, on a
    // welcome that points at creating one.
    Route::get('/dashboard', [ProjectOverviewController::class, 'landing'])
        ->name('dashboard');
    Route::get('/projects/{project}', [ProjectOverviewController::class, 'show'])
        ->whereNumber('project')
        ->name('projects.overview');

    // The sidebar's project switcher.
    Route::post('/projects/{project}/activate', [ActiveProjectController::class, 'store'])
        ->name('projects.activate');

    Route::middleware('system_admin')
        ->prefix('system')
        ->name('system.')
        ->group(function () {
            Route::get('/', [SystemController::class, 'index'])
                ->name('index');
            Route::post('/registration-invites', [SystemController::class, 'inviteRegistration'])
                ->name('registration-invites.store');
            // Bulk delete must be defined before single delete to avoid
            // "bulk-delete" being treated as a {user} parameter.
            Route::delete('/users/bulk-delete', [SystemController::class, 'destroyUsers'])
                ->name('users.bulk-delete');
            Route::delete('/users/{user}', [SystemController::class, 'destroyUser'])
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
                    Route::put(
                        '/{project}/form/{form}/structure',
                        'updateStructure'
                    )->name('form.structure.update');
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

    // A project's catalog: the taxa its records and answers are identified
    // as. Route names predate the move under /projects and are kept.
    Route::controller(ProjectCatalogController::class)
        ->prefix('/projects/{project}/catalog')
        ->whereNumber('project')
        ->name('catalogs.')
        ->group(function () {
            Route::get('/', 'show')->name('show');

            Route::get('/species/register', 'registerSpecies')
                ->name('species.register');
            Route::post('/species/register', 'storeSpecies');

            // Prefill registration from a WFO name. Literal paths must precede
            // the {species} route below, or they'd be captured as a species id.
            Route::get('/species/wfo-search', 'searchWfoNames')
                ->name('species.wfo-search');
            Route::post('/species/wfo-resolve', 'resolveWfoName')
                ->name('species.wfo-resolve');

            // iNaturalist reference photo: attribution (JSON) + a same-origin,
            // never-stored image proxy. Literal paths, before the {species} route.
            Route::get('/species/inaturalist', 'inaturalistInfo')
                ->name('species.inaturalist');
            Route::get('/species/inaturalist-photo', 'inaturalistPhoto')
                ->name('species.inaturalist-photo');

            Route::get('/species/{species}', 'showSpecies')
                ->name('species.show');

            // Fetch (and cache) the species' geographic range from WCVP via GBIF.
            Route::post('/species/{species}/distribution', 'fetchDistribution')
                ->name('species.distribution');

            // Preview the taxonomy a WFO name would apply, then adopt it.
            Route::post('/species/{species}/wfo-preview', 'previewWfoName')
                ->name('species.wfo-preview');
            Route::patch('/species/{species}', 'updateSpecies')
                ->name('species.update');

            Route::delete('/species/{species}/delete', 'destroySpecies')
                ->name('species.delete');
        });

    // The catalog's landing page from before it moved under each project.
    Route::get('/catalogs', [ProjectCatalogController::class, 'index'])
        ->name('catalogs.index');

    // FieldRecords — the physical collections a project has made. Collected and
    // recorded first, identified later, deposited later still, so determining
    // and depositing are their own routes rather than fields on a create form.
    // See docs/decisions/0008-specimens-and-determinations.md.
    Route::controller(FieldRecordController::class)
        ->prefix('/projects/{project}')
        ->whereNumber('project')
        ->name('catalogs.fieldRecords.')
        ->group(function () {
            Route::get('/records', 'index')->name('index');
            Route::get('/records/export', 'export')->name('export');
            Route::post('/records', 'store')->name('store');
            // Shortcut from a species page, where the identification is already known.
            Route::post('/catalog/species/{species}/records', 'storeForSpecies')
                ->name('store-for-species');
            Route::patch('/records/{fieldRecord}', 'update')->name('update');
            Route::post('/records/{fieldRecord}/determine', 'determine')
                ->name('determine');
            Route::post('/records/{fieldRecord}/deposit', 'deposit')
                ->name('deposit');
            Route::delete('/records/{fieldRecord}', 'destroy')->name('destroy');
        });

    // Photographs and audio on a field record. Posted from the browser rather
    // than handshaked like the companion's device uploads.
    Route::controller(FieldRecordMediaController::class)
        ->prefix('/projects/{project}/records/{fieldRecord}/media')
        ->whereNumber('project')
        ->name('catalogs.fieldRecords.media.')
        ->group(function () {
            Route::post('/', 'store')->name('store');
            Route::get('/{medium}', 'show')->name('show');
            Route::delete('/{medium}', 'destroy')->name('destroy');
        });

    // Collecting permits — the authorisations a project collects under, kept
    // beside its records. See docs/decisions/0009-collecting-permits.md.
    Route::controller(CollectingPermitController::class)
        ->prefix('/projects/{project}/permits')
        ->whereNumber('project')
        ->name('catalogs.permits.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::patch('/{permit}', 'update')->name('update');
            Route::delete('/{permit}', 'destroy')->name('destroy');
        });

    // Addresses from when records, permits and species lived under
    // /catalogs/{project}, kept working for bookmarks and shared links.
    Route::get('/catalogs/{project}/{path?}', LegacyCatalogRedirectController::class)
        ->whereNumber('project')
        ->where('path', '.*');

    Route::controller(InterviewDataController::class)->group(function () {
        Route::get('/data', 'index')->name('data.index');
        Route::get('/data/{project}/view', 'viewData')->name('data.view');
    });

    // What the companion captured, finally visible. Read-only: the device
    // authors this material and owns its lifecycle.
    Route::controller(InterviewMediaController::class)->group(function () {
        Route::get(
            '/data/{project}/interviews/{instance}/media',
            'index'
        )->name('data.media.index');
        Route::get(
            '/data/{project}/interviews/{instance}/media/{medium}',
            'show'
        )->name('data.media.show');
    });

    Route::controller(InterviewDataController::class)->group(function () {
        Route::post(
            '/data/{project}/chart-preference',
            'saveChartPreference'
        )->name('data.chart-preference');
        Route::get('/data/link/{project}', 'linkSpecies')->name('data.link');
        Route::get(
            '/data/link/{project}/species-search',
            'searchSpecies'
        )->name('data.link.species-search');
        Route::post('/data/link/{project}/handle', 'handleLinkRequest')->name(
            'data.link.handle'
        );
        Route::post('/data/link/{project}/bulk', 'handleBulkLinkRequest')->name(
            'data.link.bulk'
        );

        Route::get('/data/{project}/reports', 'reports')->name(
            'data.reports'
        );
        Route::get('/data/{project}/reports/download', 'downloadReport')->name(
            'data.reports.download'
        );

        Route::get('/data/{project}/export', 'prepareExport')->name(
            'data.export'
        );
        Route::get('/data/{project}/export/preview', 'exportPreview')->name(
            'data.export.preview'
        );
        Route::post('/data/{project}/export/download', 'downloadExport')->name(
            'data.export.download'
        );
    });
});

// Session-authenticated: called via axios from the catalog species page.
Route::post('/api/wfo-query', [WfoController::class, 'query'])
    ->middleware(['auth', 'throttle:api'])
    ->name('wfo.query');

// AGPL section 13: whoever uses this over a network must be able to obtain its
// source. Deliberately outside the public-site group — the offer has to stand
// even on a deployment that publishes no marketing pages at all.
Route::get('/software', [SoftwareNoticeController::class, 'show'])->name('software.notice');
Route::get('/software/licencias', [SoftwareNoticeController::class, 'licences'])
    ->name('software.licences');

// The root stays ungated so that an installation with no marketing pages still
// answers something useful there — it sends visitors to the application rather
// than to a 404.
Route::get('/', [PublicPageController::class, 'index'])->name('public.index');

// The rest describe one deployment's operator and make legal claims on their
// behalf, so they stay registered (route() must resolve for the links that
// reference them) but answer 404 unless this installation opted in.
Route::controller(PublicPageController::class)
    ->middleware('public_site')
    ->group(function () {
        Route::get('/acerca', 'about')->name('public.about');
        Route::get('/contacto', 'contact')->name('public.contact');
        Route::post('/contacto', 'handleContactRequest')
            ->middleware(['honeypot'])
            ->name('public.contact.handle');
        Route::get('/privacidad', 'privacy')->name('public.privacy');
        Route::get('/terminos', 'terms')->name('public.terms');
    });

require __DIR__.'/auth.php';
