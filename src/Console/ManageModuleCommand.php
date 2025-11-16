<?php

namespace Roshp\LaravelRakshak\Console;

use Illuminate\Console\Command;
use Roshp\LaravelRakshak\Facades\Rakshak;
use Roshp\LaravelRakshak\Models\Module;

class ManageModuleCommand extends Command
{
    protected $signature = 'rakshak:manage-module {action : "create" or "delete"} {name : The name of the module}';
    protected $description = 'Create or Delete an module by providing name.';

    public function handle()
    {
        $action = $this->argument('action');
        $name = $this->argument('name');

        if ($action === 'create') {
            $this->createModule($name);
        } elseif ($action === 'delete') {
            $this->deleteModule($name);
        } else {
            $this->error("Invalid action. Please use 'create' or 'delete'.");
        }
    }

    protected function createModule($name)
    {
        if (Module::where('name', $name)->exists()) {
            $this->error("Module with name '{$name}' already exists.");
        } else {
            $module = Rakshak::createModule($name);
            $this->info("Module '{$module->name}' created successfully.");
        }
    }

    protected function deleteModule($name)
    {
        $module = Module::where('name', $name)->first();

        if (!$module) {
            $this->error("Module with name '{$name}' does not exist.");
        } else {
            $module->delete();
            $this->info("Module '{$module->name}' deleted successfully.");
        }
    }
}
