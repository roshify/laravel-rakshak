<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    | Customize DB table names to avoid collisions in host applications.
    */
    'table_names' => [
        'roles'           => env('RAKSHAK_TABLE_ROLES', 'roles'),
        'permissions'     => env('RAKSHAK_TABLE_PERMISSIONS', 'permissions'),
        'modules'         => env('RAKSHAK_TABLE_MODULES', 'modules'),
        'actions'         => env('RAKSHAK_TABLE_ACTIONS', 'actions'),
        'model_has_roles' => env('RAKSHAK_TABLE_MODEL_HAS_ROLES', 'model_has_roles'),
        'model_has_permissions' => env('RAKSHAK_TABLE_MODEL_HAS_PERMISSIONS', 'model_has_permissions'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Eloquent Model Classes
    |--------------------------------------------------------------------------
    | You can override these with your own model classes that extend the
    | package models (use FQCN).
    */
    'models' => [
        'role'            => \Roshify\LaravelRakshak\Models\Role::class,
        'permission'      => \Roshify\LaravelRakshak\Models\Permission::class,
        'module'          => \Roshify\LaravelRakshak\Models\Module::class,
        'action'          => \Roshify\LaravelRakshak\Models\Action::class,
        'model_has_role'  => \Roshify\LaravelRakshak\Models\ModelHasRole::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication / Guard
    |--------------------------------------------------------------------------
    */
    'guard' => env('RAKSHAK_GUARD', 'web'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    | Centralized cache settings for permissions/roles. Use 'store' to pick the
    | cache driver defined in cache.php. If enabled=false, caching is skipped.
    */
    'cache' => [
        'enabled'    => env('RAKSHAK_CACHE_ENABLED', true),
        'store'      => env('RAKSHAK_CACHE_STORE', null), // null = default cache store
        'prefix'     => env('RAKSHAK_CACHE_PREFIX', 'rakshak_permissions:'),
        'ttl'        => (int) env('RAKSHAK_CACHE_TTL', 3600), // seconds
        // When true, cache entries include the permission-source and model ids
        // helping avoid accidental stale results across precedence modes.
        'include_source_in_key' => env('RAKSHAK_CACHE_INCLUDE_SOURCE', true),
        // automatic invalidation behavior: when roles/permissions are changed.
        'invalidate_on_update' => env('RAKSHAK_CACHE_INVALIDATE_ON_UPDATE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Source / Priority
    |--------------------------------------------------------------------------
    | Describe which model(s) will be consulted for permission checks and
    | how to treat precedence. You can add custom sources (e.g., 'department').
    |
    | Modes:
    |  - 'cascade' : check each source in order and return true on first grant.
    |  - 'strict'  : only check the first configured source (no fallbacks).
    |  - 'first'   : use the first available source (e.g., first role) and only it.
    */
    'permission_source' => [
        // Order in which sources are consulted
        'priority' => [
            'user',         // direct user permissions (always first if present/flagged)
            'roles',        // all roles assigned to the user (iterated)
            'designation',  // single related model (if your app has it)
            // add custom keys here: 'department', 'team', etc.
        ],

        // 'cascade' | 'strict' | 'first'
        'mode' => env('RAKSHAK_PERMISSION_MODE', 'cascade'),

        // If true and user has direct permission entries (or user flag like
        // `is_role_modified`), treat user permissions as definitive and do not
        // check other sources (this mirrors your desired behavior).
        'user_direct_precedence' => env('RAKSHAK_USER_DIRECT_PRECEDENCE', true),

        // If you want an "empty override" behavior: when a user has direct
        // permission records but they are empty, treat that as an explicit
        // deny. Set to true only if you need explicit deny semantics.
        'empty_user_overrides_role' => env('RAKSHAK_EMPTY_USER_OVERRIDES_ROLE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Super Admin (global bypass)
    |--------------------------------------------------------------------------
    | If enabled, users with this role get access to everything.
    */
    'super_admin' => [
        'enabled'   => env('RAKSHAK_SUPER_ADMIN_ENABLED', true),
        'role_name' => env('RAKSHAK_SUPER_ADMIN_ROLE', 'super-admin'),
        // you may optionally check by role slug or config array of slugs
    ],

    /*
    |--------------------------------------------------------------------------
    | Negative / Explicit Deny support
    |--------------------------------------------------------------------------
    | If you plan to support negative permissions (deny entries), enable this.
    |
    | - explicit_deny_flag: when true, permission checks will consider deny
    |   entries before grants (deny takes precedence).
    */
    'deny' => [
        'enabled' => env('RAKSHAK_DENY_ENABLED', false),
        'deny_key' => 'deny', // how deny is stored/flagged on permission pivot
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware & Routes
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'classes' => [
            'role'       => \Roshify\LaravelRakshak\Http\Middleware\RoleMiddleware::class,
            'permission' => \Roshify\LaravelRakshak\Http\Middleware\PermissionMiddleware::class,
        ],
        // route alias names for registering in ServiceProvider
        'aliases' => [
            'role'       => 'rakshak.role',
            'permission' => 'rakshak.permission',
        ],
    ],

    'routes' => [
        'enabled'     => env('RAKSHAK_ROUTES_ENABLED', false),
        'prefix'      => env('RAKSHAK_ROUTES_PREFIX', 'rakshak'),
        'middleware'  => ['api'],
        'name_prefix' => 'rakshak.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exceptions (responses)
    |--------------------------------------------------------------------------
    */
    'exceptions' => [
        // HTTP status to return for unauthorized access
        'unauthorized_status_code' => (int) env('RAKSHAK_UNAUTHORIZED_STATUS', 403),

        // Default message for unauthorized responses (can be localized)
        'unauthorized_message'     => env('RAKSHAK_UNAUTHORIZED_MESSAGE', 'Unauthorized access.'),

        // If true, middleware returns JSON with consistent shape:
        // { "success": false, "message": "...", "code": 403 }
        'json_response'            => env('RAKSHAK_JSON_EXCEPTIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Publishing and Development Helpers
    |--------------------------------------------------------------------------
    */
    'publish' => [
        'config'     => true,
        'migrations' => true,
        'seeders'    => true,
        'views'      => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Event & Audit
    |--------------------------------------------------------------------------
    | Emit events on role/permission changes so consuming apps can respond
    | (e.g., clear caches, notify systems).
    */
    'events' => [
        'emit' => env('RAKSHAK_EMIT_EVENTS', true),
        'events' => [
            'role_assigned'    => \Roshify\LaravelRakshak\Events\RoleAssigned::class,
            'role_revoked'     => \Roshify\LaravelRakshak\Events\RoleRevoked::class,
            'permission_added' => \Roshify\LaravelRakshak\Events\PermissionAdded::class,
            'permission_removed' => \Roshify\LaravelRakshak\Events\PermissionRemoved::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing / Dev options
    |--------------------------------------------------------------------------
    | Helpers to speed up development, e.g. auto-run seeder or use sqlite.
    */
    'testing' => [
        'use_orchestra' => env('RAKSHAK_TESTS_USE_ORCHESTRA', true),
        'seed_with_default' => env('RAKSHAK_TESTS_SEED_DEFAULT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging & Debug
    |--------------------------------------------------------------------------
    */
    'debug' => [
        'log_changes' => env('RAKSHAK_LOG_CHANGES', false), // log to laravel log on changes
    ],
];
