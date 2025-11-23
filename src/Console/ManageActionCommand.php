<?php

namespace Roshify\LaravelRakshak\Console;

use Illuminate\Console\Command;
use Roshify\LaravelRakshak\Facades\Rakshak;
use Roshify\LaravelRakshak\Models\Action;

class ManageActionCommand extends Command
{
    protected $signature = 'rakshak:manage-action {action : "create" or "delete"} {name : The name of the action}';
    protected $description = 'Create or Delete an action by providing name';

    public function handle()
    {
        $action = $this->argument('action');
        $name = $this->argument('name');

        if ($action === 'create') {
            $this->createAction($name);
        } elseif ($action === 'delete') {
            $this->deleteAction($name);
        } else {
            $this->error("Invalid action. Please use 'create' or 'delete'.");
        }
    }

    protected function createAction($name)
    {
        if (Action::where('name', $name)->exists()) {
            $this->error("Action with name '{$name}' already exists.");
        } else {
            $action = Rakshak::createAction($name);
            $this->info("Action '{$action->name}' created successfully.");
        }
    }

    protected function deleteAction($name)
    {
        $action = Action::where('name', $name)->first();

        if (!$action) {
            $this->error("Action with name '{$name}' does not exist.");
        } else {
            $action->delete();
            $this->info("Action '{$action->name}' deleted successfully.");
        }
    }
}
