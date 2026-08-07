<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewInstance extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'interview_form_id',
        'user_id',
        'captured_at',
        'location_lat',
        'location_lng',
        'location_accuracy_m',
        'location_captured_at',
        'form_version_cursor',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'location_captured_at' => 'datetime',
        'form_version_cursor' => 'datetime',
        'location_lat' => 'float',
        'location_lng' => 'float',
        'location_accuracy_m' => 'float',
    ];

    public function form()
    {
        return $this->belongsTo(InterviewForm::class, 'interview_form_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(InstanceAnswer::class, 'interview_instance_id');
    }

    public function media()
    {
        return $this->hasMany(InstanceMedia::class, 'interview_instance_id');
    }
}
