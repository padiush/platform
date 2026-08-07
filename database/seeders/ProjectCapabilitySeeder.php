<?php

namespace Database\Seeders;

use App\Models\ProjectCapability;
use Illuminate\Database\Seeder;

class ProjectCapabilitySeeder extends Seeder
{
    public function run(): void
    {
        $capabilities = [
            [
                'name' => 'Administrador del proyecto',
                'manage_project' => true,
                'manage_users' => true,
                'manage_forms' => true,
                'record_data' => true,
                'manage_data' => true,
                'generate_reports' => true,
                'view_catalog' => true,
                'edit_catalog' => true,
            ],
            [
                'name' => 'Usuario técnico',
                'manage_project' => false,
                'manage_users' => false,
                'manage_forms' => false,
                'record_data' => true,
                'manage_data' => false,
                'generate_reports' => true,
                'view_catalog' => true,
                'edit_catalog' => true,
            ],
            [
                'name' => 'Usuario de consulta',
                'manage_project' => false,
                'manage_users' => false,
                'manage_forms' => false,
                'record_data' => false,
                'manage_data' => false,
                'generate_reports' => true,
                'view_catalog' => true,
                'edit_catalog' => false,
            ],
        ];

        foreach ($capabilities as $capability) {
            ProjectCapability::updateOrCreate(
                ['name' => $capability['name']],
                $capability
            );
        }
    }
}
