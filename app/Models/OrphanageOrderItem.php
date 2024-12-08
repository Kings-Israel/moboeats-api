<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrphanageOrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the orphanageOrder that owns the OrphanageOrderItem
     */
    public function orphanageOrder(): BelongsTo
    {
        return $this->belongsTo(OrphanageOrder::class);
    }
}
