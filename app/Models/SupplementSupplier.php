<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class SupplementSupplier extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'description', 'location', 'status', 'image'];

    /**
     * Get the image
     *
     * @param  string  $value
     * @return string
     */
    public function getImageAttribute($value)
    {
        if ($value) {
            return config('app.url').'/storage/supplements/suppliers/'.$value;
        }

        return NULL;
    }

    /**
     * Get all of the supplements for the SupplementSupplier
     */
    public function supplements(): HasMany
    {
        return $this->hasMany(Supplement::class);
    }

    /**
     * Get all of the orders for the SupplementSupplier
     */
    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(SupplementOrder::class, Supplement::class);
    }
}
