<?php

namespace Roshp\LaravelRakshak\Traits;

use Illuminate\Support\Facades\Cache;
use Roshp\LaravelRakshak\Models\Action;
use Roshp\LaravelRakshak\Models\Module;
use Roshp\LaravelRakshak\Models\Permission;
use Roshp\LaravelRakshak\Services\PermissionAssignment;

trait HasPermissions {

    /**
     * A model can have multiple permissions.
     *
     * This method defines a polymorphic relationship to the `Permission` model,
     * meaning any model can have multiple permissions assigned to it.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function permissions()
    {
        /** @var \Illuminate\Database\Eloquent\Model $this */
        return $this->morphMany(Permission::class, 'model');
    }

    /**
     * Assign permissions with chaining and flexibility.
     *
     * This method provides a way to assign multiple permissions to the model 
     * in a flexible and chainable manner using the `PermissionAssignment` helper.
     *
     * @param array|\Illuminate\Support\Collection $permissions
     * @return PermissionAssignment
     */
    public function assignPermission($permissions)
    {
        return new PermissionAssignment($this, $permissions);
    }

    /**
     * Remove specific permissions from the model.
     *
     * This method removes one or more permissions from the model. It handles 
     * both cases where the permission is defined by a module or a module-action pair.
     * 
     * - If a permission includes an action, it removes the specific module-action pair.
     * - If no action is provided, it removes all actions for the given module.
     *
     * @param array|\Illuminate\Support\Collection $permissions
     * @return void
     */
    public function removePermission($permissions)
    {
        // Prepare the permissions, ensuring they are formatted correctly
        $preparedPermissions = $this->preparePermissions($permissions);

        // Separate permissions into module-only and module-action combinations
        $moduleIds = collect();
        $moduleActionPairs = collect();

        foreach ($preparedPermissions as $permission) {
            if (isset($permission->action)) {
                // If there's an action, treat it as a module-action pair
                $moduleActionPairs->push([
                    'module_id' => $permission->module->id,
                    'action_id' => $permission->action->id,
                ]);
            } else {
                // If no action, treat it as module-only
                $moduleIds->push($permission->module->id);
            }
        }

        // Remove permissions for module-action pairs
        if ($moduleActionPairs->isNotEmpty()) {
            Permission::where('model_id', $this->id)
                ->where('model_type', get_class($this))
                ->where(function ($query) use ($moduleActionPairs) {
                    foreach ($moduleActionPairs as $pair) {
                        $query->orWhere(function ($subQuery) use ($pair) {
                            $subQuery->where('module_id', $pair['module_id'])
                                ->where('action_id', $pair['action_id']);
                        });
                    }
                })
                ->delete();
        }

        // Remove permissions for entire modules (all actions)
        if ($moduleIds->isNotEmpty()) {
            Permission::where('model_id', $this->id)
                ->where('model_type', get_class($this))
                ->whereIn('module_id', $moduleIds)
                ->delete();
        }
    }

    /**
     * Remove all permissions from the model.
     *
     * This method deletes all permissions associated with the model.
     *
     * @return void
     */
    public function removeAllPermissions()
    {
        // Delete all permissions related to the model
        $this->permissions()->delete();
    }

    /**
     * Check if the model has a specific permission.
     *
     * This method checks if the model has any of the specified permissions.
     * It uses caching to improve performance when checking frequently.
     *
     * @param array|\Illuminate\Support\Collection $permissions
     * @return bool
     */
    public function hasPermission($permissions)
    {
        // Prepare permissions
        $permissions = $this->preparePermissions($permissions);

        // Check permissions with caching, and return whether any match
        return $this->checkPermissions($permissions, false);
    }

    /**
     * Check if the model has all of the specified permissions.
     *
     * This method checks if the model has **all** of the specified permissions.
     * It uses caching to improve performance when checking frequently.
     *
     * @param array|\Illuminate\Support\Collection $permissions
     * @return bool
     */
    public function hasAllPermissions($permissions)
    {
        // Prepare permissions
        $permissions = $this->preparePermissions($permissions);

        // Check permissions with caching, and return whether all match
        return $this->checkPermissions($permissions, true);
    }

    /**
     * Check if the user has permission through roles and designation.
     *
     * This method is a placeholder for future logic to check permissions through
     * the user's roles or job designation, currently returning false.
     *
     * @param array|\Illuminate\Support\Collection $permissions
     * @return bool
     */
    public function hasPermissionThroughHierarchy($permissions)
    {
        return false;
    }

    /**
     * Check permissions using a custom hierarchy.
     *
     * This method allows checking permissions through a custom hierarchy provided
     * as an optional argument. This is a placeholder method that currently returns false.
     *
     * @param array|\Illuminate\Support\Collection $permissions
     * @param mixed|null $customHierarchy
     * @return bool
     */
    public function hasPermissionUsingHierarchy($permissions, $customHierarchy = null)
    {
        return false;
    }

    /**
     * Check permissions across specified related models.
     *
     * This method is a placeholder for logic to check permissions across different
     * related models, such as roles or groups, and currently returns false.
     *
     * @param array|string $relations
     * @param array|\Illuminate\Support\Collection $permissions
     * @return bool
     */
    public function hasPermissionAcross($relations, $permissions)
    {
        return false;
    }

    // Refactored method to handle permission checking with caching
    protected function checkPermissions($permissions, $matchAll = false)
    {
        $now = now(); // Get the current datetime

        // Generate a unique cache key for the permission check
        $cacheKey = $this->generateCacheKey($matchAll ? 'hasAllPermissions' : 'hasPermission', $permissions);

        // Retrieve cached result or perform the check if not cached
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($permissions, $now, $matchAll) {
            // Eager load the model's permissions once
            $modelPermissions = $this->permissions()->get();

            // Filter and count permissions based on module, action, and time constraints
            $matchingPermissions = $permissions->filter(function ($permission) use ($modelPermissions, $now) {
                $module = $permission->module;
                $action = $permission->action;

                // Check if user has the required permission
                return $modelPermissions->contains(function ($userPermission) use ($module, $action, $now) {
                    return $userPermission->module_id === $module->id
                        && (!$action || $userPermission->action_id === $action->id)
                        && ($userPermission->valid_from === null || $userPermission->valid_from <= $now)
                        && ($userPermission->valid_till === null || $userPermission->valid_till >= $now)
                        && ($userPermission->daily_start_time === null || $userPermission->daily_start_time <= $now->format('H:i:s'))
                        && ($userPermission->daily_end_time === null || $userPermission->daily_end_time >= $now->format('H:i:s'));
                });
            });

            // If matchAll is true, check if all permissions match, otherwise check if at least one matches
            return $matchAll ? $matchingPermissions->count() === $permissions->count() : $matchingPermissions->isNotEmpty();
        });
    }

    // Helper method to generate a unique cache key based on user and permissions
    protected function generateCacheKey($methodName, $permissions)
    {
        // Convert permissions to a unique string
        $permissionsString = $permissions->map(function ($permission) {
            return $permission->module->name . ':' . ($permission->action ? $permission->action->name : 'all');
        })->implode('|');

        // Generate a unique cache key for the user and method
        return strtolower(class_basename($this)) . '_' . $this->id . '_' . $methodName . '_' . md5($permissionsString);
    }

    // Helper method to prepare permissions (for string, array, model, or collection)
    protected function preparePermissions($permissions)
    {
        $preparedPermissions = collect();

        // Ensure $permissions is an array
        $permissions = is_string($permissions) ? [$permissions] : $permissions;

        // Extract unique module names and actions from all permissions
        $moduleNames = collect();
        $actionNames = collect();

        foreach ($permissions as $permission) {
            // Split module and actions safely
            if (strpos($permission, ':') !== false) {
                list($moduleName, $actions) = explode(':', $permission);
                $actionNames = $actionNames->merge(explode(',', $actions)); // Collect action names
            } else {
                $moduleName = $permission;
                $actions = null; // No actions specified
            }

            $moduleNames->push($moduleName); // Collect module names
        }

        // Ensure moduleNames and actionNames contain only unique values
        $moduleNames = $moduleNames->unique();
        $actionNames = $actionNames->unique();

        // Fetch all required modules and actions in a single query
        $moduleCollection = Module::whereIn('name', $moduleNames->toArray())->get()->keyBy('name');
        $actionCollection = Action::whereIn('name', $actionNames->toArray())->get()->keyBy('name');

        // Process each permission string
        foreach ($permissions as $permission) {
            // Split module and actions safely
            if (strpos($permission, ':') !== false) {
                list($moduleName, $actions) = explode(':', $permission);
                $actionsArray = explode(',', $actions);
            } else {
                $moduleName = $permission;
                $actionsArray = []; // No actions specified
            }

            // Get the module
            $module = $moduleCollection->get($moduleName);

            if (!$module) {
                continue; // Skip if module doesn't exist
            }

            // Get the actions for the module
            foreach ($actionsArray as $actionName) {
                $action = $actionCollection->get($actionName);

                if ($action) {
                    // Push the module-action pair to the prepared permissions collection
                    $preparedPermissions->push((object)[
                        'module' => $module,
                        'action' => $action,
                    ]);
                }
            }

            // If there are no actions specified, push the module with action as null
            if (empty($actionsArray)) {
                $preparedPermissions->push((object)[
                    'module' => $module,
                    'action' => null,
                ]);
            }
        }

        return $preparedPermissions;
    }
}