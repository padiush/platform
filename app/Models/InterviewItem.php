<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'interview_section_id',
        'label',
        'type',
        'name',
        'required',
        'order',
        'min',
        'max',
        'step',
        'options',
        'link_to_species',
    ];

    protected $casts = [
        'required' => 'boolean',
        'link_to_species' => 'boolean',
    ];

    public function section()
    {
        return $this->belongsTo(InterviewSection::class);
    }

    public function getOptionsAttribute($value)
    {
        return json_decode($value);
    }
}
