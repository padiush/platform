<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Project;

class CatalogSpecies extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'family',
        'genus',
        'name',
        'authority'
    ];

    public function project(){
        return $this->belongsTo(Project::class);
    }
}
