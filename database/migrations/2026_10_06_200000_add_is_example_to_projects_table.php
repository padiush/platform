<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks a project as an example: a copy of the invented demonstration study
 * that a user opened to see the platform with something in it. It is labelled
 * wherever it appears, can be removed in one step, and is left out of counts
 * of real work.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('is_example')->default(false)->after('shared');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('is_example');
        });
    }
};
