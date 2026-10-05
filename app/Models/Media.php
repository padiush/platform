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
        'upload_id',
        'upload_part_size',
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
        'upload_part_size' => 'integer',
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

    /**
     * Whether the bytes are arriving as a multipart upload that storage is
     * still holding open (docs/decisions/0012-resumable-media-upload.md).
     */
    public function isMultipart(): bool
    {
        return $this->upload_id !== null;
    }

    /** How many parts the multipart upload is split into. */
    public function partCount(): int
    {
        return (int) ceil($this->byte_size / $this->upload_part_size);
    }

    /** The size the given part must be: a full part, except the last. */
    public function expectedPartSize(int $partNumber): int
    {
        return $partNumber < $this->partCount()
            ? $this->upload_part_size
            : $this->byte_size - $this->upload_part_size * ($this->partCount() - 1);
    }

    public function isAudio(): bool
    {
        return $this->kind === self::KIND_AUDIO;
    }
}
