<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;


class User extends Authenticatable implements LaratrustUser
{
    use HasRolesAndPermissions;

    use HasApiTokens, HasFactory, Notifiable;

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
        'device_token'
    ];

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
}
