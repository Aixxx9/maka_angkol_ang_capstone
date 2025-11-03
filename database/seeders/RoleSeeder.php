<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure our two roles exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole  = Role::firstOrCreate(['name' => 'user']);

        // Backward compatibility: keep any existing super-admin as also an admin
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin && !$superAdmin->wasRecentlyCreated) {
            // Nothing to do; routes/UI also check for super-admin where helpful
        }

        // Seed a default admin account if not present
        $user = User::firstOrCreate(
            ['email' => 'admin@prisaa.test'],
            ['name' => 'Admin', 'password' => Hash::make('password')]
        );
        if (!$user->hasRole('admin')) {
            $user->assignRole($adminRole);
        }
    }
}
