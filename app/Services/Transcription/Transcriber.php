<?php

namespace App\Services\Transcription;

use App\Models\InstanceMedia;

/**
 * Turns a stored audio recording into text. The production implementation is
 * self-hosted Whisper (ADR 0005), which requires provisioning a real queue
 * driver and the model server; until that lands, NullTranscriber is bound and
 * transcription stays disabled behind config('services.transcription.enabled').
 */
interface Transcriber
{
    public function transcribe(InstanceMedia $media): string;
}
