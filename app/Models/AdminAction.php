<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One thing a system administrator did, for the record every administrator
 * sees. `details` holds only what the sentence needs — names, emails, counts —
 * and `action` is a key the frontend words in the reader's language.
 */
class AdminAction extends Model
{
    public const INVITE_SENT = 'invite.sent';

    public const INVITE_RESENT = 'invite.resent';

    public const INVITE_WITHDRAWN = 'invite.withdrawn';

    public const USER_DELETED = 'user.deleted';

    public const PROJECTS_TRANSFERRED = 'projects.transferred';

    public const ADMIN_PROMOTED = 'admin.promoted';

    public const UPDATED_AT = null;

    protected $fillable = ['actor_id', 'actor_name', 'action', 'details'];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    /** Record an action; a null actor is someone at the server's console. */
    public static function record(?User $actor, string $action, array $details = []): self
    {
        return self::create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'action' => $action,
            'details' => $details,
        ]);
    }
}
