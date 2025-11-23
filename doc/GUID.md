# Laravel Rakshak

Laravel Rakshak is a flexible, modular, and powerful Roles & Permissions management package for Laravel applications.  
It provides role assignment, module-based permissions, action-based control, and middleware integration with full configurability.

---

## Features

-   Flexible role and permission system
-   Supports Laravel 9, 10, and 11
-   Module → Action based permissions
-   Assign multiple roles to any model
-   Optional role validity (valid_from, valid_till, daily time windows)
-   Includes `RoleMiddleware` and `PermissionMiddleware`
-   Caching support
-   Easily configurable models, tables, and middleware
-   Artisan commands for modules, actions, permissions, and syncing
-   Works with polymorphic relationships (model_has_roles)

---

## Installation

Install with Composer:

```bash
composer require roshify/laravel-rakshak
```

For local development:

```bash
composer config repositories.rakshak path ./packages/laravel-rakshak
composer require roshify/laravel-rakshak:dev-main
```

---

## Publish Configuration & Migrations

```bash
php artisan vendor:publish --provider="Roshify\LaravelRakshak\LaravelRakshakServiceProvider"
```

Then run migrations:

```bash
php artisan migrate
```

---

## Configuration

The config file is located at `config/rakshak.php`.

### Table Names

```php
'table_names' => [
    'roles' => 'roles',
    'permissions' => 'permissions',
    'modules' => 'modules',
    'actions' => 'actions',
    'model_has_roles' => 'model_has_roles',
],
```

### Models

```php
'models' => [
    'role' => \Roshify\LaravelRakshak\Models\Role::class,
    'permission' => \Roshify\LaravelRakshak\Models\Permission::class,
    'module' => \Roshify\LaravelRakshak\Models\Module::class,
    'action' => \Roshify\LaravelRakshak\Models\Action::class,
    'model_has_role' => \Roshify\LaravelRakshak\Models\ModelHasRole::class,
],
```

### Middleware Aliases

```php
'middleware_aliases' => [
    'role' => 'rakshak.role',
    'permission' => 'rakshak.permission',
],
```

### Super Admin

```php
'super_admin' => [
    'enabled' => true,
    'role_name' => 'super-admin',
],
```

---

## Usage

### Add Traits to Your User Model

```php
use Roshify\LaravelRakshak\Traits\HasRoles;
use Roshify\LaravelRakshak\Traits\HasPermissions;

class User extends Model
{
    use HasRoles, HasPermissions;
}
```

---

## Assigning Roles

```php
$user->assignRole('admin');
$user->assignRole(['editor', 'manager']);
```

### Assign Role With Validity

```php
$user->assignRole('shift-manager')
    ->validFrom('2025-01-01')
    ->validTill('2025-01-10')
    ->dailyBetween('09:00', '18:00')
    ->save();
```

---

## Checking Roles

```php
$user->hasRole('admin');
$user->hasAnyRole(['admin', 'editor']);
$user->hasAllRoles(['admin', 'supervisor']);
```

---

## Checking Permissions

Permission formats:

-   `m1`
-   `m1:a1`
-   `m1:a1,a2`
-   `['m1', 'm2']`
-   `['m1:a1,a2', 'm2:a1']`

Examples:

```php
$user->hasPermission('users:view');
$user->hasAnyPermission(['users:view', 'users:edit']);
$user->hasAllPermissions(['users:view', 'users:edit']);
```

---

## Middleware

### Role Middleware

```php
Route::get('/admin', function () {
    return 'Admin Panel';
})->middleware('rakshak.role:admin');
```

### Permission Middleware

```php
Route::post('/users/create', function () {
    return 'Create user';
})->middleware('rakshak.permission:users:create');
```

---

## Artisan Commands

| Command           | Description              |
| ----------------- | ------------------------ |
| `rakshak:modules` | List modules and actions |
| `rakshak:actions` | List actions             |
| `rakshak:roles`   | Manage roles             |
| `rakshak:sync`    | Sync modules and actions |

Examples:

```bash
php artisan rakshak:modules
php artisan rakshak:sync --force
```

---

## Package Structure

```
src/
 ├── Models/
 ├── Traits/
 ├── Http/
 │    └── Middleware/
 ├── Commands/
 ├── Providers/
 ├── Database/
 │    └── migrations/
 └── Facades/Rakshak.php
```

---

## License

Laravel Rakshak is open-source software licensed under the MIT license.
