<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a specimen was identified as, by whom, and when — kept as a history
 * rather than a single answer, because a determination is an opinion that gets
 * revised. The current one is merely the latest.
 * See docs/decisions/0008-specimens-and-determinations.md.
 *
 * The taxon is NULLABLE on purpose. `indet.` is a real and common state: a
 * specimen is collected and deposited long before anyone puts a name to it, and
 * forcing a name at collection time would record a guess as a fact. A
 * determination with no taxon still carries who looked at it and when, which is
 * the useful part of "nobody has identified this yet".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('determinations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('specimen_id');

            // Null = indet. Deleting a taxon must not delete the record that
            // someone once identified this specimen as it.
            $table->unsignedBigInteger('catalog_species_id')->nullable();

            $table->string('determiner')->nullable();
            $table->date('determined_on')->nullable();

            // Botanical confidence qualifiers: 'cf' (compare with), 'aff.'
            // (allied to), 'sp.' (genus only). Null = determined without
            // reservation. A string rather than an enum because the vocabulary
            // differs by subfield (0006) and an enum migration is expensive.
            $table->string('qualifier')->nullable();

            $table->boolean('is_current')->default(true);
            $table->text('notes')->nullable();

            $table->foreign('specimen_id')
                ->references('id')
                ->on('specimens')
                ->cascadeOnDelete();

            $table->foreign('catalog_species_id')
                ->references('id')
                ->on('catalog_species')
                ->nullOnDelete();

            // The read this table exists to serve: "what is this specimen now?"
            $table->index(['specimen_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('determinations');
    }
};
