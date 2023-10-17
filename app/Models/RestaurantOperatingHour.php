<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantOperatingHour extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function getOpeningTimeAttribute($value)
    {
        return Carbon::parse($value)->format('H:i');
    }

    public function getClosingTimeAttribute($value)
    {
        return Carbon::parse($value)->format('H:i');
    }

    /**
     * Get the restaurant that owns the RestaurantOperatingHour
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
