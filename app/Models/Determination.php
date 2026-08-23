<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * What a fieldRecord was identified as, by whom, and when. Kept as history: a
 * determination is an opinion, and revising it must not erase what was thought
 * before. See docs/decisions/0008-specimens-and-determinations.md.
 */
class Determination extends Model
{
    use HasFactory;

    /** Compare with — resembles the named taxon but is not confirmed as it. */
    public const QUALIFIER_CF = 'cf';

    /** Allied to — close to the named taxon, possibly undescribed. */
    public const QUALIFIER_AFF = 'aff';

    /** Genus only — identified no further than the genus. */
    public const QUALIFIER_SP = 'sp';

    protected $fillable = [
        'catalog_species_id',
        'determiner',
        'determined_on',
        'qualifier',
        'is_current',
        'notes',
    ];

    protected $casts = [
        'determined_on' => 'date',
        'is_current' => 'boolean',
    ];

    public function fieldRecord()
    {
        return $this->belongsTo(FieldRecord::class);
    }

    /** Null when the fieldRecord is `indet.` — collected but not yet identified. */
    public function species()
    {
        return $this->belongsTo(CatalogSpecies::class, 'catalog_species_id');
    }

    public function isIndeterminate(): bool
    {
        return $this->catalog_species_id === null;
    }
}
