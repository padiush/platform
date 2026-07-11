<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * designed_by was never populated and the designer() relation reading it
     * has been removed. The create migration no longer defines the column
     * (SQLite cannot drop a column referenced by a foreign key), so this
     * only runs against databases migrated before the removal.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('interview_forms', 'designed_by')) {
            return;
        }

        Schema::table('interview_forms', function (Blueprint $table) {
            $table->dropForeign(['designed_by']);
            $table->dropColumn('designed_by');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('interview_forms', 'designed_by')) {
            return;
        }

        Schema::table('interview_forms', function (Blueprint $table) {
            $table->unsignedBigInteger('designed_by')->nullable();
            $table->foreign('designed_by')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
