<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectCapability extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'manage_project',
        'manage_users',
        'manage_forms',
        'record_data',
        'manage_data',
        'generate_reports',
    ];

    protected $casts = [
        'manage_project' => 'boolean',
        'manage_users' => 'boolean',
        'manage_forms' => 'boolean',
        'record_data' => 'boolean',
        'manage_data' => 'boolean',
        'generate_reports' => 'boolean',
    ];

    public function projectAccesses()
    {
        return $this->belongsToMany(ProjectAccess::class);
    }
}
