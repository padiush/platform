<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\ProjectInvite;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'author',
        'institution',
        'author_email',
        'country',
        'finished',
        'published',
        'shared',
    ];

    protected $casts = [
        'finished' => 'boolean',
        'published' => 'boolean',
        'shared' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function accesses()
    {
        return $this->hasMany(ProjectAccess::class);
    }

    public function capabilities()
    {
        return $this->hasMany(ProjectCapability::class);
    }

    public function users()
    {
        $users = collect();

        foreach($this->accesses as $access){
            $users->push($access->user);
        }

        return $users;
    }

    public function invites()
    {
        return $this->hasMany(ProjectInvite::class);
    }
}
