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
}
