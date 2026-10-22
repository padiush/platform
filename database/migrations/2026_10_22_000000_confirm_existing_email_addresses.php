<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Accounts now confirm their address before they can do anything. The ones
 * that already exist are counted as confirmed, so nobody already at work —
 * including someone in the field, on the app — is stopped by it. Only
 * accounts made from now on are asked.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    /** Which addresses were confirmed before this ran is not recorded, so there is nothing to undo. */
    public function down(): void {}
};
