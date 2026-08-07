<?php

namespace App\Services\Transcription;

use RuntimeException;

/**
 * Thrown when transcription is requested but no real transcriber is provisioned
 * (ADR 0005). The job catches it and marks the recording's transcription failed
 * rather than crashing the queue.
 */
class TranscriptionUnavailableException extends RuntimeException {}
