<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Motorbikes extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the currentRider that owns the Motorbikes
     *
     */
    public function currentRider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get all of the restaurants for the Motorbikes
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function restaurants(): HasManyThrough
    {
        return $this->hasManyThrough(Restaurant::class, RestaurantMotorbike::class, 'motorbike_id', 'id', 'id', 'restaurant_id');
    }
}
