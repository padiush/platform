<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogSpecies extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'family',
        'genus',
        'name',
        'authority',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function answers()
    {
        return $this->hasMany(InstanceAnswer::class);
    }

    /**
     * Reference imagery for the taxon — not photographs of a collection, which
     * belong to the specimen. See docs/decisions/0008-specimens-and-determinations.md.
     */
    public function photos()
    {
        return $this->hasMany(CatalogSpeciesPhoto::class);
    }

    /** Every determination that has ever named this taxon, current or superseded. */
    public function determinations()
    {
        return $this->hasMany(Determination::class);
    }

    /** The specimens currently determined as this taxon. */
    public function specimens()
    {
        return $this->hasManyThrough(
            Specimen::class,
            Determination::class,
            'catalog_species_id',
            'id',
            'id',
            'specimen_id',
        )->where('determinations.is_current', true);
    }
}
