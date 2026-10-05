<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The project a user last worked in. The sidebar opens on it after signing
 * in, and on any page whose address names no project. Kept on the account
 * rather than in the session, so it follows the user across devices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('last_project_id')
                ->nullable()
                ->after('system_admin')
                ->constrained('projects')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('last_project_id');
        });
    }
};
