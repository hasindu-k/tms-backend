<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $managerWeb = Role::firstOrCreate(['name' => 'manager']);
        $managerApi = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'api']);
        $userWeb = Role::firstOrCreate(['name' => 'user']);
        $userApi = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
    }
}
