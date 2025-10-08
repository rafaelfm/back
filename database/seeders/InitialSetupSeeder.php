<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InitialSetupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $databaseName = 'banco';

        Config::set('database.connections.mysql.database', null);
        DB::purge('mysql');
        DB::reconnect('mysql');

        DB::statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        Config::set('database.connections.mysql.database', $databaseName);
        DB::purge('mysql');
        DB::reconnect('mysql');

        Artisan::call('migrate', ['--force' => true]);

        $permissions = [
            'travel.create',
            'travel.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $roles = [
            'administrador' => $permissions,
            'usuario' => ['travel.create'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->givePermissionTo($rolePermissions);
        }

        $users = [
            [
                'name' => 'Administrador',
                'email' => 'admin@gmail.com',
                'role' => 'administrador',
            ],
            [
                'name' => 'Usuario',
                'email' => 'usuario@gmail.com',
                'role' => 'usuario',
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $data['email'],
                ],
            );

            $user->syncRoles([$data['role']]);
        }
    }
}
