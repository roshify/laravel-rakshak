<?php

namespace Roshp\LaravelRakshak\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Roshp\LaravelRakshak\Models\Role;

class RoleAssignment {
    protected $model;
    protected $roles;
    protected $attributes = [
        'valid_from' => null,
        'valid_till' => null,
        'daily_start_time' => null,
        'daily_end_time' => null,
    ];

    public function __construct(Model $model, $roles)
    {
        $this->model = $model;
        $this->roles = $this->prepareRoles($roles);
    }

    // Set valid_from attribute
    public function validFrom($date)
    {
        $this->attributes['valid_from'] = $date ?? null;
        return $this;
    }

    // Set valid_till attribute
    public function validTill($date)
    {
        $this->attributes['valid_till'] = $date ?? null;
        return $this;
    }

    // Set daily_start_time attribute
    public function dailyStartTime($time)
    {
        $this->attributes['daily_start_time'] = $time ?? null;
        return $this;
    }

    // Set daily_end_time attribute
    public function dailyEndTime($time)
    {
        $this->attributes['daily_end_time'] = $time ?? null;
        return $this;
    }

    // Save the roles with the specified attributes
    public function save()
    {
        foreach ($this->roles as $role) {
            // Check if the role is already assigned to the model
            $existingRole = $this->model->roles()->where('role_id', $role->id)->first();

            if ($existingRole) {
                // If the role is already assigned, update the pivot attributes
                $this->model->roles()->updateExistingPivot($role->id, $this->attributes);
            } else {
                // If the role is not assigned, attach the role with the specified attributes
                $this->model->roles()->attach($role->id, $this->attributes);
            }
        }
    }

    // Sync the roles: Add, update, and remove as necessary
    public function sync()
    {
        // Get the current role IDs assigned to the model
        $currentRoleIds = $this->model->roles()->pluck('role_id')->toArray();
        $newRoleIds = $this->roles->pluck('id')->toArray();

        // Determine which roles should be removed (assigned but not incoming)
        $rolesToRemove = array_diff($currentRoleIds, $newRoleIds);
        if (!empty($rolesToRemove)) {
            $this->model->roles()->detach($rolesToRemove);
        }

        // Now save the incoming roles (either add new or update existing)
        foreach ($this->roles as $role) {
            $existingRole = $this->model->roles()->where('role_id', $role->id)->first();

            if ($existingRole) {
                // Update existing role with the new attributes
                $this->model->roles()->updateExistingPivot($role->id, $this->attributes);
            } else {
                // Attach new role with the specified attributes
                $this->model->roles()->attach($role->id, $this->attributes);
            }
        }
    }

    // Helper method to prepare roles (for string, array, model, or collection)
    protected function prepareRoles($roles)
    {
        if (is_string($roles)) {
            return collect([Role::where('name', $roles)->firstOrFail()]);
        } elseif (is_array($roles)) {
            return Role::whereIn('name', $roles)->get();
        } elseif ($roles instanceof Role) {
            return collect([$roles]);
        }

        return $roles instanceof Collection ? $roles : collect();
    }
}