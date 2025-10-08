<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\TravelRequest;
use App\Models\User;
use App\Notifications\TravelRequestStatusChanged;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
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

        $country = Country::create([
            'name' => 'Brasil',
            'code' => 'BR',
            'slug' => 'brasil',
        ]);

        $state = State::create([
            'country_id' => $country->id,
            'name' => 'São Paulo',
            'code' => 'SP',
            'slug' => 'sao-paulo',
        ]);

        $city = City::create([
            'country_id' => $country->id,
            'state_id' => $state->id,
            'name' => 'São Paulo',
            'slug' => 'sao-paulo-city',
        ]);

        $payload = [
            'requester_name' => $user->name,
            'city_id' => $city->id,
            'departure_date' => now()->addWeek()->toDateString(),
            'return_date' => now()->addWeeks(2)->toDateString(),
            'notes' => 'Reunião com cliente',
        ];

        $this->postJson('/api/travel-requests', $payload, [
            'Authorization' => "Bearer {$token}",
        ])->assertCreated()
            ->assertJsonPath('data.city_id', $city->id)
            ->assertJsonPath('data.location_label', 'São Paulo, SP, Brasil');

        TravelRequest::factory()->count(12)->create([
            'user_id' => $user->id,
            'city_id' => $city->id,
            'status' => 'requested',
        ]);

        $this->getJson('/api/travel-requests', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk()
            ->assertJsonPath('data.0.location_label', 'São Paulo, SP, Brasil')
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.current_page', 1);

        $this->getJson('/api/travel-requests?location=são%20paulo&per_page=5&page=2', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk()
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data')
                     ->where('data', fn ($data) => count($data) > 0)
                     ->etc()
            );
    }

    public function test_user_can_filter_travel_requests_by_departure_and_return_dates(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usuario');
        $token = $this->jwtFor($user);

        $city = $this->createCity('Rio de Janeiro', 'Rio de Janeiro', 'Brasil', 'RJ', 'BR');

        $baseDate = Carbon::now()->startOfDay();

        $matchingDeparture = $baseDate->copy()->addDays(30)->toDateString();
        $matchingReturn = $baseDate->copy()->addDays(40)->toDateString();
        $matchingDepartureIso = Carbon::parse($matchingDeparture)->toJSON();
        $matchingReturnIso = Carbon::parse($matchingReturn)->toJSON();

        TravelRequest::create([
            'user_id' => $user->id,
            'city_id' => $city->id,
            'requester_name' => $user->name,
            'departure_date' => $baseDate->copy()->addDays(5)->toDateString(),
            'return_date' => $baseDate->copy()->addDays(10)->toDateString(),
            'status' => 'requested',
            'notes' => null,
        ]);

        $matching = TravelRequest::create([
            'user_id' => $user->id,
            'city_id' => $city->id,
            'requester_name' => $user->name,
            'departure_date' => $matchingDeparture,
            'return_date' => $matchingReturn,
            'status' => 'requested',
            'notes' => null,
        ]);

        TravelRequest::create([
            'user_id' => $user->id,
            'city_id' => $city->id,
            'requester_name' => $user->name,
            'departure_date' => $baseDate->copy()->addDays(70)->toDateString(),
            'return_date' => $baseDate->copy()->addDays(80)->toDateString(),
            'status' => 'requested',
            'notes' => null,
        ]);

        $response = $this->getJson(sprintf(
            '/api/travel-requests?departure_from=%s&departure_to=%s&return_from=%s&return_to=%s',
            $baseDate->copy()->addDays(25)->toDateString(),
            $baseDate->copy()->addDays(35)->toDateString(),
            $baseDate->copy()->addDays(38)->toDateString(),
            $baseDate->copy()->addDays(45)->toDateString(),
        ), [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('data.0.departure_date', $matchingDepartureIso)
            ->assertJsonPath('data.0.return_date', $matchingReturnIso);
    }

    public function test_admin_can_list_travel_requests_for_all_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $userOne = User::factory()->create();
        $userOne->assignRole('usuario');

        $userTwo = User::factory()->create();
        $userTwo->assignRole('usuario');

        $city = $this->createCity('Fortaleza', 'Ceara', 'Brasil', 'CE', 'BR');

        $firstRequest = TravelRequest::create([
            'user_id' => $userOne->id,
            'city_id' => $city->id,
            'requester_name' => $userOne->name,
            'departure_date' => Carbon::now()->addDays(5)->toDateString(),
            'return_date' => Carbon::now()->addDays(10)->toDateString(),
            'status' => 'requested',
            'notes' => null,
        ]);

        $secondRequest = TravelRequest::create([
            'user_id' => $userTwo->id,
            'city_id' => $city->id,
            'requester_name' => $userTwo->name,
            'departure_date' => Carbon::now()->addDays(15)->toDateString(),
            'return_date' => Carbon::now()->addDays(20)->toDateString(),
            'status' => 'requested',
            'notes' => null,
        ]);

        $token = $this->jwtFor($admin);

        $this->getJson('/api/travel-requests', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data', 2)
                ->where('data', fn ($data) => collect($data)->pluck('id')->contains($firstRequest->id)
                    && collect($data)->pluck('id')->contains($secondRequest->id))
                ->etc()
            );
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

    private function createCity(
        ?string $cityName = null,
        ?string $stateName = null,
        ?string $countryName = null,
        ?string $stateCode = null,
        ?string $countryCode = null,
    ): City {
        $cityName ??= 'City '.Str::random(6);
        $stateName ??= 'State '.Str::random(6);
        $countryName ??= 'Country '.Str::random(6);

        $country = Country::create([
            'name' => $countryName,
            'code' => $countryCode,
            'slug' => Str::slug($countryName.'-'.Str::random(8)),
        ]);

        $state = State::create([
            'country_id' => $country->id,
            'name' => $stateName,
            'code' => $stateCode,
            'slug' => Str::slug($stateName.'-'.Str::random(8)),
        ]);

        return City::create([
            'country_id' => $country->id,
            'state_id' => $state->id,
            'name' => $cityName,
            'slug' => Str::slug($cityName.'-'.Str::random(8)),
        ]);
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
