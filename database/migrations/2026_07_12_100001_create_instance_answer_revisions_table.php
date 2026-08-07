<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The overwrite audit trail for the last-writer-wins conflict policy
 * (docs/decisions/0004-offline-sync-model.md). When a sync (or a web edit)
 * overwrites an existing answer with a newer edit, the old value is snapshotted
 * here first — so a clobbered field is recoverable, never silently lost.
 *
 * Rows are immutable (created only), so there is no updated_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instance_answer_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_answer_id')
                ->constrained('instance_answers')
                ->cascadeOnDelete();
            // The overwritten value, kept encrypted just like the live answer.
            $table->text('answer')->nullable();
            // The species link that was in force when this value was overwritten
            // (usually null — linking is a later web-side task).
            $table->unsignedBigInteger('catalog_species_id')->nullable();
            // The edit-time of the value that was overwritten (its LWW key).
            $table->timestamp('edited_at')->nullable();
            // Where the overwriting write came from: 'api' (device sync) or 'web'.
            $table->string('source')->default('api');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instance_answer_revisions');
    }
};
