<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\Transcription\Transcriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Transcribes a stored audio recording out of band from the interview push
 * (docs/contracts/sync-protocol.md). Enqueued by the media complete endpoint
 * when transcription is enabled; the device learns the result on a later pull of
 * GET /api/v1/instances/{instance}.
 *
 * This needs a real queue driver (the app defaults to QUEUE_CONNECTION=sync) and
 * a provisioned transcriber (ADR 0005) — both prerequisites to enabling it.
 */
class TranscribeAudio implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Media $media) {}

    public function handle(Transcriber $transcriber): void
    {
        $this->media->update(['transcription_status' => 'processing']);

        try {
            // Includes TranscriptionUnavailableException when no driver is bound.
            $text = $transcriber->transcribe($this->media);
        } catch (Throwable $e) {
            $this->media->update(['transcription_status' => 'failed']);

            return;
        }

        $this->media->update([
            'transcription_status' => 'done',
            'transcription_text' => $text,
        ]);
    }
}
