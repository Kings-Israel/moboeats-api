<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class RestaurantTable extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['seating_area_id', 'restaurant_id', 'name', 'seat_number'];

    /**
     * Get the restaurant that owns the RestaurantTable
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get the seatingArea that owns the RestaurantTable
     */
    public function seatingArea(): BelongsTo
    {
        return $this->belongsTo(SeatingArea::class);
    }

    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(Order::class, OrderTable::class, 'order_id', 'restaurant_table_id');
    }
}
