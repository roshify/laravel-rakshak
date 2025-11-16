<?php

namespace Roshp\LaravelRakshak\Traits;

use Illuminate\Database\Eloquent\Collection;
use Roshp\LaravelRakshak\Models\ModelHasRole;
use Roshp\LaravelRakshak\Models\Role;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Roshp\LaravelRakshak\Services\RoleAssignment;

trait HasRoles {

    /**
     * A model may have multiple roles.
     */
    public function roles(): MorphToMany
    {
        /** @var \Illuminate\Database\Eloquent\Model $this */
        return $this->morphToMany(Role::class, 'model', 'model_has_roles', 'model_id', 'role_id')
            ->using(ModelHasRole::class)
            ->withPivot(['valid_from', 'valid_till', 'daily_start_time', 'daily_end_time'])
            ->withTimestamps();
    }

    // Assign roles with chaining and flexibility
    public function assignRole($roles)
    {
        return new RoleAssignment($this, $roles);
    }

    // Remove roles
    public function removeRole($roles)
    {
        $roles = $this->prepareRoles($roles);
        $this->roles()->detach($roles->pluck('id')->toArray());
    }

    // Remove all roles from the model
    public function removeAllRoles()
    {
        // Detach all roles from the model
        $this->roles()->detach();
    }

    // Check if the model has a specific role
    public function hasRole($roles)
    {
        $now = now();
        $roles = $this->prepareRoles($roles);

        return $this->roles()
            ->where(function ($query) use ($now) {
                // Check if valid_from is null or less than or equal to now
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                // Check if valid_till is null or greater than or equal to now
                $query->whereNull('valid_till')
                    ->orWhere('valid_till', '>=', $now);
            })
            // Handle daily_start_time and daily_end_time being nullable
            ->where(function ($query) use ($now) {
                $query->whereNull('daily_start_time')
                    ->orWhere('daily_start_time', '<=', $now->format('H:i:s'));
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('daily_end_time')
                    ->orWhere('daily_end_time', '>=', $now->format('H:i:s'));
            })
            ->whereIn('roles.id', $roles->pluck('id')->toArray())
            ->exists();
    }

    // Helper to prepare roles for checking, removing, etc.
    protected function prepareRoles($roles)
    {
        if (is_string($roles)) {
            return collect([Role::where('name', $roles)->first()]);
        } elseif (is_array($roles)) {
            return Role::whereIn('name', $roles)->get();
        } elseif ($roles instanceof Role) {
            return collect([$roles]);
        }

        return $roles instanceof Collection ? $roles : collect();
    }
}