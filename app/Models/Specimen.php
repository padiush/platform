<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A physical collection — what was picked, pressed, tagged and deposited.
 * Distinct from CatalogSpecies, which is the taxon it was eventually identified
 * as. See docs/decisions/0008-specimens-and-determinations.md.
 */
class Specimen extends Model
{
    use HasFactory;

    /**
     * `project_id` is deliberately absent: which study a specimen belongs to is
     * not something a request body gets to say. Set it explicitly, the way
     * Project does with its owner.
     */
    protected $fillable = [
        'accession_number',
        'collection_number',
        'collector',
        'collected_on',
        'locality',
        'location_lat',
        'location_lng',
        'repository',
        'collecting_permit_id',
        'permit_exemption',
        'notes',
        'instance_answer_id',
    ];

    protected $casts = [
        'collected_on' => 'date',
        'location_lat' => 'float',
        'location_lng' => 'float',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function determinations()
    {
        return $this->hasMany(Determination::class);
    }

    /**
     * The determination in force. Latest rather than only — superseded ones stay
     * on the record.
     */
    public function currentDetermination()
    {
        return $this->hasOne(Determination::class)->where('is_current', true);
    }

    /** The answer this specimen came out of, when it came out of an interview. */
    public function answer()
    {
        return $this->belongsTo(InstanceAnswer::class, 'instance_answer_id');
    }

    /** A specimen is voucher-backed once it carries an accession number. */
    public function isVouchered(): bool
    {
        return filled($this->accession_number);
    }

    /** The authorisation this was collected under, when one was needed. */
    public function collectingPermit()
    {
        return $this->belongsTo(CollectingPermit::class);
    }

    /**
     * Whether the legal basis for this collection has been accounted for —
     * either a permit, or a stated reason none was required.
     *
     * The two are different answers and both are complete ones; only the
     * absence of either means "not recorded yet".
     */
    public function permitIsAccountedFor(): bool
    {
        return $this->collecting_permit_id !== null
            || filled($this->permit_exemption);
    }

    public function isPermitExempt(): bool
    {
        return filled($this->permit_exemption);
    }
}
