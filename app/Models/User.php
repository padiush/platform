<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
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

    public function projectCapabilities()
    {
        return $this->hasMany(ProjectCapability::class);
    }

    public function hasAccessToProject(Project $project)
    {
        $access = $this->projectAccesses()->where('project_id', $project->id)->first();

        return $access ? $access : false;
    }

    public function hasCapabilityOnProject(Project $project, string $query)
    {
        $access = $this->hasAccessToProject($project);

        if ($access) {
            return $access->capability->$query;
        }

        return false;
    }
}
