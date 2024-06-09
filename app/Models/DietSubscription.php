<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DietSubscription extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the user that owns the DietPlan
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
