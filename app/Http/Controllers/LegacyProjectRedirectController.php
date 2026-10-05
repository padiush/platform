<?php

namespace App\Http\Controllers;

use App\Services\ActiveProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sends an address from before a project's data and its administration moved
 * under /projects/{project} to where each lives now, so bookmarks and links
 * shared before the move keep working. Each old address named its project,
 * so the move is by address alone.
 */
class LegacyProjectRedirectController extends Controller
{
    /**
     * The data used to open on every project's; this opens the project the
     * user works in, or the one an old ?project= named.
     */
    public function data(Request $request, ActiveProject $active): RedirectResponse
    {
        $asked = $request->query('project');
        $project = ctype_digit((string) $asked) && $active->accesses($request->user())->has((int) $asked)
            ? (int) $asked
            : $active->resolve($request)?->id;

        return $project
            ? redirect()->route('data.view', ['project' => $project])
            : redirect()->route('dashboard');
    }

    /** /data/link/{project}, linking answers to species. */
    public function links(Request $request, int $project, ?string $path = null): RedirectResponse
    {
        $path = trim((string) $path, '/');

        abort_unless(in_array($path, ['', 'species-search'], true), 404);

        return $this->moved($request, rtrim("/projects/{$project}/data/links/{$path}", '/'));
    }

    /** /data/{project}/…, the table, reports, exports and media. */
    public function dataPage(Request $request, int $project, string $path): RedirectResponse
    {
        $path = trim($path, '/');

        $target = match (true) {
            $path === 'view' => '',
            in_array($path, ['reports', 'reports/download', 'export', 'export/preview'], true) => $path,
            (bool) preg_match('#^interviews/[0-9a-f-]{36}/media(/\d+)?$#i', $path) => $path,
            default => abort(404),
        };

        return $this->moved($request, rtrim("/projects/{$project}/data/{$target}", '/'));
    }

    public function settings(Request $request, int $project): RedirectResponse
    {
        return $this->moved($request, "/projects/{$project}/settings");
    }

    /** /projects/{project}/accesses…, who works in the project. */
    public function members(Request $request, int $project, ?string $path = null): RedirectResponse
    {
        $path = trim((string) $path, '/');

        abort_unless(in_array($path, ['', 'invites'], true), 404);

        return $this->moved($request, rtrim("/projects/{$project}/members/{$path}", '/'));
    }

    private function moved(Request $request, string $target): RedirectResponse
    {
        $query = $request->getQueryString();

        return redirect()->to($target.($query ? "?{$query}" : ''), 301);
    }
}
