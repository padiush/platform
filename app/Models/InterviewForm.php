<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Project;
use App\Models\User;
use App\Models\InterviewSection;
use App\Models\InterviewInstance;

class InterviewForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'designed_by',
        'name',
        'description',
        'is_active',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function designer()
    {
        return $this->belongsTo(User::class, 'designed_by');
    }

    public function sections()
    {
        return $this->hasMany(InterviewSection::class);
    }

    public function nonRepeatableSections()
    {
        return $this->hasMany(InterviewSection::class)->where(
            'repeatable',
            false
        );
    }

    public function repeatableSections()
    {
        return $this->hasMany(InterviewSection::class)->where(
            'repeatable',
            true
        );
    }

    public function instances()
    {
        return $this->hasMany(InterviewInstance::class);
    }
}
