<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TravelRequestFactory extends Factory
{
    protected $model = TravelRequest::class;

    public function definition(): array
    {
        $departure = fake()->dateTimeBetween('+2 days', '+2 months');
        $returnDate = (clone $departure)->modify('+'.fake()->numberBetween(2, 10).' days');

        $country = Country::query()->inRandomOrder()->first();

        if (! $country) {
            $countryName = fake()->unique()->country();
            $country = Country::create([
                'name' => $countryName,
                'code' => null,
                'slug' => Str::slug('factory-'.$countryName.'-'.Str::random(4)),
            ]);
        }

        $state = State::query()->where('country_id', $country->id)->inRandomOrder()->first();

        if (! $state) {
            $stateName = fake()->unique()->state();
            $state = State::create([
                'country_id' => $country->id,
                'name' => $stateName,
                'code' => null,
                'slug' => Str::slug('factory-'.$stateName.'-'.Str::random(4)),
            ]);
        }

        $cityName = fake()->unique()->city();

        $city = City::create([
            'country_id' => $country->id,
            'state_id' => $state->id,
            'name' => $cityName,
            'slug' => Str::slug('factory-'.$cityName.'-'.Str::random(4)),
        ]);

        return [
            'user_id' => User::factory(),
            'city_id' => $city->id,
            'requester_name' => fake()->name(),
            'departure_date' => $departure->format('Y-m-d'),
            'return_date' => $returnDate->format('Y-m-d'),
            'status' => 'requested',
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function approved(): self
    {
        return $this->state(fn () => ['status' => 'approved']);
    }

    public function cancelled(): self
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }
}
