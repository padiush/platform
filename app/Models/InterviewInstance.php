<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;
use App\Models\InterviewForm;
use App\Models\InstanceAnswer;

class InterviewInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'interview_form_id',
        'user_id',
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
}
