<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplementImage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['supplement_id', 'image'];

    /**
     * Get the image
     *
     * @param  string  $value
     * @return string
     */
    public function getImageAttribute($value)
    {
        if ($value) {
            return config('app.url').'/storage/supplements/'.$value;
        }

        return NULL;
    }

    /**
     * Get the supplement that owns the SupplementImage
     */
    public function supplement(): BelongsTo
    {
        return $this->belongsTo(Supplement::class);
    }
}
