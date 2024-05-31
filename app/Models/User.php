<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Helpers\NumberGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;
use Laravel\Cashier\Billable;
use Musonza\Chat\Traits\Messageable;

class User extends Authenticatable implements LaratrustUser
{
    use HasRolesAndPermissions, Messageable, SoftDeletes;

    use HasApiTokens, HasFactory, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'role_id',
        'status',
        'location',
        'latitude',
        'longitude',
        'device_token',
        'image',
        'phone_number',
        'type',
    ];

    protected $keyType = 'int';

    public $incrementing = true;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });

        static::created(function ($model) {
            $code = NumberGenerator::generateVerificationCode(ReferralCode::class, 'referral_code');
            $model->referralCode()->create([
                'referral_code' => $code,
            ]);
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['total_rider_tips', 'rider_last_delivery', 'total_rider_deliveries'];

    public function receivesBroadcastNotificationOn(): string
    {
        return 'users.'.$this->email;
    }

    /**
     * Get the avatar
     *
     * @param  string  $value
     * @return string
     */
    public function getImageAttribute($value)
    {
        if ($value) {
            if (env('APP_ENV') == 'production') {
                return 'https://moboeats.com/storage/user/avatar/'.$value;
            } else {
                return config('app.url').'/storage/user/avatar/'.$value;
            }
        }
        return config('app.url').'/assets/user/default.png';
    }

    /**
     * Get all of the restaurants for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class, 'user_id', 'id');
    }

    /**
     * Get the orderer associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function orderer(): HasOne
    {
        return $this->hasOne(Orderer::class, 'user_id', 'id');
    }
    /**
     * Get the rider associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function rider(): HasOne
    {
        return $this->hasOne(Rider::class, 'user_id', 'id');
    }

    /**
     * Get all of the bookmarks for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(MenuBookmark::class, 'user_id', 'id');
    }

    /**
     * Get all of the bookmarks for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function restaurantBookmarks(): HasMany
    {
        return $this->hasMany(RestaurantBookmark::class, 'user_id', 'id');
    }

    // /**
    //  * Get all of the carts for the User
    //  *
    //  * @return \Illuminate\Database\Eloquent\Relations\HasMany
    //  */
    // public function carts(): HasMany
    // {
    //     return $this->hasMany(Cart::class, 'user_id', 'id');
    // }

    /**
     * Get the cart associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class, 'user_id', 'id');
    }

    /**
     * Get all of the orders for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id', 'id');
    }

    /**
     * Get all of the stripePayments for the User
     */
    public function stripePayments(): HasMany
    {
        return $this->hasMany(StripePayment::class);
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
     * Get the restaurant that owns the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'user_restaurants', 'user_id', 'restaurant_id');
    }

    /**
     * Get all of the deliveries for the User
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Order::class, 'rider_id', 'id');
    }

    /**
     * Get all of the reviews for the Order
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the referralCode associated with the User
     */
    public function referralCode(): HasOne
    {
        return $this->hasOne(ReferralCode::class);
    }

    public function isAdmin(): bool
    {
        return $this->email == 'admin@moboeats.com' ? true : false;
    }

    /**
     * Get the riderTips
     *
     * @param  string  $value
     * @return string
     */
    public function getTotalRiderTipsAttribute()
    {
        $amount = 0;
        if ($this->hasRole('rider') && $this->rider) {
            foreach ($this->rider->tips as $tip) {
                $amount += $tip->amount;
            }
        }
        return $amount;
    }

    /**
     * Get the riderLastDelivery
     *
     * @param  string  $value
     * @return string
     */
    public function getRiderLastDeliveryAttribute()
    {
        $value = NULL;
        if ($this->hasRole('rider') && $this->rider) {
            $value = $this->rider->deliveries?->sortDesc()->first();
        }
        return $value;
    }

    /**
     * Get the totalRiderDeliveries
     *
     * @param  string  $value
     * @return string
     */
    public function getTotalRiderDeliveriesAttribute()
    {
        return $this->rider ? $this->rider->deliveries?->count() : 0;
    }
}
