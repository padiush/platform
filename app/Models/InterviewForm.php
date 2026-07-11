<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'is_active',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function sections()
    {
        return $this->hasMany(InterviewSection::class);
    }

    public function instances()
    {
        return $this->hasMany(InterviewInstance::class);
    }
}
