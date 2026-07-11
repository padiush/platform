<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'interview_form_id',
        'name',
        'description',
        'order',
        'repeatable',
    ];

    protected $casts = [
        'repeatable' => 'boolean',
    ];

    public function interviewForm()
    {
        return $this->belongsTo(InterviewForm::class);
    }

    public function items()
    {
        return $this->hasMany(InterviewItem::class);
    }
}
