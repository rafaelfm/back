<?php

namespace Database\Seeders;

use App\Models\FlightTravelRequest;
use Illuminate\Database\Seeder;

class FlightTravelRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FlightTravelRequest::factory(10)->create();
    }
}
