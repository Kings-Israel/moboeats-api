<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'order_id', 'reviewable_type', 'reviewable_id', 'rating', 'review'];

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that owns the Review
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order that owns the Review
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
