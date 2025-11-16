<?php

namespace Roshp\LaravelRakshak;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Roshp\LaravelRakshak\Facades\Rakshak as FacadesRakshak;
use Roshp\LaravelRakshak\Rakshak;

class LaravelRakshakServiceProvider extends ServiceProvider 
{
    public function register()
    {
        // Register the main Rakshak class in the service container
        $this->app->singleton('rakshak', function () {
            return new Rakshak; // Replace this with your main package class logic
        });

        // Register the facade alias without changing the main project
        $this->app->alias('rakshak', FacadesRakshak::class);
    }

    public function boot()
    {
        // Load package routes
        Route::prefix('laravel-rakshak')
            ->as('laravel-rakshak')
            ->group(function () {
                $this->loadRoutesFrom(__DIR__. '/../routes/api.php');
            });

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Publish config file
        $this->publishes([
            __DIR__.'/../config/rakshak.php' => config_path('rakshak.php'),
        ]);

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Roshp\LaravelRakshak\Console\ManageRoleCommand::class,
                \Roshp\LaravelRakshak\Console\ManageActionCommand::class,
                \Roshp\LaravelRakshak\Console\ManageModuleCommand::class,
                \Roshp\LaravelRakshak\Console\ListRakshakItemsCommand::class,
            ]);
        }
    }
}