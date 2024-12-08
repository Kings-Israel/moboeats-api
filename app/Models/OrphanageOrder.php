<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrphanageOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the user that owns the OrphanageOrder
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the orphanage that owns the OrphanageOrder
     */
    public function orphanage(): BelongsTo
    {
        return $this->belongsTo(Orphanage::class);
    }

    /**
     * Get all of the orphanageOrderItems for the OrphanageOrder
     */
    public function orphanageOrderItems(): HasMany
    {
        return $this->hasMany(OrphanageOrderItem::class);
    }
}
