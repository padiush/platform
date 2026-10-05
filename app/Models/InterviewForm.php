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

    protected static function booted(): void
    {
        // Its interviews cascade in the database; their recordings and
        // photographs are deleted through the model first, so the bytes go
        // too (MediaObserver).
        static::deleting(function (InterviewForm $form) {
            Media::whereIn('interview_instance_id', $form->instances()->select('id'))
                ->get()
                ->each
                ->delete();
        });
    }

    public function instances()
    {
        return $this->hasMany(InterviewInstance::class);
    }
}
