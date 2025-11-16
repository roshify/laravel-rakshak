<?php

namespace Roshp\LaravelRakshak\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void assignRole(\Illuminate\Database\Eloquent\Model $model, string | \Roshp\LaravelRakshak\Models\Role $role) Assign a role to the given model.
 * @method static void assignPermission(\Illuminate\Database\Eloquent\Model $model, string | \Roshp\LaravelRakshak\Models\Role $role) Assign a role to the given model.
 */

class Rakshak extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'rakshak';
    }
}