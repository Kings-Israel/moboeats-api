<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderDocument extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the document path
     *
     * @param  string  $value
     * @return string
     */
    public function getFileAttribute($value)
    {
        return config('app.url'). '/storage/rider/documents/' . $value;
    }

    /**
     * Get the rider that owns the RiderDocument
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }
}
