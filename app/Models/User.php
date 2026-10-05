<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements HasLocalePreference
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'system_admin' => 'boolean',
        'completed_tours' => 'array',
    ];

    /**
     * The guided tours the web offers: one welcome tour, then one for each
     * section on its first visit. The names are shared with
     * resources/js/tours/definitions.js.
     */
    public const TOURS = [
        'welcome',
        'navigation',
        'projects',
        'overview',
        'forms',
        'designer',
        'interviews',
        'records',
        'permits',
        'catalog',
        'data',
        'members',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function projectAccesses()
    {
        return $this->hasMany(ProjectAccess::class);
    }

    public function hasAccessToProject(Project $project)
    {
        // Loaded once and cached on the instance: policy checks call this
        // repeatedly within a request.
        $this->loadMissing('projectAccesses.capability');

        return $this->projectAccesses->firstWhere('project_id', $project->id) ?: false;
    }

    public function hasCapabilityOnProject(Project $project, string $query)
    {
        $access = $this->hasAccessToProject($project);

        if ($access) {
            return $access->capability->$query;
        }

        return false;
    }

    /**
     * The language this account's notifications should be written in.
     *
     * Laravel reads this when sending, so a queued email lands in the
     * recipient's language even though no request is in flight. Null falls
     * back to the application locale, which is right for accounts that have
     * never expressed a preference.
     */
    public function preferredLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * A new account has nothing to catch up on: it starts on the release it
     * was created under, so "What's new" first appears at the next one.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->last_seen_version ??= config('app.version');
        });

        // The projects an account owns cascade in the database; deleting them
        // through the model takes their catalog, questions and stored files
        // with them (Project::booted).
        static::deleting(function (User $user) {
            $user->projects()->get()->each->delete();
        });
    }

    /** Whether a release has shipped since this user last saw the notes. */
    public function hasUnseenRelease(): bool
    {
        return version_compare((string) $this->last_seen_version, (string) config('app.version'), '<');
    }

    /** Note that the user has finished, or skipped, a guided tour. */
    public function completeTour(string $tour): void
    {
        $done = $this->completed_tours ?? [];

        if (! in_array($tour, $done, true)) {
            $done[] = $tour;
            $this->forceFill(['completed_tours' => $done])->save();
        }
    }

    /** Offer every guided tour again, as if the user were new. */
    public function resetTours(): void
    {
        $this->forceFill(['completed_tours' => []])->save();
    }

    /** Note that the user has seen the notes of the release running now. */
    public function markReleaseSeen(): void
    {
        $this->forceFill(['last_seen_version' => config('app.version')])->save();
    }
}
