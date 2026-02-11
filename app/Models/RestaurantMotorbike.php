<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantMotorbike extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the restaurant that owns the RestaurantMotorbike
     *
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get the motorbike that owns the RestaurantMotorbike
     *
     */
    public function motorbike(): BelongsTo
    {
        return $this->belongsTo(Motorbikes::class);
    }
}
