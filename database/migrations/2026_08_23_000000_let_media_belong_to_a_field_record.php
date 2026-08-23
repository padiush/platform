<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Media stops belonging only to an interview.
 *
 * A field record needs photographs and audio too, and for a record of something
 * that was never collected the photograph *is* the record — no material
 * survives to re-examine. See docs/decisions/0010-field-records-and-basis.md.
 *
 * One table rather than two, because the lifecycle is identical (a row, bytes
 * in storage, a status) and because audio from a guided walk is exactly what
 * the transcription pipeline exists for
 * (docs/decisions/0005-interview-transcription-whisper.md). Duplicating that
 * plumbing to keep the tables apart would be the expensive mistake.
 *
 * Two nullable foreign keys rather than one polymorphic pair: an interview id
 * is a uuid and a field record id is an integer, so a single owner column would
 * hold both only as text, and both cascades would be lost. Exactly one is set,
 * enforced above the database because the two supported engines express a check
 * constraint differently.
 *
 * The table is renamed because `instance_media` stops being true. Nothing
 * external depends on the name — the companion contract names endpoints, not
 * tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('instance_media', 'media');

        Schema::table('media', function (Blueprint $table) {
            // Media captured against a field record has no interview.
            $table->uuid('interview_instance_id')->nullable()->change();

            $table->unsignedBigInteger('field_record_id')->nullable()->after('interview_instance_id');

            $table->foreign('field_record_id')
                ->references('id')
                ->on('field_records')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropForeign(['field_record_id']);
            $table->dropColumn('field_record_id');
        });

        // Anything left without an interview cannot survive the old shape.
        DB::table('media')->whereNull('interview_instance_id')->delete();

        Schema::table('media', function (Blueprint $table) {
            $table->uuid('interview_instance_id')->nullable(false)->change();
        });

        Schema::rename('media', 'instance_media');
    }
};
