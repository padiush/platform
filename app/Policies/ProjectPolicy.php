<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Authorization is per-project RBAC: a user's ProjectAccess row links to a
 * ProjectCapability role whose boolean columns are checked here. Controllers
 * translate denials into their own redirect/JSON responses, so policy
 * methods are only used as boolean checks (never via authorize()/403).
 */
class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return (bool) $user->hasAccessToProject($project);
    }

    public function manageProject(User $user, Project $project): bool
    {
        return (bool) $user->hasCapabilityOnProject($project, 'manage_project');
    }

    public function manageUsers(User $user, Project $project): bool
    {
        return (bool) $user->hasCapabilityOnProject($project, 'manage_users');
    }

    public function manageForms(User $user, Project $project): bool
    {
        return (bool) $user->hasCapabilityOnProject($project, 'manage_forms');
    }

    public function recordData(User $user, Project $project): bool
    {
        return (bool) $user->hasCapabilityOnProject($project, 'record_data');
    }

    public function manageData(User $user, Project $project): bool
    {
        return (bool) $user->hasCapabilityOnProject($project, 'manage_data');
    }

    public function generateReports(User $user, Project $project): bool
    {
        return (bool) $user->hasCapabilityOnProject($project, 'generate_reports');
    }

    public function viewCatalog(User $user, Project $project): bool
    {
        return (bool) $user->hasCapabilityOnProject($project, 'view_catalog');
    }

    public function editCatalog(User $user, Project $project): bool
    {
        return (bool) $user->hasCapabilityOnProject($project, 'edit_catalog');
    }
}
