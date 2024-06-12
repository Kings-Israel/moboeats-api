<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RiderTip extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['rider_id', 'order_id', 'amount', 'status', 'transaction_id'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    /**
     * Get the rider that owns the RiderTip
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    public function payment()
    {
        return $this->morphOne(Payment::class, 'orderable');
    }
}
