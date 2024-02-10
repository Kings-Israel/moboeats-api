<?php

namespace App\Models;

use App\Enums\RiderStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Rider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone_no',
        'address',
        'city',
        'state',
        'postal_code',
        'vehicle_type',
        'vehicle_license_plate',
        'profile_picture',
        'status',
        'created_by',
        'updated_by',
        'paypal_email',
    ];

    /**
     * Get the profilePicture
     *
     * @param  string  $value
     * @return string
     */
    public function getProfilePictureAttribute($value)
    {
        if ($value) {
            return config('app.url').'/storage/rider/avatar/'.$value;
        }

        return null;
    }

    protected $keyType = 'int';
    public $incrementing = true;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function getRouteKey()
    {
        return $this->uuid;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('uuid', $value)->firstOrFail();
    }

    public static function options($column)
    {
        if($column == 'status'){
            $options = [
                ['id' => 1,'caption' => 'Inactive', 'color' => 'bg-yellow-500'],
                ['id' => 2,'caption' => 'Active', 'color' => 'bg-green-500'],
            ];
        }
        if(isset($options)){
            return $options;
        }else{
            return null;
        }
    }

    /**
     * Get the user that owns the Rider
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Get all of the tips for the Rider
     */
    public function tips(): HasMany
    {
        return $this->hasMany(RiderTip::class);
    }
}
