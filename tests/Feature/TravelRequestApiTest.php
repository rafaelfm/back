<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\User;
use App\Notifications\TravelRequestStatusChanged;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TravelRequestApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'travel.create']);
        Permission::firstOrCreate(['name' => 'travel.manage']);

        Role::firstOrCreate(['name' => 'usuario'])->givePermissionTo(['travel.create']);
        Role::firstOrCreate(['name' => 'administrador'])->givePermissionTo(['travel.create', 'travel.manage']);
    }

    public function test_user_can_create_and_list_travel_requests(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usuario');

        $token = $this->jwtFor($user);

        $payload = [
            'requester_name' => $user->name,
            'destination' => 'São Paulo, Brasil',
            'departure_date' => now()->addWeek()->toDateString(),
            'return_date' => now()->addWeeks(2)->toDateString(),
            'notes' => 'Reunião com cliente',
        ];

        $this->postJson('/api/travel-requests', $payload, [
            'Authorization' => "Bearer {$token}",
        ])->assertCreated()
            ->assertJsonPath('data.destination', 'São Paulo, Brasil');

        $this->getJson('/api/travel-requests', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk()
            ->assertJsonPath('data.0.destination', 'São Paulo, Brasil');
    }

    public function test_admin_can_update_status_and_send_notification(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $owner = User::factory()->create();
        $owner->assignRole('usuario');

        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => 'requested',
        ]);

        $token = $this->jwtFor($admin);

        $this->patchJson("/api/travel-requests/{$travelRequest->id}/status", [
            'status' => 'approved',
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'status' => 'approved',
        ]);

        Notification::assertSentTo($owner, TravelRequestStatusChanged::class);
    }

    public function test_regular_user_cannot_update_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usuario');

        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
        ]);

        $token = $this->jwtFor($user);

        $this->patchJson("/api/travel-requests/{$travelRequest->id}/status", [
            'status' => 'approved',
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertForbidden();
    }

    private function jwtFor(User $user): string
    {
        $key = config('app.key');

        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return JWT::encode([
            'iss' => config('app.url'),
            'sub' => $user->getKey(),
            'iat' => now()->timestamp,
            'exp' => now()->addHour()->timestamp,
        ], $key, 'HS256');
    }
}
