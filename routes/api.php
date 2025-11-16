<?php

use Illuminate\Support\Facades\Route;
use Roshp\LaravelRakshak\Http\Controllers\RoleController;

Route::apiResource('roles', RoleController::class);