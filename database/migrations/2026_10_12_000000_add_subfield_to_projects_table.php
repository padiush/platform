<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Names the branch of ethnobiology a project belongs to. It will decide which
 * taxonomic authorities the catalog checks names against, and the vocabulary
 * and report defaults the project starts with. Every project so far is an
 * ethnobotany study, which is what the default gives existing rows.
 *
 * A string rather than an enum, because each new subfield would otherwise cost
 * a table rebuild. See docs/decisions/0006-multi-subfield-architecture.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('subfield', 32)->default('ethnobotany')->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('subfield');
        });
    }
};
