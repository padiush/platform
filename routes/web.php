<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ActiveProjectController;
use App\Http\Controllers\CollectingPermitController;
use App\Http\Controllers\ExampleProjectController;
use App\Http\Controllers\FieldRecordController;
use App\Http\Controllers\FieldRecordMediaController;
use App\Http\Controllers\InterviewDataController;
use App\Http\Controllers\InterviewDesignerController;
use App\Http\Controllers\InterviewFormController;
use App\Http\Controllers\InterviewInstancesController;
use App\Http\Controllers\InterviewMediaController;
use App\Http\Controllers\LegacyCatalogRedirectController;
use App\Http\Controllers\LegacyInterviewRedirectController;
use App\Http\Controllers\LegacyProjectRedirectController;
use App\Http\Controllers\ProjectCatalogController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectOverviewController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\SoftwareNoticeController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\WfoController;
use App\Http\Controllers\WhatsNewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Signing in lands on the project the user works in; with none yet, on a
    // welcome that points at creating one.
    Route::get('/dashboard', [ProjectOverviewController::class, 'landing'])
        ->name('dashboard');
    Route::get('/projects/{project}', [ProjectOverviewController::class, 'show'])
        ->whereNumber('project')
        ->name('projects.overview');

    // The example project: the invented demo study, a private copy per user.
    Route::post('/projects/example', [ExampleProjectController::class, 'store'])
        ->name('projects.example.store');
    Route::delete('/projects/example', [ExampleProjectController::class, 'destroy'])
        ->name('projects.example.destroy');

    // The sidebar's project switcher.
    Route::post('/projects/{project}/activate', [ActiveProjectController::class, 'store'])
        ->name('projects.activate');

    // What's new: the notes of every release, and the one the user has seen.
    Route::get('/whats-new', [WhatsNewController::class, 'index'])->name('whats-new');
    Route::post('/whats-new/seen', [WhatsNewController::class, 'seen'])->name('whats-new.seen');

    // Guided tours: which ones the user has been through, and starting over.
    Route::post('/tours/done', [TourController::class, 'complete'])->name('tours.done');
    Route::delete('/tours', [TourController::class, 'reset'])->name('tours.reset');

    // Mi cuenta: where the user is signed in, and signing it out.
    Route::controller(AccountController::class)
        ->prefix('account')
        ->name('account.')
        ->group(function () {
            Route::get('/', 'show')->name('show');
            Route::delete('/sessions', 'destroyOtherSessions')->name('sessions.destroy-others');
            Route::delete('/sessions/{session}', 'destroySession')->name('sessions.destroy');
            Route::delete('/devices/{device}', 'destroyDevice')
                ->whereNumber('device')
                ->name('devices.destroy');
        });

    Route::middleware('system_admin')
        ->prefix('system')
        ->name('system.')
        ->group(function () {
            Route::get('/', [SystemController::class, 'index'])
                ->name('index');
            Route::get('/users', [SystemController::class, 'users'])
                ->name('users');
            Route::get('/storage', [SystemController::class, 'storage'])
                ->name('storage');
            Route::post('/registration-invites', [SystemController::class, 'inviteRegistration'])
                ->name('registration-invites.store');
            Route::post('/registration-invites/{invite}/resend', [SystemController::class, 'resendInvite'])
                ->name('registration-invites.resend');
            Route::delete('/registration-invites/{invite}', [SystemController::class, 'withdrawInvite'])
                ->name('registration-invites.destroy');
            Route::get('/users/{user}/deletion', [SystemController::class, 'deletionPreview'])
                ->name('users.deletion');
            Route::post('/users/{user}/transfer', [SystemController::class, 'transferProjects'])
                ->name('users.transfer');
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

            Route::get('/accept/{invite}', 'acceptInvite')->name(
                'invites.accept'
            );
            Route::get('/decline/{invite}', 'declineInvite')->name(
                'invites.decline'
            );

            // A project's administration: its details, and who works in it.
            // Route names predate the move to /settings and /members.
            Route::whereNumber('project')->group(function () {
                Route::get('/{project}/settings', 'edit')->name('edit');
                Route::post('/{project}/settings', 'update');
                Route::delete('/{project}', 'destroy')->name('delete');

                Route::get('/{project}/members', 'manageAccess')->name('accesses');
                Route::post('/{project}/members/invite', 'inviteUser')
                    ->name('accesses.invite');
                Route::get('/{project}/members/invites', 'projectInvites')
                    ->name('accesses.invites');
                Route::delete('/{project}/members/invites/{invite}', 'revokeInvite')
                    ->name('accesses.invites.revoke');
                Route::delete('/{project}/members/{user}', 'revokeAccess')
                    ->name('accesses.revoke');
            });
        });

    // A project's interview forms: designing them, and the form's details.
    // Route names predate the move under /projects and are kept.
    Route::prefix('/projects/{project}/forms')
        ->whereNumber('project')
        ->name('designer.')
        ->group(function () {
            Route::controller(InterviewFormController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/create', 'store');
                Route::get('/{form}/edit', 'edit')->name('form.edit');
                Route::put('/{form}', 'update')->name('form.update');
                Route::delete('/{form}', 'destroy')->name('form.delete');
                Route::put('/{form}/toggle', 'toggle')->name('form.toggle');
            });

            Route::controller(InterviewDesignerController::class)->group(function () {
                Route::get('/{form}/design', 'designer')->name('form.wizard');
                Route::get('/{form}/preview', 'preview')->name('form.preview');
                Route::put('/{form}/structure', 'updateStructure')
                    ->name('form.structure.update');
            });
        });

    // A project's interviews: starting one from an active form, the ones
    // each form has taken, and answering one.
    Route::controller(InterviewInstancesController::class)
        ->prefix('/projects/{project}/interviews')
        ->whereNumber('project')
        ->name('interviews.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/forms/{form}', 'list')->name('instances');
            Route::get('/forms/{form}/new', 'create')->name('create');
            Route::get('/{instance}', 'show')->whereUuid('instance')->name('show');
            Route::post('/{instance}/answers', 'saveAnswer')->name('save_answer');
            Route::delete('/{instance}/sections/{section}', 'destroyRepeatableSet')
                ->name('section.remove');
            Route::delete('/{instance}', 'destroy')->name('destroy');
        });

    // Addresses from before forms and interviews moved under their project,
    // kept working for bookmarks and shared links.
    Route::controller(LegacyInterviewRedirectController::class)->group(function () {
        Route::get('/designer', 'forms');
        Route::get('/designer/{project}/{path}', 'form')
            ->whereNumber('project')
            ->where('path', '.*');
        Route::get('/interviews', 'interviews');
        Route::get('/interviews/{form}/{page}', 'formInterviews')
            ->whereNumber('form')
            ->whereIn('page', ['create', 'instances']);
        Route::get('/interviews/instance/{instance}', 'interview')
            ->whereUuid('instance');
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

    // A project's interview data: the table, linking answers to species, the
    // reports, and exports. Route names predate the move under /projects.
    Route::prefix('/projects/{project}/data')
        ->whereNumber('project')
        ->name('data.')
        ->group(function () {
            Route::controller(InterviewDataController::class)->group(function () {
                Route::get('/', 'viewData')->name('view');
                Route::post('/chart-preference', 'saveChartPreference')
                    ->name('chart-preference');

                Route::get('/links', 'linkSpecies')->name('link');
                Route::get('/links/species-search', 'searchSpecies')
                    ->name('link.species-search');
                Route::post('/links/handle', 'handleLinkRequest')->name('link.handle');
                Route::post('/links/bulk', 'handleBulkLinkRequest')->name('link.bulk');

                Route::get('/reports', 'reports')->name('reports');
                Route::get('/reports/download', 'downloadReport')
                    ->name('reports.download');

                Route::get('/export', 'prepareExport')->name('export');
                Route::get('/export/preview', 'exportPreview')->name('export.preview');
                Route::post('/export/download', 'downloadExport')
                    ->name('export.download');
            });

            // What the companion captured, finally visible. Read-only: the
            // device authors this material and owns its lifecycle.
            Route::controller(InterviewMediaController::class)->group(function () {
                Route::get('/interviews/{instance}/media', 'index')
                    ->name('media.index');
                Route::get('/interviews/{instance}/media/{medium}', 'show')
                    ->name('media.show');
            });
        });

    // Addresses from before the data and a project's administration moved
    // under the project, kept working for bookmarks and shared links.
    Route::controller(LegacyProjectRedirectController::class)->group(function () {
        Route::get('/data', 'data');
        Route::get('/data/link/{project}/{path?}', 'links')
            ->whereNumber('project')
            ->where('path', '.*');
        Route::get('/data/{project}/{path}', 'dataPage')
            ->whereNumber('project')
            ->where('path', '.*');
        Route::get('/projects/{project}/edit', 'settings')->whereNumber('project');
        Route::get('/projects/{project}/accesses/{path?}', 'members')
            ->whereNumber('project')
            ->where('path', '.*');
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
