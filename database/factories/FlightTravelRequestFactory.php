<?php

namespace Database\Factories;

use App\Models\Flight;
use App\Models\FlightTravelRequest;
use App\Models\TravelRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class FlightTravelRequestFactory extends Factory
{
    protected $model = FlightTravelRequest::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'flight_id' => Flight::factory(),
            'travel_request_id' => TravelRequest::factory(),
        ];
    }
}
