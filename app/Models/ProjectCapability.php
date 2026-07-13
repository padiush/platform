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
        'view_catalog',
        'edit_catalog',
    ];

    protected $casts = [
        'manage_project' => 'boolean',
        'manage_users' => 'boolean',
        'manage_forms' => 'boolean',
        'record_data' => 'boolean',
        'manage_data' => 'boolean',
        'generate_reports' => 'boolean',
        'view_catalog' => 'boolean',
        'edit_catalog' => 'boolean',
    ];

    /**
     * The permission flags as a flat name => bool map. Shared by the web's
     * auth.capabilities and the companion API's /me so both describe a role
     * the same way.
     */
    public function flags(): array
    {
        return [
            'manage_project' => (bool) $this->manage_project,
            'manage_users' => (bool) $this->manage_users,
            'manage_forms' => (bool) $this->manage_forms,
            'record_data' => (bool) $this->record_data,
            'manage_data' => (bool) $this->manage_data,
            'generate_reports' => (bool) $this->generate_reports,
            'view_catalog' => (bool) $this->view_catalog,
            'edit_catalog' => (bool) $this->edit_catalog,
        ];
    }
}
