<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\City;
use App\Models\TravelRequest;
use Database\Seeders\DestinationSeeder;
use Illuminate\Support\Arr;
use function now;
use function fake;
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

        $this->call(DestinationSeeder::class);

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
            ],[
                'name' => 'Teste',
                'email' => 'teste@gmail.com',
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

        $testUser = User::where('email', 'teste@gmail.com')->first();

        if ($testUser) {
            $existing = $testUser->travelRequests()->count();
            $toCreate = max(0, 50 - $existing);
            $cities = City::with(['state', 'country'])->get();

            if ($toCreate > 0 && $cities->isNotEmpty()) {
                $preferredCities = ['Curitiba', 'São Paulo', 'Rio de Janeiro', 'Brasília'];

                foreach ($preferredCities as $preferred) {
                    if ($toCreate <= 0) {
                        break;
                    }

                    $city = $cities->firstWhere('name', $preferred);

                    if ($city) {
                        $this->createTravelRequest($testUser, $city);
                        $toCreate--;
                    }
                }

                while ($toCreate > 0) {
                    $city = $cities->random();
                    $this->createTravelRequest($testUser, $city);
                    $toCreate--;
                }
            }
        }
    }

    private function createTravelRequest(User $user, City $city): void
    {
        $departure = now()->addDays(fake()->numberBetween(2, 120));
        $returnDate = (clone $departure)->addDays(fake()->numberBetween(2, 10));

        TravelRequest::create([
            'user_id' => $user->id,
            'city_id' => $city->id,
            'requester_name' => $user->name,
            'departure_date' => $departure->format('Y-m-d'),
            'return_date' => $returnDate->format('Y-m-d'),
            'status' => Arr::random(['requested', 'approved', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
        ]);
    }
}
