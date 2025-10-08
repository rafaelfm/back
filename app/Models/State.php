<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'country_id',
        'name',
        'code',
        'slug',
    ];

    /**
     * @return BelongsTo<Country, State>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return HasMany<City>
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /**
     * @return HasMany<Destination>
     */
    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class);
    }
}
