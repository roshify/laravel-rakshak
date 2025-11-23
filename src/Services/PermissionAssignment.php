<?php

namespace Roshify\LaravelRakshak\Services;

use Illuminate\Database\Eloquent\Model;
use Roshify\LaravelRakshak\Models\Action;
use Roshify\LaravelRakshak\Models\Module;

class PermissionAssignment {
    protected $model;
    protected $permissions;
    protected $attributes = [
        'valid_from' => null,
        'valid_till' => null,
        'daily_start_time' => null,
        'daily_end_time' => null,
    ];

    public function __construct(Model $model, $permissions)
    {
        $this->model = $model;
        $this->permissions = $this->preparePermissions($permissions);
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

    // Save the permissions with the specified attributes
    public function save()
    {
        // Extract unique module and action IDs
        $moduleIds = $this->permissions->pluck('module.id')->unique();
        $actionIds = $this->permissions->pluck('action.id')->unique()->filter(); // Filter out null values

        // Fetch existing permissions for the current model (to avoid duplicate creation)
        $existingPermissions = $this->model->permissions()
            ->whereIn('module_id', $moduleIds)
            ->where(function ($query) use ($actionIds) {
                $query->whereIn('action_id', $actionIds)
                    ->orWhereNull('action_id'); // Include rows where action_id is null
            })
            ->get()
            ->keyBy(function ($permission) {
                // Create unique key without trailing '|' if action_id is null
                return $permission->module_id . ($permission->action_id ? '|' . $permission->action_id : ''); 
            });

        foreach ($this->permissions as $permission) {
            // Extract the module and action IDs
            $moduleId = $permission->module->id;
            $actionId = $permission->action ? $permission->action->id : null; // Check if action is null

            // Create a unique key to check if the permission already exists
            $uniqueKey = $moduleId . ($actionId !== null ? '|' . $actionId : ''); // No trailing '|'


            if (isset($existingPermissions[$uniqueKey])) {
                // Update the existing permission with new attributes
                $existingPermissions[$uniqueKey]->update($this->attributes);
            } else {
                // Create a new permission for the model
                $this->model->permissions()->create(array_merge([
                    'module_id' => $moduleId,
                    'action_id' => $actionId, // This can be null
                ], $this->attributes));
            }
        }
    }

    // Sync method: add, update, and remove as necessary
    public function sync()
    {
        // Extract unique module and action IDs for the incoming permissions
        $moduleIds = $this->permissions->pluck('module.id')->unique();
        $actionIds = $this->permissions->pluck('action.id')->unique()->filter();

        // Fetch the current permissions assigned to the model
        $existingPermissions = $this->model->permissions()
            ->whereIn('module_id', $moduleIds)
            ->where(function ($query) use ($actionIds) {
                $query->whereIn('action_id', $actionIds)
                    ->orWhereNull('action_id');
            })
            ->get()
            ->keyBy(function ($permission) {
                return $permission->module_id . ($permission->action_id ? '|' . $permission->action_id : ''); 
            });

        // Extract the unique keys from the incoming permissions
        $incomingKeys = $this->permissions->map(function ($permission) {
            $moduleId = $permission->module->id;
            $actionId = $permission->action ? $permission->action->id : null;
            return $moduleId . ($actionId !== null ? '|' . $actionId : '');
        });

        // Find permissions to detach (currently assigned but not in incoming)
        $permissionsToDetach = $existingPermissions->keys()->diff($incomingKeys);

        if ($permissionsToDetach->isNotEmpty()) {
            foreach ($permissionsToDetach as $key) {
                [$moduleId, $actionId] = explode('|', $key) + [null, null];
                $this->model->permissions()
                    ->where('module_id', $moduleId)
                    ->when($actionId, function ($query) use ($actionId) {
                        $query->where('action_id', $actionId);
                    }, function ($query) {
                        $query->whereNull('action_id');
                    })
                    ->delete();
            }
        }

        // Now handle the creation and updating of incoming permissions
        foreach ($this->permissions as $permission) {
            $moduleId = $permission->module->id;
            $actionId = $permission->action ? $permission->action->id : null;

            $uniqueKey = $moduleId . ($actionId !== null ? '|' . $actionId : '');

            if (isset($existingPermissions[$uniqueKey])) {
                // Update the existing permission
                $existingPermissions[$uniqueKey]->update($this->attributes);
            } else {
                // Create a new permission
                $this->model->permissions()->create(array_merge([
                    'module_id' => $moduleId,
                    'action_id' => $actionId,
                ], $this->attributes));
            }
        }
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