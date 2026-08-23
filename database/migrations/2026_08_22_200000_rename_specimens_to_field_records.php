<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A field record is any documented encounter with a plant: one that was
 * collected, and one that was only seen.
 *
 * 0008 modelled the collection and called the table `fieldRecords`. Much of what a
 * study documents is never collected — no permit covers it, the taxon is
 * protected, it is a tree, or a key informant simply pointed at it on a walk —
 * and those encounters are real records with a place, a date, an observer and
 * something to identify later. `basis_of_record` says which kind, using Darwin
 * Core's own values so the export needs no translation.
 *
 * The name changes because "fieldRecord" stops being true, and a record of
 * something nobody collected is not an ejemplar.
 * See docs/decisions/0010-field-records-and-basis.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('specimens', 'field_records');

        // The foreign key survives the table rename; the column name does not
        // change with it, so it is dropped and rebuilt around the new name.
        Schema::table('determinations', function (Blueprint $table) {
            $table->dropForeign(['specimen_id']);
        });

        Schema::table('determinations', function (Blueprint $table) {
            $table->renameColumn('specimen_id', 'field_record_id');
        });

        Schema::table('determinations', function (Blueprint $table) {
            $table->foreign('field_record_id')
                ->references('id')
                ->on('field_records')
                ->cascadeOnDelete();
        });

        Schema::table('field_records', function (Blueprint $table) {
            // Everything that exists was collected: 0008 could not record
            // anything else.
            $table->string('basis_of_record')
                ->default('preserved_specimen')
                ->after('project_id');

            // What an informant called it. Text rather than string because the
            // ciphertext is longer than the name, and encrypted because this is
            // the same category of data as an interview answer — which the
            // platform already encrypts wherever it is captured.
            $table->text('vernacular_name')->nullable()->after('basis_of_record');
        });
    }

    public function down(): void
    {
        Schema::table('field_records', function (Blueprint $table) {
            $table->dropColumn(['basis_of_record', 'vernacular_name']);
        });

        Schema::table('determinations', function (Blueprint $table) {
            $table->dropForeign(['field_record_id']);
        });

        Schema::table('determinations', function (Blueprint $table) {
            $table->renameColumn('field_record_id', 'specimen_id');
        });

        Schema::rename('field_records', 'specimens');

        Schema::table('determinations', function (Blueprint $table) {
            $table->foreign('specimen_id')
                ->references('id')
                ->on('specimens')
                ->cascadeOnDelete();
        });
    }
};
