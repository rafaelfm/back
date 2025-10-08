<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    /**
     * Retorna uma lista de destinos para auto completar.
     */
    public function index(Request $request): JsonResponse
    {
        $term = $request->string('q')->trim();
        $limit = (int) $request->integer('limit', 10);
        $limit = max(1, min($limit, 25));

        $destinations = Destination::query()
            ->select([
                'destinations.id',
                'destinations.slug',
                'destinations.city_id',
                'destinations.label',
                'cities.name as city_name',
                'states.name as state_name',
                'states.code as state_code',
                'countries.name as country_name',
            ])
            ->join('countries', 'countries.id', '=', 'destinations.country_id')
            ->leftJoin('states', 'states.id', '=', 'destinations.state_id')
            ->join('cities', 'cities.id', '=', 'destinations.city_id')
            ->when($term->isNotEmpty(), function (Builder $query) use ($term): void {
                $escaped = addcslashes($term->value(), '%_');
                $search = '%' . $escaped . '%';

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('cities.name', 'LIKE', $search)
                        ->orWhere('states.name', 'LIKE', $search)
                        ->orWhere('states.code', 'LIKE', $search)
                        ->orWhere('countries.name', 'LIKE', $search)
                        ->orWhere('destinations.label', 'LIKE', $search);
                });
            })
            ->orderBy('cities.name')
            ->orderBy('countries.name')
            ->limit($limit)
            ->get()
            ->map(static function ($row): array {
                $parts = array_filter([
                    $row->city_name,
                    $row->state_code ?? $row->state_name,
                    $row->country_name,
                ]);

                return [
                    'id' => $row->id,
                    'slug' => $row->slug,
                    'city_id' => $row->city_id,
                    'city' => $row->city_name,
                    'state' => $row->state_name,
                    'state_code' => $row->state_code,
                    'country' => $row->country_name,
                    'label' => $row->label ?? implode(', ', $parts),
                ];
            });

        return response()->json([
            'data' => $destinations,
        ]);
    }
}
