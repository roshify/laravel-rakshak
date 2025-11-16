<?php

namespace Roshp\LaravelRakshak\Console;

use Illuminate\Console\Command;
use Roshp\LaravelRakshak\Facades\Rakshak;
use Roshp\LaravelRakshak\Models\Role;

class ManageRoleCommand extends Command
{
    protected $signature = 'rakshak:manage-role {action : "create" or "delete"} {name : The name of the role}';
    protected $description = 'Create or Delete an role by providing name.';

    public function handle()
    {
        $action = $this->argument('action');
        $name = $this->argument('name');

        if ($action === 'create') {
            $this->createRole($name);
        } elseif ($action === 'delete') {
            $this->deleteRole($name);
        } else {
            $this->error("Invalid action. Please use 'create' or 'delete'.");
        }
    }

    protected function createRole($name)
    {
        if (Role::where('name', $name)->exists()) {
            $this->error("Role with name '{$name}' already exists.");
        } else {
            $role = Rakshak::createRole($name);
            $this->info("Role '{$role->name}' created successfully.");
        }
    }

    protected function deleteRole($name)
    {
        $role = Role::where('name', $name)->first();

        if (!$role) {
            $this->error("Role with name '{$name}' does not exist.");
        } else {
            $role->delete();
            $this->info("Role '{$role->name}' deleted successfully.");
        }
    }
}
