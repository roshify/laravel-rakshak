<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SimpleModuleActionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Use config-driven model classes so projects can override implementations
        $moduleClass = config('rakshak.models.module', \Roshify\LaravelRakshak\Models\Module::class);
        $actionClass = config('rakshak.models.action', \Roshify\LaravelRakshak\Models\Action::class);

        Schema::disableForeignKeyConstraints();
        // truncate for a clean seed - remove if you want idempotent behaviour
        $moduleClass::truncate();
        $actionClass::truncate();

        // 1) create canonical actions
        $actions = ['Create', 'Read', 'Update', 'Delete', 'Export', 'Approve/Deny', 'Payment'];
        $actionIds = [];
        foreach ($actions as $name) {
            $action = $actionClass::firstOrCreate(['name' => $name]);
            $actionIds[$name] = $action->id;
        }

        // 2) define a simplified module => allowed actions mapping
        $modules = [
            'Client Management' => ['Read', 'Update', 'Export'],
            'Document Management' => ['Create', 'Read', 'Update', 'Delete'],
            'Payments' => ['Read', 'Approve/Deny', 'Payment', 'Export'],
            'Reporting' => ['Read', 'Export'],
            'Dashboard' => ['Read'],
        ];

        // 3) create modules and attach allowed actions
        foreach ($modules as $moduleName => $allowedActionNames) {
            $module = $moduleClass::create(['name' => $moduleName]);

            // prepare sync data with timestamps
            $sync = [];
            foreach ($allowedActionNames as $actName) {
                if (isset($actionIds[$actName])) {
                    $sync[$actionIds[$actName]] = [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            if (!empty($sync)) {
                $module->actions()->sync($sync);
            }
        }

        Schema::enableForeignKeyConstraints();
    }
}