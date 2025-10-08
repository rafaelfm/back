<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\Destination;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DestinationSeeder extends Seeder
{
    /**
     * Execute the database seeds.
     */
    public function run(): void
    {
        $entries = [
            [
                'country' => ['name' => 'Brasil', 'code' => 'BR'],
                'state' => ['name' => 'São Paulo', 'code' => 'SP'],
                'city' => 'São Paulo',
            ],
            [
                'country' => ['name' => 'Brasil', 'code' => 'BR'],
                'state' => ['name' => 'Rio de Janeiro', 'code' => 'RJ'],
                'city' => 'Rio de Janeiro',
            ],
            [
                'country' => ['name' => 'Brasil', 'code' => 'BR'],
                'state' => ['name' => 'Distrito Federal', 'code' => 'DF'],
                'city' => 'Brasília',
            ],
            [
                'country' => ['name' => 'Brasil', 'code' => 'BR'],
                'state' => ['name' => 'Minas Gerais', 'code' => 'MG'],
                'city' => 'Belo Horizonte',
            ],
            [
                'country' => ['name' => 'Brasil', 'code' => 'BR'],
                'state' => ['name' => 'Paraná', 'code' => 'PR'],
                'city' => 'Curitiba',
            ],
            [
                'country' => ['name' => 'Brasil', 'code' => 'BR'],
                'state' => ['name' => 'Rio Grande do Sul', 'code' => 'RS'],
                'city' => 'Porto Alegre',
            ],
            [
                'country' => ['name' => 'Brasil', 'code' => 'BR'],
                'state' => ['name' => 'Bahia', 'code' => 'BA'],
                'city' => 'Salvador',
            ],
            [
                'country' => ['name' => 'Brasil', 'code' => 'BR'],
                'state' => ['name' => 'Pernambuco', 'code' => 'PE'],
                'city' => 'Recife',
            ],
            [
                'country' => ['name' => 'Estados Unidos', 'code' => 'US'],
                'state' => ['name' => 'Nova York', 'code' => 'NY'],
                'city' => 'Nova York',
            ],
            [
                'country' => ['name' => 'Estados Unidos', 'code' => 'US'],
                'state' => ['name' => 'Califórnia', 'code' => 'CA'],
                'city' => 'Los Angeles',
            ],
            [
                'country' => ['name' => 'Portugal', 'code' => 'PT'],
                'state' => null,
                'city' => 'Lisboa',
            ],
            [
                'country' => ['name' => 'Portugal', 'code' => 'PT'],
                'state' => null,
                'city' => 'Porto',
            ],
            [
                'country' => ['name' => 'Espanha', 'code' => 'ES'],
                'state' => null,
                'city' => 'Madri',
            ],
            [
                'country' => ['name' => 'França', 'code' => 'FR'],
                'state' => null,
                'city' => 'Paris',
            ],
            [
                'country' => ['name' => 'Reino Unido', 'code' => 'GB'],
                'state' => ['name' => 'Inglaterra', 'code' => 'ENG'],
                'city' => 'Londres',
            ],
            [
                'country' => ['name' => 'Alemanha', 'code' => 'DE'],
                'state' => null,
                'city' => 'Berlim',
            ],
            [
                'country' => ['name' => 'Itália', 'code' => 'IT'],
                'state' => null,
                'city' => 'Roma',
            ],
            [
                'country' => ['name' => 'Argentina', 'code' => 'AR'],
                'state' => null,
                'city' => 'Buenos Aires',
            ],
            [
                'country' => ['name' => 'Chile', 'code' => 'CL'],
                'state' => null,
                'city' => 'Santiago',
            ],
            [
                'country' => ['name' => 'Canadá', 'code' => 'CA'],
                'state' => ['name' => 'Ontário', 'code' => 'ON'],
                'city' => 'Toronto',
            ],
            [
                'country' => ['name' => 'México', 'code' => 'MX'],
                'state' => null,
                'city' => 'Cidade do México',
            ],
            [
                'country' => ['name' => 'Japão', 'code' => 'JP'],
                'state' => null,
                'city' => 'Tóquio',
            ],
            [
                'country' => ['name' => 'Austrália', 'code' => 'AU'],
                'state' => ['name' => 'Nova Gales do Sul', 'code' => 'NSW'],
                'city' => 'Sydney',
            ],
            [
                'country' => ['name' => 'África do Sul', 'code' => 'ZA'],
                'state' => null,
                'city' => 'Cidade do Cabo',
            ],
        ];

        foreach ($entries as $entry) {
            $countryName = $entry['country']['name'];
            $countryCode = $entry['country']['code'] ?? null;
            $countrySlug = Str::slug($countryName);

            $country = Country::query()->updateOrCreate(
                ['slug' => $countrySlug],
                [
                    'name' => $countryName,
                    'code' => $countryCode,
                ],
            );

            $state = null;

            if ($entry['state'] !== null) {
                $stateName = $entry['state']['name'];
                $stateCode = $entry['state']['code'] ?? null;
                $stateSlug = Str::slug($stateName . '-' . $countrySlug);

                $state = State::query()->updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'slug' => $stateSlug,
                    ],
                    [
                        'name' => $stateName,
                        'code' => $stateCode,
                    ],
                );
            }

            $cityName = $entry['city'];
            $citySlugComponents = [$cityName, $state?->code ?? $state?->name, $country->code ?? $country->name];
            $citySlug = Str::slug(implode('-', array_filter($citySlugComponents)));

            $city = City::query()->updateOrCreate(
                [
                    'country_id' => $country->id,
                    'state_id' => $state?->id,
                    'slug' => $citySlug,
                ],
                [
                    'name' => $cityName,
                ],
            );

            $labelParts = array_filter([
                $city->name,
                $state?->code ?? $state?->name,
                $country->name,
            ]);

            $destinationSlug = Str::slug(implode('-', $labelParts));

            Destination::query()->updateOrCreate(
                [
                    'country_id' => $country->id,
                    'state_id' => $state?->id,
                    'city_id' => $city->id,
                ],
                [
                    'label' => implode(', ', $labelParts),
                    'slug' => $destinationSlug,
                ],
            );
        }
    }
}
