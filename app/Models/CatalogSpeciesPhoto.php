<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CatalogSpecies;

class CatalogSpeciesPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'catalog_species_id',
        'path',
        'caption',
        'author',
        'license',
        'license_url',
    ];

    public function species(){
        return $this->belongsTo(CatalogSpecies::class, 'catalog_species_id');
    }
}
