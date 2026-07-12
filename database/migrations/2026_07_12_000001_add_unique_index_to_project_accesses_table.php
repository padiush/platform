<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A user should have at most one access row per project — the app assumes
     * it (hasAccessToProject uses firstWhere), but nothing enforced it, leaving
     * the effective role nondeterministic if a duplicate ever slipped in.
     */
    public function up(): void
    {
        // Collapse any pre-existing duplicates, keeping the earliest row per
        // (user, project). Portable across MariaDB and the sqlite test DB.
        $keepIds = DB::table('project_accesses')
            ->select(DB::raw('MIN(id) as id'))
            ->groupBy('user_id', 'project_id')
            ->pluck('id');

        DB::table('project_accesses')->whereNotIn('id', $keepIds)->delete();

        Schema::table('project_accesses', function (Blueprint $table) {
            $table->unique(['user_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::table('project_accesses', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'project_id']);
        });
    }
};
