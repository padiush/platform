<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The authorisation under which a project's material was collected.
 *
 * Collecting wild biological material is regulated almost everywhere — the
 * issuing authority differs by country, the requirement does not. One permit
 * covers many collections, which is why this is a table rather than a string
 * repeated on every specimen: "which specimens did I take under this permit?"
 * is the question that actually gets asked, by a herbarium on deposit, by a
 * journal at submission, or by an authority years later.
 *
 * A reference record, NOT a document store, and NOT a compliance check. What is
 * here is what the researcher stated; nothing validates that a permit is
 * genuine, current, or covers what was collected.
 * See docs/decisions/0009-collecting-permits.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collecting_permits', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('project_id');

            // Free text: the naming varies by country and there is no list worth
            // hard-coding ("MARN", "CONAP", "SERFOR", a regional office…).
            $table->string('authority');
            $table->string('reference');

            $table->date('issued_on')->nullable();
            // Displayed because it is written on the permit — not enforced, and
            // not a verdict about whether a collection was lawful.
            $table->date('expires_on')->nullable();

            // Scope as the permit words it: taxa, area, quantity.
            $table->text('notes')->nullable();

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnDelete();

            // Per project, like every other record here. The same reference may
            // legitimately appear in two studies.
            $table->unique(['project_id', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collecting_permits');
    }
};
