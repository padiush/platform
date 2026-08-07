<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Capture artifacts the platform could not hold before: audio recordings and
 * photos attached to an interview. The bytes live in object storage (S3/MinIO);
 * this row is the metadata + upload lifecycle. See docs/contracts/companion-api.md.
 *
 * Lifecycle: a device registers intent (status 'pending', a presigned upload
 * URL is issued), PUTs the file directly to storage, then calls complete
 * (status 'stored'). For audio, transcription is a separate, queued step
 * (docs/decisions/0005-interview-transcription-whisper.md); its status and the
 * (encrypted) transcript text live here too.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instance_media', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->uuid('interview_instance_id');
            // Device-minted idempotency key, stable across retries.
            $table->uuid('client_id')->unique();
            $table->string('kind'); // 'audio' | 'photo'
            $table->string('storage_disk')->default('s3');
            $table->string('storage_key')->nullable();
            $table->string('content_type')->nullable();
            $table->unsignedBigInteger('byte_size')->nullable();
            $table->unsignedInteger('duration_s')->nullable(); // audio only
            $table->string('status')->default('pending'); // 'pending' | 'stored'
            // Audio only; null for photos and until enqueued.
            $table->string('transcription_status')->nullable();
            $table->text('transcription_text')->nullable(); // encrypted
            $table->timestamp('captured_at')->nullable();

            $table->foreign('interview_instance_id')
                ->references('id')
                ->on('interview_instances')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instance_media');
    }
};
