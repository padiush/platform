<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A capture artifact — an audio recording or a photograph — belonging either to
 * an interview or to a field record, never to both. The bytes live in storage;
 * this row is the metadata and the upload/transcription lifecycle.
 *
 * For a field record of something that was never collected the photograph is
 * the record itself, since no material survives to re-examine
 * (docs/decisions/0010-field-records-and-basis.md). See also
 * docs/contracts/companion-api.md for the device upload path.
 */
class Media extends Model
{
    protected $table = 'media';

    public const KIND_AUDIO = 'audio';

    public const KIND_PHOTO = 'photo';

    public const STATUS_PENDING = 'pending';

    public const STATUS_STORED = 'stored';

    protected $fillable = [
        'interview_instance_id',
        'field_record_id',
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

    public function fieldRecord()
    {
        return $this->belongsTo(FieldRecord::class);
    }

    /**
     * Whether this belongs to a field record rather than an interview. Exactly
     * one owner is set; the pairing has no meaning and nothing writes it.
     */
    public function belongsToFieldRecord(): bool
    {
        return $this->field_record_id !== null;
    }

    public function isAudio(): bool
    {
        return $this->kind === self::KIND_AUDIO;
    }
}
