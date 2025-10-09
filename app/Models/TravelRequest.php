<?php

namespace App\Models;

use App\TravelRequests\States\TravelRequestState;
use App\TravelRequests\States\TravelRequestStateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'city_id',
        'requester_name',
        'departure_date',
        'return_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
        'city_id' => 'integer',
    ];

    protected $appends = [
        'location_label',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function getLocationLabelAttribute(): string
    {
        $city = $this->city;

        if (! $city) {
            return '';
        }

        $parts = array_filter([
            $city->name,
            $city->state?->code ?? $city->state?->name,
            $city->country?->name,
        ]);

        return implode(', ', $parts);
    }

    public function stateFor(User $actor): TravelRequestState
    {
        return TravelRequestStateFactory::make($this, $actor);
    }

    public function ensureStatusTransitionPossible(string $status, User $actor): void
    {
        $this->stateFor($actor)->ensureTransitionPossible($status);
    }

    public function canTransitionStatusTo(string $status, User $actor): bool
    {
        return $this->stateFor($actor)->canTransitionTo($status);
    }

    public function transitionStatusTo(string $status, User $actor): self
    {
        $this->stateFor($actor)->transitionTo($status);

        return $this->refresh();
    }
}
