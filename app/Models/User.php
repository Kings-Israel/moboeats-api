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
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
        'height',
        'height_units',
        'weight',
        'weight_units',
        'body_mass_index',
        'is_guided',
        'country',
        'country_code',
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
        'is_guided' => 'bool',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['total_rider_tips', 'rider_last_delivery', 'total_rider_deliveries', 'amount_spent', 'latest_order', 'county'];

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
            return config('app.url').'/storage/user/avatar/'.$value;
        }
        return config('app.url').'/assets/user/default.png';
    }

    /**
     * Scope a query to only include activeDietSubscribtions
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActiveDietSubscription($query)
    {
        return $query->whereHas('dietSubscriptions', function ($query) {
            $query->whereDate('end_date', '>=', now()->format('Y-m-d'));
        });
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

    /**
     * Get all of the dietPlans for the User
     */
    public function dietPlans(): HasMany
    {
        return $this->hasMany(DietPlan::class);
    }

    /**
     * Get all of the dietSubscriptions for the User
     */
    public function dietSubscriptions(): HasMany
    {
        return $this->hasMany(DietSubscription::class);
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
        if ($this->hasRole('rider')) {
            $value = $this->deliveries?->sortDesc()->first();
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
        return $this->deliveries?->count();
    }

    /**
     * Get the latestOrder
     *
     * @param  string  $value
     */
    public function getLatestOrderAttribute(): Order|NULL
    {
        if ($this->hasRole('orderer')) {
            return $this->orders->sortByDesc('id')->first();
        }

        return NULL;
    }

    /**
     * Get the amount spent
     *
     * @param  string  $value
     * @return string
     */
    public function getAmountSpentAttribute():float
    {
        if ($this->hasRole('orderer')) {
            return $this->orders->sum('total_amount');
        }

        return 0;
    }

    /**
     * Get the default country
     *
     * @param  string  $value
     * @return string
     */
    public function getCountyAttribute()
    {
        if ($this->country) {
            return $this->country;
        }

        if ($this->latitude && $this->longitude) {
            $user_country = Cache::get($this->uuid.'-country');
            if (!$user_country) {
                try {
                    $user_location = Http::withOptions(['verify' => false])
                                            ->get('https://maps.googleapis.com/maps/api/geocode/json?latlng='.$this->latitude.','.$this->longitude.'&key='.config('services.map.key'));

                    if($user_location->failed() || $user_location->clientError() || $user_location->serverError()) {
                        $user_country = 'Kenya';
                        $user_short_country = 'KE';
                    } elseif ($user_location && array_key_exists('status', collect($user_location)->toArray()) && $user_location['status'] == "REQUEST_DENIED") {
                        $user_country = 'Kenya';
                        $user_short_country = 'KE';
                    } else {
                        foreach ($user_location['results'][0]['address_components'] as $place) {
                            if (collect($place['types'])->contains('country')) {
                                $user_country = $place['long_name'];
                            }

                            if (collect($place['types'])->contains('country')) {
                                $user_short_country = $place['short_name'];
                            }
                        }
                    }
                } catch (ConnectionException $e) {
                    $user_country = 'Kenya';
                }

                Cache::put($this->uuid.'-country', $user_country);

                $this->update([
                    'country' => $user_country,
                    'country_code' => $user_short_country
                ]);
            }

            return $user_country;
        }

        return 'Kenya';
    }

    /**
     * Get the users currency
     *
     * @param  string  $value
     * @return string
     */
    public function getCurrencyAttribute()
    {
        if (str_starts_with($this->phone_number, '+254') || str_starts_with($this->phone_number, '254')) {
            return 'KES';
        } else {
            return 'GBP';
        }
    }
}
