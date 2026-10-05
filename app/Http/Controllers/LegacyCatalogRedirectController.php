<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sends an address from when a project's records, permits and species all
 * lived under /catalogs/{project} to where each lives now, so bookmarks and
 * links shared before the move keep working.
 *
 * The query string travels with it, since the records list opens filtered
 * from one. A fragment never reaches the server; the browser carries it over
 * a redirect on its own, which keeps links to a record's row working too.
 */
class LegacyCatalogRedirectController extends Controller
{
    public function __invoke(Request $request, int $project, ?string $path = null): RedirectResponse
    {
        $path = trim((string) $path, '/');
        $section = strtok($path, '/');

        $target = match (true) {
            $path === '' => "/projects/{$project}/catalog",
            in_array($section, ['records', 'permits'], true) => "/projects/{$project}/{$path}",
            $section === 'species' => "/projects/{$project}/catalog/{$path}",
            default => abort(404),
        };

        $query = $request->getQueryString();

        return redirect()->to($target.($query ? "?{$query}" : ''), 301);
    }
}
