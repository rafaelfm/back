<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'country_id',
        'state_id',
        'name',
        'slug',
    ];

    /**
     * @return BelongsTo<Country, City>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo<State, City>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * @return HasMany<Destination>
     */
    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class);
    }
}
