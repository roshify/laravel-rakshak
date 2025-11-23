<?php

namespace Roshify\LaravelRakshak;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Roshify\LaravelRakshak\Console\ListRakshakItemsCommand;
use Roshify\LaravelRakshak\Console\ManageActionCommand;
use Roshify\LaravelRakshak\Console\ManageModuleCommand;
use Roshify\LaravelRakshak\Console\ManageRoleCommand;
use Roshify\LaravelRakshak\Http\Middleware\PermissionMiddleware;
use Roshify\LaravelRakshak\Http\Middleware\RoleMiddleware;

class LaravelRakshakServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package config with application config
        $this->mergeConfigFrom(
            __DIR__.'/../config/rakshak.php',
            'rakshak'
        );

        // Register the main Rakshak class in the service container
        $this->app->singleton('rakshak', function ($app) {
            return new Rakshak();
        });

        // Register the facade alias
        $this->app->alias('rakshak', Rakshak::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register publishable resources
        $this->registerPublishables();

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register middleware
        $this->registerMiddleware();

        // Register routes if enabled
        $this->registerRoutes();

        // Register commands
        $this->registerCommands();
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishables(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__.'/../config/rakshak.php' => config_path('rakshak.php'),
            ], 'rakshak-config');

            // Publish migrations
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'rakshak-migrations');

            // Publish seeders
            $this->publishes([
                __DIR__.'/../database/seeders' => database_path('seeders'),
            ], 'rakshak-seeders');

            // Publish all
            $this->publishes([
                __DIR__.'/../config/rakshak.php' => config_path('rakshak.php'),
                __DIR__.'/../database/migrations' => database_path('migrations'),
                __DIR__.'/../database/seeders' => database_path('seeders'),
            ], 'rakshak');
        }
    }

    /**
     * Register the package middleware.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        // Register middleware with configured aliases
        $middlewareAliases = config('rakshak.middleware_aliases', [
            'role' => 'rakshak.role',
            'permission' => 'rakshak.permission',
        ]);

        $middlewareClasses = config('rakshak.middleware', [
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);

        // Register role middleware
        if (isset($middlewareAliases['role']) && isset($middlewareClasses['role'])) {
            $router->aliasMiddleware($middlewareAliases['role'], $middlewareClasses['role']);
        }

        // Register permission middleware
        if (isset($middlewareAliases['permission']) && isset($middlewareClasses['permission'])) {
            $router->aliasMiddleware($middlewareAliases['permission'], $middlewareClasses['permission']);
        }
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        // Only load routes if enabled in config
        if (config('rakshak.routes.enabled', false)) {
            $routeConfig = config('rakshak.routes', []);

            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }
    }

    /**
     * Register the package's commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ManageRoleCommand::class,
                ManageActionCommand::class,
                ManageModuleCommand::class,
                ListRakshakItemsCommand::class,
            ]);
        }
    }
}
