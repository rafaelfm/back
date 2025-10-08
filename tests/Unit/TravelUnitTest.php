<?php

namespace Tests\Unit;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\TravelRequest;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TravelUnitTest extends TestCase
{
    public function test_location_label_combines_city_state_code_and_country(): void
    {
        $travelRequest = new TravelRequest();
        $travelRequest->setRelation('city', tap(new City(['name' => 'São Paulo']), function (City $city) {
            $city->setRelation('state', new State(['code' => 'SP']));
            $city->setRelation('country', new Country(['name' => 'Brasil']));
        }));

        $this->assertSame('São Paulo, SP, Brasil', $travelRequest->location_label);
    }

    public function test_location_label_falls_back_to_state_name_when_code_missing(): void
    {
        $travelRequest = new TravelRequest();
        $travelRequest->setRelation('city', tap(new City(['name' => 'Porto']), function (City $city) {
            $city->setRelation('state', new State(['name' => 'Porto District']));
            $city->setRelation('country', new Country(['name' => 'Portugal']));
        }));

        $this->assertSame('Porto, Porto District, Portugal', $travelRequest->location_label);
    }

    public function test_location_label_is_empty_when_city_not_loaded(): void
    {
        $travelRequest = new TravelRequest();
        $travelRequest->setRelation('city', null);

        $this->assertSame('', $travelRequest->location_label);
    }

    public function test_dates_are_cast_to_carbon_instances(): void
    {
        $travelRequest = new TravelRequest([
            'departure_date' => '2024-07-01',
            'return_date' => '2024-07-15',
        ]);

        $this->assertInstanceOf(Carbon::class, $travelRequest->departure_date);
        $this->assertInstanceOf(Carbon::class, $travelRequest->return_date);
    }

    public function test_appended_location_label_is_present_in_arrayable_output(): void
    {
        $travelRequest = new TravelRequest();
        $travelRequest->setRelation('city', tap(new City(['name' => 'Curitiba']), function (City $city) {
            $city->setRelation('state', new State(['code' => 'PR']));
            $city->setRelation('country', new Country(['name' => 'Brasil']));
        }));

        $payload = $travelRequest->toArray();

        $this->assertArrayHasKey('location_label', $payload);
        $this->assertSame('Curitiba, PR, Brasil', $payload['location_label']);
    }
}
