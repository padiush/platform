<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'interview_item_id',
        'chart_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        return $this->belongsTo(InterviewItem::class, 'interview_item_id');
    }
}
