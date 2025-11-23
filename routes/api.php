<?php

use Illuminate\Support\Facades\Route;
use Roshify\LaravelRakshak\Http\Controllers\RoleController;

Route::apiResource('roles', RoleController::class);