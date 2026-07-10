<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectInvite extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'inviting_user_id',
        'invited_user_id',
        'invited_name',
        'invited_email',
        'expires_at',
        'project_capability_id',
    ];

    protected $casts = [
        // Serialized as an ISO string; the invite tables format it into a
        // relative "expires in…" client-side, in the user's UI language.
        'expires_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function invitingUser()
    {
        return $this->belongsTo(User::class, 'inviting_user_id');
    }

    public function invitedUser()
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }

    public function capability()
    {
        return $this->belongsTo(ProjectCapability::class, 'project_capability_id');
    }
}
