<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\InterviewInstance;
use App\Models\InterviewItem;

class InstanceAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'interview_instance_id',
        'interview_item_id',
        'repeatable_index',
        'answer',
    ];

    public function instance(){
        return $this->belongsTo(InterviewInstance::class, 'interview_instance_id');
    }

    public function item(){
        return $this->belongsTo(InterviewItem::class, 'interview_item_id');
    }
}
