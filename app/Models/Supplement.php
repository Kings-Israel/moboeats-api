<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplement extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['supplement_supplier_id', 'name', 'description', 'is_available', 'price_per_quantity', 'measuring_unit'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_available' => 'bool',
    ];

    /**
     * Scope a query to only include available
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Get the supplier that owns the Supplement
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SupplementSupplier::class, 'supplement_supplier_id', 'id');
    }

    /**
     * Get all of the orders for the Supplement
     */
    public function orders(): HasMany
    {
        return $this->hasMany(SupplementOrder::class);
    }

    /**
     * Get all of the images for the Supplement
     */
    public function images(): HasMany
    {
        return $this->hasMany(SupplementImage::class);
    }
}
