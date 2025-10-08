<?php

namespace Database\Factories;

use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TravelRequestFactory extends Factory
{
    protected $model = TravelRequest::class;

    public function definition(): array
    {
        $departure = fake()->dateTimeBetween('+2 days', '+2 months');
        $returnDate = (clone $departure)->modify('+'.fake()->numberBetween(2, 10).' days');

        return [
            'user_id' => User::factory(),
            'requester_name' => fake()->name(),
            'destination' => fake()->city().', '.fake()->country(),
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
