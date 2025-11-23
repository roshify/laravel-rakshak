<?php

namespace Roshify\LaravelRakshak;

use Roshify\LaravelRakshak\Models\Action;
use Roshify\LaravelRakshak\Models\Module;
use Roshify\LaravelRakshak\Models\Role;

class Rakshak
{
    // Create a new role
    public function createRole($name)
    {
        return Role::create([
            'name' => $name,
            'slug' => strtr(strtolower($name), ' ', '-'),
            'status' => true, // Default status is active
        ]);
    }

    // Create a new module
    public function createModule($name)
    {
        return Module::create([
            'name' => $name,
            'slug' => strtr(strtolower($name), ' ', '-'),
            'status' => true,
        ]);
    }

    // Create a new action
    public function createAction($name)
    {
        return Action::create([
            'name' => $name,
            'slug' => strtr(strtolower($name), ' ', '-'),
            'status' => true,
        ]);
    }

    // Your main logic for managing roles, permissions, etc.
    public function assignRole(\Illuminate\Database\Eloquent\Model $model, string | \Roshify\LaravelRakshak\Models\Role $role)
    {
        // Logic to assign a role to a model
    }

    public function checkPermission(\Illuminate\Database\Eloquent\Model $model, string | \Roshify\LaravelRakshak\Models\Role $role)
    {
        // Logic to check permissions
    }
}