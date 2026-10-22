<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\PendingProjectInvites;
use Illuminate\Auth\Events\Verified;

/** Once an address is confirmed, the invitations waiting for it are honoured. */
class AcceptPendingProjectInvites
{
    public function __construct(private PendingProjectInvites $invites) {}

    public function handle(Verified $event): void
    {
        if ($event->user instanceof User) {
            $this->invites->accept($event->user);
        }
    }
}
