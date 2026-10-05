<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What system administrators did: invitations, account deletions, project
 * transfers, promotions. Shown to every administrator, so the people who can
 * delete accounts can see each other do it.
 *
 * The details are names, emails and counts, never anything from inside a
 * project. The actor's name is kept beside the link to them, so the record
 * still reads after their own account is gone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('action', 64);
            $table->json('details')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_actions');
    }
};
