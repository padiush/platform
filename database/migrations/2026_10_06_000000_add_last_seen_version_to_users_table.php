<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The last release whose notes each user has seen, so "What's new" can show
 * them everything since (docs/releasing.md).
 *
 * Every account that exists now has been using 1.0.0, the only release before
 * this column, so that is what they are recorded as having seen: the first
 * release after it will show its notes to all of them. Accounts created later
 * start on the release they were created under.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_seen_version', 32)->nullable()->after('last_project_id');
        });

        DB::table('users')->update(['last_seen_version' => '1.0.0']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_seen_version');
        });
    }
};
