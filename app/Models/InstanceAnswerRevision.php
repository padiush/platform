<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A snapshot of an answer's value taken just before it was overwritten by a
 * newer edit — the audit trail that keeps last-writer-wins from silently losing
 * a field (docs/decisions/0004-offline-sync-model.md). Immutable: created once,
 * never updated.
 */
class InstanceAnswerRevision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'instance_answer_id',
        'answer',
        'catalog_species_id',
        'edited_at',
        'source',
    ];

    protected $casts = [
        'answer' => 'encrypted',
        'edited_at' => 'datetime',
    ];

    public function answer()
    {
        return $this->belongsTo(InstanceAnswer::class, 'instance_answer_id');
    }
}
