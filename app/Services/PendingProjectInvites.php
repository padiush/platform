<?php

namespace App\Services;

use App\Models\ProjectInvite;
use App\Models\User;

/**
 * Invitations to a project sent to an address before it had an account. They
 * are honoured once the account behind that address has proved it receives
 * mail there — at once for an account made from an invitation link, after the
 * confirmation link for one made through open registration. Granting them on
 * the strength of a typed address alone would hand a project to whoever typed
 * it first.
 */
class PendingProjectInvites
{
    /** Grant the account every live invitation to its address; drop expired ones. */
    public function accept(User $user): void
    {
        $invites = ProjectInvite::query()
            ->where('invited_email', $user->email)
            ->where('expires_at', '>', now())
            ->get();

        foreach ($invites as $invite) {
            $invite->project->accesses()->firstOrCreate(
                ['user_id' => $user->id],
                ['project_capability_id' => $invite->project_capability_id]
            );

            $invite->delete();
        }

        ProjectInvite::query()
            ->where('invited_email', $user->email)
            ->where('expires_at', '<=', now())
            ->delete();
    }
}
