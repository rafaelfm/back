<?php

namespace Tests\Unit;

use App\Models\TravelRequest;
use App\Models\User;
use App\TravelRequests\Exceptions\InvalidTravelRequestTransitionException;
use App\TravelRequests\Exceptions\UnauthorizedTravelRequestTransitionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TravelRequestStateTest extends TestCase
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

    public function test_regular_user_cannot_approve_requested_request(): void
    {
        $travelRequest = TravelRequest::factory()->create(['status' => 'requested']);

        $user = User::factory()->create();
        $user->assignRole('usuario');

        $this->assertFalse($travelRequest->canTransitionStatusTo('approved', $user));

        $this->expectException(UnauthorizedTravelRequestTransitionException::class);
        $travelRequest->transitionStatusTo('approved', $user);
    }

    public function test_admin_can_approve_requested_request(): void
    {
        $travelRequest = TravelRequest::factory()->create(['status' => 'requested']);

        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $this->assertTrue($travelRequest->canTransitionStatusTo('approved', $admin));

        $travelRequest->transitionStatusTo('approved', $admin);

        $this->assertSame('approved', $travelRequest->refresh()->status);
    }

    public function test_approved_request_cannot_be_cancelled(): void
    {
        $travelRequest = TravelRequest::factory()->create(['status' => 'approved']);

        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $this->expectException(InvalidTravelRequestTransitionException::class);
        $travelRequest->ensureStatusTransitionPossible('cancelled', $admin);
    }
}

