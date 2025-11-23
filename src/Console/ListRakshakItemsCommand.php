<?php

namespace Roshify\LaravelRakshak\Console;

use Illuminate\Console\Command;
use Roshify\LaravelRakshak\Models\Module;
use Roshify\LaravelRakshak\Models\Action;
use Roshify\LaravelRakshak\Models\Role;

class ListRakshakItemsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rakshak:list 
                            {--m|modules : List all modules}
                            {--a|actions : List all actions}
                            {--r|roles : List all roles}
                            {--am : List modules and actions}
                            {--amr : List modules, actions, and roles}
                            {--A : Alias for listing all: modules, actions, and roles}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all modules, actions, roles, or a combination in the system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Determine which options are selected
        $listModules = $this->option('modules');
        $listActions = $this->option('actions');
        $listRoles = $this->option('roles');
        $listModulesAndActions = $this->option('am');
        $listAll = $this->option('amr') || $this->option('A');

        // If no options are provided, default to listing all (modules, actions, roles)
        if (!$listModules && !$listActions && !$listRoles && !$listModulesAndActions && !$listAll) {
            $listAll = true;  // Set listAll to true by default if no options are selected
        }

        // If --amr or -A is passed (or defaulted), show modules, actions, and roles
        if ($listAll) {
            $this->listModules();
            $this->listActions();
            $this->listRoles();
            return;
        }

        // If --am is passed, show both modules and actions
        if ($listModulesAndActions) {
            $this->listModules();
            $this->listActions();
            return;
        }

        // Show based on individual options
        if ($listModules) {
            $this->listModules();
        }
        if ($listActions) {
            $this->listActions();
        }
        if ($listRoles) {
            $this->listRoles();
        }
    }

    /**
     * List all modules in tabular form.
     */
    protected function listModules()
    {
        $modules = Module::all(['id', 'name', 'slug']);
        if ($modules->isEmpty()) {
            $this->warn('No modules found.');
        } else {
            $this->info('Listing all modules:');
            $this->table(['ID', 'Name', 'Slug'], $modules->toArray());
        }
    }

    /**
     * List all actions in tabular form.
     */
    protected function listActions()
    {
        $actions = Action::all(['id', 'name', 'slug']);
        if ($actions->isEmpty()) {
            $this->warn('No actions found.');
        } else {
            $this->info('Listing all actions:');
            $this->table(['ID', 'Name', 'Slug'], $actions->toArray());
        }
    }

    /**
     * List all roles in tabular form.
     */
    protected function listRoles()
    {
        $roles = Role::all(['id', 'name', 'slug']);
        if ($roles->isEmpty()) {
            $this->warn('No roles found.');
        } else {
            $this->info('Listing all roles:');
            $this->table(['ID', 'Name', 'Slug'], $roles->toArray());
        }
    }
}
