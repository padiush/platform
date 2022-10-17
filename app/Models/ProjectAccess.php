<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Project;
use App\Models\User;
use App\Models\ProjectCapability;

class ProjectAccess extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'project_capability_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function capability()
    {
        return $this->hasOne(ProjectCapability::class, 'id', 'project_capability_id');
    }
}
