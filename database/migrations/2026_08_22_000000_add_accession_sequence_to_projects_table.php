<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a project issue its own accession numbers.
 *
 * A herbarium with a curator has a registry; a community herbarium has neither,
 * which is why specimens deposited with a community arrive without accession
 * numbers and therefore without vouchers. The project becomes its own issuing
 * authority — see docs/decisions/0008-specimens-and-determinations.md.
 *
 * The prefix is the researcher's (an acronym, a collector's initials, whatever
 * the study already uses); the counter is the sequence behind it. Both live on
 * the project because numbering is per study, not global — two projects on one
 * instance issue their own series and neither can see the other's.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('accession_prefix')->nullable()->after('country');
            // The next number to hand out, not the last one handed out — so a
            // fresh project starts at 1 without a special case.
            $table->unsignedInteger('next_accession_number')->default(1)->after('accession_prefix');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['accession_prefix', 'next_accession_number']);
        });
    }
};
