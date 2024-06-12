<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DietSubscriptionPackage extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get all of the subscriptions for the DietSubscriptionPackage
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(DietSubscription::class);
    }
}
