<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'expires_at' => 'datetime',
    ];

    /**
     * The invite tables render this as the "Expires" column; without it being
     * appended, invite.expires_at_human is undefined on the client and the
     * column shows blank.
     */
    protected $appends = ['expires_at_human'];

    protected function expiresAtHuman(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->expires_at?->diffForHumans()
        );
    }

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
