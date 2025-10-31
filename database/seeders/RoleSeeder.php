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
$super = Role::firstOrCreate(['name' => 'super-admin']);
$mod = Role::firstOrCreate(['name' => 'mod']);


$user = User::firstOrCreate(
['email' => 'admin@prisaa.test'],
['name' => 'Super Admin','password' => Hash::make('password')]
);
$user->assignRole($super);
}
}