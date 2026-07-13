<?php

namespace App\Services\Transcription;

use App\Models\InstanceMedia;

/**
 * The default transcriber: none is provisioned. Bound until a real Whisper
 * driver is wired (ADR 0005). It should never actually run — transcription is
 * gated off by config — but if a job reaches it, it fails loudly rather than
 * inventing a transcript.
 */
class NullTranscriber implements Transcriber
{
    public function transcribe(InstanceMedia $media): string
    {
        throw new TranscriptionUnavailableException(
            'No transcription driver is configured.'
        );
    }
}
