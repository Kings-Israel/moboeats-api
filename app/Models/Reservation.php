<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['order_id', 'seat_number', 'reservation_date', 'status'];

    /**
     * Get the order that owns the Reservation
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
