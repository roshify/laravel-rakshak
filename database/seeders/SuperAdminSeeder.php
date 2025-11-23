<?php
declare(strict_types=1);

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** Example: create/ensure super-admin user and assign role */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Resolve user model class from auth config; fallback to App\Models\User if exists
        $userModelClass = config('auth.providers.users.model')
            ?? (class_exists(\App\Models\User::class) ? \App\Models\User::class : null);

        if (! $userModelClass) {
            $this->command->error('Unable to resolve user model. Set auth.providers.users.model in config/auth.php.');
            return;
        }

        /** @var \Illuminate\Database\Eloquent\Model $userModelInstance */
        $userModelInstance = app($userModelClass);

        // Build a query builder from the model instance
        $query = $userModelInstance->newQuery();

        $adminEmail = env('RAKSHAK_SUPERADMIN_EMAIL', 'admin@example.com');
        $adminPassword = env('RAKSHAK_SUPERADMIN_PASSWORD', 'ChangeMe123!');

        $user = $query->firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Super Admin',
                'password' => Hash::make($adminPassword),
                // add other required fields here if your User model requires them
            ]
        );

        // Assign role using package API (use assignRole or pivot attach depending on your package)
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('super-admin');
        } else {
            // fallback: attach by pivot if your package exposes roles relation
            if (method_exists($user, 'roles')) {
                $roleClass = config('rakshak.models.role', \Roshify\LaravelRakshak\Models\Role::class);
                $role = $roleClass::firstWhere('slug', 'super-admin');
                if ($role) {
                    $user->roles()->syncWithoutDetaching([$role->id]);
                }
            }
        }

        $this->command->info("Super admin ensured: {$adminEmail}");
    }
}
