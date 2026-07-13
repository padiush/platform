<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A capture artifact (audio recording or photo) attached to an interview. The
 * bytes live in object storage; this row is the metadata and upload/transcription
 * lifecycle. See docs/contracts/companion-api.md.
 */
class InstanceMedia extends Model
{
    protected $table = 'instance_media';

    public const KIND_AUDIO = 'audio';

    public const KIND_PHOTO = 'photo';

    public const STATUS_PENDING = 'pending';

    public const STATUS_STORED = 'stored';

    protected $fillable = [
        'interview_instance_id',
        'client_id',
        'kind',
        'storage_disk',
        'storage_key',
        'content_type',
        'byte_size',
        'duration_s',
        'status',
        'transcription_status',
        'transcription_text',
        'captured_at',
    ];

    protected $casts = [
        'transcription_text' => 'encrypted',
        'captured_at' => 'datetime',
        'byte_size' => 'integer',
        'duration_s' => 'integer',
    ];

    public function instance()
    {
        return $this->belongsTo(InterviewInstance::class, 'interview_instance_id');
    }

    public function isAudio(): bool
    {
        return $this->kind === self::KIND_AUDIO;
    }
}
