<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvertPoster extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the poster
     */
    public function getPosterAttribute($value)
    {
        if ($value) {
            return config('app.url').'/storage/ad/poster/'.$value;
        }

        return NULL;
    }
}
