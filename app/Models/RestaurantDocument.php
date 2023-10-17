<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantDocument extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the File Url
     *
     * @param  string  $value
     * @return string
     */
    // public function getFileUrlAttribute($value)
    // {
    //     return config('app.url').'/api/v1/restaurant/documents/'.$this->id.'/download';
    // }

    /**
     * Get the restaurant that owns the RestaurantDocument
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
