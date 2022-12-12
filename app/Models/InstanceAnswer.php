<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\InterviewInstance;
use App\Models\InterviewItem;
use App\Models\CatalogSpecies;
use App\Models\InterviewSection;

class InstanceAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'interview_instance_id',
        'interview_section_id',
        'interview_item_id',
        'repeatable_index',
        'answer',
        'catalog_species_id',
    ];

    public function instance(){
        return $this->belongsTo(InterviewInstance::class, 'interview_instance_id');
    }

    public function item(){
        return $this->belongsTo(InterviewItem::class, 'interview_item_id');
    }

    public function section(){
        return $this->belongsTo(InterviewSection::class, 'interview_section_id');
    }

    public function species(){
        return $this->belongsTo(CatalogSpecies::class, 'catalog_species_id');
    }
}
