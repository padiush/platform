<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The authorisation a project's material was collected under.
 *
 * A reference record. Nothing here validates that a permit is genuine, current,
 * or covers what was collected — `hasExpired()` reads a date the researcher
 * typed, and is not a verdict about whether a collection was lawful.
 * See docs/decisions/0009-collecting-permits.md.
 */
class CollectingPermit extends Model
{
    use HasFactory;

    /**
     * `project_id` is deliberately absent, as on Specimen: which study a permit
     * belongs to is not something a request body gets to say.
     */
    protected $fillable = [
        'authority',
        'reference',
        'issued_on',
        'expires_on',
        'notes',
    ];

    protected $casts = [
        'issued_on' => 'date',
        'expires_on' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function specimens()
    {
        return $this->hasMany(Specimen::class);
    }

    /**
     * Whether the expiry date written on the permit has passed. Null when no
     * expiry was recorded — which is not the same as "still valid".
     */
    public function hasExpired(): ?bool
    {
        return $this->expires_on?->isPast();
    }

    /** How it reads in a table or an export: "MARN · RES-042-2026". */
    public function label(): string
    {
        return trim("{$this->authority} · {$this->reference}", ' ·');
    }
}
