<?php

namespace App\Http\Controllers;

use App\Models\AdminAction;
use App\Models\Project;
use App\Models\ProjectAccess;
use App\Models\RegistrationInvite;
use App\Models\User;
use App\Notifications\RegistrationInviteNotification;
use App\Services\System\AccountDeletion;
use App\Services\System\StorageUsage;
use App\Services\System\SystemHealth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The system administrators' panel: who uses the platform, how much storage
 * their work takes, whether the upkeep is running, and what the other
 * administrators did.
 *
 * It shows counts, sizes and names, never what is inside a project: an
 * administrator who is not a member of a project cannot read its answers,
 * recordings or catalog from here.
 */
class SystemController extends Controller
{
    /** How far back "active" reaches on the summary. */
    private const ACTIVE_DAYS = 30;

    public function __construct(
        private StorageUsage $usage,
        private SystemHealth $health,
    ) {}

    public function index(): Response
    {
        $byProject = $this->usage->byProject();
        $owners = $this->usage->byOwner($byProject);
        $lastActive = $this->lastActivity();
        $projects = Project::where('is_example', false);

        return Inertia::render('System/Index', [
            'counts' => [
                'users' => User::count(),
                'active_users' => $lastActive
                    ->filter(fn (string $at) => now()->subDays(self::ACTIVE_DAYS)->lt($at))
                    ->count(),
                'invites' => RegistrationInvite::where('expires_at', '>', now())->count(),
                // Example projects are copies of the invented demo study, not work.
                'projects' => (clone $projects)->count(),
                'finished_projects' => (clone $projects)->where('finished', true)->count(),
            ],
            'storage' => [
                ...$this->usage->totals($byProject),
                'recent' => $this->usage->totals($this->usage->byProject(now()->subDays(self::ACTIVE_DAYS)))['total'],
            ],
            'version' => config('app.version'),
            'pending_migrations' => $this->health->pendingMigrations(),
            'checks' => $this->health->checks(),
            'top_owners' => $this->owners($owners)->take(5)->values(),
            'log' => AdminAction::latest('created_at')->latest('id')->limit(10)->get()
                ->map(fn (AdminAction $action) => $this->logEntry($action)),
        ]);
    }

    public function users(): Response
    {
        $owners = $this->usage->byOwner();
        $lastActive = $this->lastActivity();
        $owned = Project::where('is_example', false)
            ->select('user_id', DB::raw('COUNT(*) as count'))
            ->groupBy('user_id')
            ->pluck('count', 'user_id');
        $ownedIds = Project::pluck('user_id', 'id');
        $memberships = ProjectAccess::get(['user_id', 'project_id'])
            ->reject(fn (ProjectAccess $access) => ($ownedIds[$access->project_id] ?? null) === $access->user_id)
            ->countBy('user_id');

        return Inertia::render('System/Users', [
            'users' => User::orderBy('name')->get()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'system_admin' => (bool) $user->system_admin,
                'is_self' => $user->id === Auth::id(),
                'owned_projects' => (int) ($owned[$user->id] ?? 0),
                'member_of' => (int) ($memberships[$user->id] ?? 0),
                'storage' => $owners->get($user->id)['total'] ?? 0,
                'last_active_at' => $lastActive->get($user->id),
            ]),
            'registration_invites' => RegistrationInvite::query()
                ->where('expires_at', '>', now())
                ->orderBy('expires_at')
                ->get(['id', 'invited_name', 'invited_email', 'expires_at']),
        ]);
    }

    public function storage(): Response
    {
        $byProject = $this->usage->byProject();
        $names = User::pluck('name', 'id');

        $largest = Project::whereIn('id', $byProject->keys())->get(['id', 'name', 'user_id'])
            ->map(function (Project $project) use ($byProject, $names) {
                $usage = $byProject->get($project->id);

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'owner' => $names[$project->user_id] ?? null,
                    'bytes' => $usage['audio'] + $usage['photo'],
                    'files' => $usage['files'],
                ];
            })
            ->sortByDesc('bytes')
            ->take(10)
            ->values();

        return Inertia::render('System/Storage', [
            'storage' => [
                ...$this->usage->totals($byProject),
                'recent' => $this->usage->totals($this->usage->byProject(now()->subDays(self::ACTIVE_DAYS)))['total'],
            ],
            'owners' => $this->owners($this->usage->byOwner($byProject))->values(),
            'largest' => $largest,
            'checks' => $this->health->checks(),
        ]);
    }

    /** What deleting this account would take with it, for the confirmation. */
    public function deletionPreview(User $user, AccountDeletion $deletion): JsonResponse
    {
        return response()->json($deletion->preview($user));
    }

    /** Hand every project the account owns to someone else, before it goes. */
    public function transferProjects(Request $request, User $user, AccountDeletion $deletion): RedirectResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'integer', Rule::exists('users', 'id'), Rule::notIn([$user->id])],
        ]);

        $to = User::findOrFail($validated['to']);
        $count = $deletion->transfer($user, $to);

        AdminAction::record(Auth::user(), AdminAction::PROJECTS_TRANSFERRED, [
            'from' => $user->name,
            'to' => $to->name,
            'count' => $count,
        ]);

        return back(fallback: route('system.users'))
            ->with('message', 'system.projects_transferred')
            ->with('message_type', 'success');
    }

    public function inviteRegistration(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
        ]);

        $invite = RegistrationInvite::updateOrCreate(
            ['invited_email' => $validated['email']],
            [
                'inviting_user_id' => Auth::id(),
                'invited_name' => $validated['name'],
                'expires_at' => now()->addDays(RegistrationInvite::EXPIRATION_DAYS),
            ]
        );

        $this->sendInvite($invite);

        AdminAction::record(Auth::user(), AdminAction::INVITE_SENT, [
            'name' => $invite->invited_name,
            'email' => $invite->invited_email,
        ]);

        return back(fallback: route('system.users'))
            ->with('message', 'system.registration_invite_sent')
            ->with('message_type', 'success');
    }

    /** Send an invitation again, with a fresh week to accept it. */
    public function resendInvite(RegistrationInvite $invite): RedirectResponse
    {
        $invite->forceFill([
            'inviting_user_id' => Auth::id(),
            'expires_at' => now()->addDays(RegistrationInvite::EXPIRATION_DAYS),
        ])->save();

        $this->sendInvite($invite);

        AdminAction::record(Auth::user(), AdminAction::INVITE_RESENT, ['email' => $invite->invited_email]);

        return back(fallback: route('system.users'))
            ->with('message', 'system.registration_invite_sent')
            ->with('message_type', 'success');
    }

    public function withdrawInvite(RegistrationInvite $invite): RedirectResponse
    {
        $invite->delete();

        AdminAction::record(Auth::user(), AdminAction::INVITE_WITHDRAWN, ['email' => $invite->invited_email]);

        return back(fallback: route('system.users'))
            ->with('message', 'system.registration_invite_withdrawn')
            ->with('message_type', 'success');
    }

    public function destroyUser(User $user, AccountDeletion $deletion): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return redirect()
                ->route('system.users')
                ->with('message', 'system.cannot_delete_self')
                ->with('message_type', 'error');
        }

        if ($user->system_admin && User::where('system_admin', true)->count() <= 1) {
            return redirect()
                ->route('system.users')
                ->with('message', 'system.cannot_delete_last_admin')
                ->with('message_type', 'error');
        }

        // Counted before, for the record: afterwards there is nothing to count.
        $preview = $deletion->preview($user);

        $user->delete();

        AdminAction::record(Auth::user(), AdminAction::USER_DELETED, [
            'name' => $preview['user']['name'],
            'email' => $preview['user']['email'],
            'projects' => $preview['projects']->count(),
            'files' => $preview['files'],
        ]);

        return redirect()
            ->route('system.users')
            ->with('message', 'system.user_deleted')
            ->with('message_type', 'success');
    }

    private function sendInvite(RegistrationInvite $invite): void
    {
        Notification::route('mail', $invite->invited_email)->notify(
            new RegistrationInviteNotification($invite)
        );
    }

    /**
     * When each person was last seen: their latest web session or the last
     * time one of their field devices used its token, whichever is later.
     *
     * @return Collection<int, string> ISO 8601, by user id
     */
    private function lastActivity(): Collection
    {
        $web = DB::table('sessions')
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('MAX(last_activity) as at'))
            ->groupBy('user_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->user_id => now()->setTimestamp((int) $row->at)]);

        $devices = DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->whereNotNull('last_used_at')
            ->select('tokenable_id', DB::raw('MAX(last_used_at) as at'))
            ->groupBy('tokenable_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->tokenable_id => Carbon::parse($row->at)]);

        return $web->keys()->merge($devices->keys())->unique()
            ->mapWithKeys(function (int $id) use ($web, $devices) {
                $latest = collect([$web->get($id), $devices->get($id)])->filter()->max();

                return [$id => $latest->toIso8601String()];
            });
    }

    /** @return Collection<int, array> owners with stored media, most first */
    private function owners(Collection $byOwner): Collection
    {
        $names = User::whereIn('id', $byOwner->keys())->pluck('name', 'id');

        return $byOwner
            ->filter(fn (array $usage) => $usage['total'] > 0)
            ->map(fn (array $usage, int $id) => ['id' => $id, 'name' => $names[$id] ?? null, ...$usage])
            ->sortByDesc('total');
    }

    private function logEntry(AdminAction $action): array
    {
        return [
            'id' => $action->id,
            'actor' => $action->actor_name,
            'action' => $action->action,
            'details' => $action->details ?? [],
            'at' => $action->created_at->toIso8601String(),
        ];
    }
}
