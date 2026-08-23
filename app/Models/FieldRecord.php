<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A physical collection — what was picked, pressed, tagged and deposited.
 * Distinct from CatalogSpecies, which is the taxon it was eventually identified
 * as. See docs/decisions/0008-specimens-and-determinations.md.
 */
class FieldRecord extends Model
{
    use HasFactory;

    /**
     * `project_id` is deliberately absent: which study a fieldRecord belongs to is
     * not something a request body gets to say. Set it explicitly, the way
     * Project does with its owner.
     */
    /** Pressed, tagged, deposited — what 0008 modelled, and the default. */
    public const BASIS_PRESERVED = 'preserved_specimen';

    /** Seen and documented, nothing collected. */
    public const BASIS_OBSERVATION = 'human_observation';

    /** Growing material: a garden, a nursery, a living collection. */
    public const BASIS_LIVING = 'living_specimen';

    /** A sample taken from something without collecting the whole. */
    public const BASIS_SAMPLE = 'material_sample';

    public const BASES = [
        self::BASIS_PRESERVED,
        self::BASIS_OBSERVATION,
        self::BASIS_LIVING,
        self::BASIS_SAMPLE,
    ];

    /**
     * The stated reasons a collection needed no permit. Domain vocabulary, so
     * it lives beside BASES rather than on whichever controller first needed it
     * — the web and the companion sync both validate against it now.
     * See docs/decisions/0009-collecting-permits.md.
     */
    public const EXEMPTIONS = ['private_land', 'cultivated', 'market', 'other'];

    protected $fillable = [
        'client_id',
        'basis_of_record',
        'vernacular_name',
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
        'edited_at',
    ];

    /**
     * Mirrors the column default so a record built in memory reports the basis
     * it would be saved with.
     */
    protected $attributes = [
        'basis_of_record' => self::BASIS_PRESERVED,
    ];

    protected $casts = [
        // The name an informant gave it. Encrypted because it is the same
        // category of data as an interview answer, and the platform should not
        // treat it differently for having been typed on another screen.
        'vernacular_name' => 'encrypted',
        'collected_on' => 'date',
        'location_lat' => 'float',
        'location_lng' => 'float',
        // The device's own edit time, which last-writer-wins compares against.
        'edited_at' => 'datetime',
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

    /** The answer this fieldRecord came out of, when it came out of an interview. */
    public function answer()
    {
        return $this->belongsTo(InstanceAnswer::class, 'instance_answer_id');
    }

    /** A record is voucher-backed once it carries an accession number. */
    public function isVouchered(): bool
    {
        return filled($this->accession_number);
    }

    /**
     * Whether anything was actually taken. A record of something left standing
     * can never carry a voucher, so it is not a gap in the evidence — it is a
     * different kind of evidence, and coverage counts it apart.
     */
    public function wasCollected(): bool
    {
        return $this->basis_of_record !== self::BASIS_OBSERVATION;
    }

    /**
     * Photographs and audio. For an observation these are the whole of the
     * evidence — there is no pressed material behind them.
     */
    public function media()
    {
        return $this->hasMany(Media::class);
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
