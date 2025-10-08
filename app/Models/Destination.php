<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Destination extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'country_id',
        'state_id',
        'city_id',
        'label',
        'slug',
    ];

    /**
     * @return BelongsTo<Country, Destination>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo<State, Destination>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * @return BelongsTo<City, Destination>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
