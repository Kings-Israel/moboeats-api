<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplementOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'supplement_id', 'quantity', 'status', 'courier_contact_name', 'courier_contact_email', 'courier_contact_phone'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['amount'];

    /**
     * Get the amount
     *
     * @param  string  $value
     * @return string
     */
    public function getAmountAttribute()
    {
        return $this->quantity * $this->supplement->price_per_quantity;
    }

    /**
     * Get the user that owns the SupplementOrder
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the supplement that owns the SupplementOrder
     */
    public function supplement(): BelongsTo
    {
        return $this->belongsTo(Supplement::class);
    }

    public function payment()
    {
        return $this->morphOne(Payment::class, 'orderable');
    }
}
