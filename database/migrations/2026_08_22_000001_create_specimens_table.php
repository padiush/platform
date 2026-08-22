<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A physical collection: the thing that was picked, pressed, tagged and
 * deposited. Distinct from `catalog_species`, which is a *taxon* — the end of
 * identification rather than the object identified. One taxon has many
 * specimens. See docs/decisions/0008-specimens-and-determinations.md.
 *
 * Two numbers, deliberately, because botany uses two and they are not
 * interchangeable:
 *
 *   collection_number — the collector's own field number, written on the tag at
 *                       the moment of collection (Darwin Core `recordNumber`)
 *   accession_number  — the voucher identifier the repository issues on deposit
 *                       (Darwin Core `catalogNumber`); here the project issues
 *                       it, because a community herbarium has no curator to
 *                       issue it for them
 *
 * Everything is nullable but the project. A voucher is never required — capture
 * never blocks anywhere else in Padiush, and market surveys, cultivated species
 * and pure observation are legitimately unvouchered. Coverage is reported
 * instead, so an absent voucher is visible rather than silent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specimens', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('project_id');

            $table->string('accession_number')->nullable();
            $table->string('collection_number')->nullable();
            $table->string('collector')->nullable();
            $table->date('collected_on')->nullable();

            // Where it was collected. Free text because a locality description
            // ("cafetal above the school, ~1400m") is what a field notebook
            // holds; the coordinates are what the companion already captures.
            $table->string('locality')->nullable();
            $table->decimal('location_lat', 10, 7)->nullable();
            $table->decimal('location_lng', 10, 7)->nullable();

            // An institution or a community herbarium — the model does not care
            // which, which is the point.
            $table->string('repository')->nullable();
            $table->text('notes')->nullable();

            // The answer that produced it, when the specimen came out of an
            // interview rather than being entered directly. Nullable both ways:
            // a specimen can exist without an interview, and deleting an answer
            // must not destroy the physical record it referred to.
            $table->unsignedBigInteger('instance_answer_id')->nullable();

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnDelete();

            $table->foreign('instance_answer_id')
                ->references('id')
                ->on('instance_answers')
                ->nullOnDelete();

            // Per project, not globally: two studies on one instance issue
            // their own series. Nulls are exempt, so unvouchered specimens
            // coexist freely.
            $table->unique(['project_id', 'accession_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specimens');
    }
};
