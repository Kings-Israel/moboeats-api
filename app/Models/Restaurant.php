<?php

namespace App\Models;

use App\Enums\RestaurantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Session;
use App\Traits\Admin\SearchTrait;
use App\Traits\Admin\ColumnsTrait;
use App\Traits\Admin\UuidTrait;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;

class Restaurant extends Model implements UrlRoutable
{
    use HasFactory, Notifiable;
    // protected $primaryKey = 'uuid';
    // protected $guarded = [];
    protected $keyType = 'int';
    public $incrementing = true;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
            $model->user_id = Auth::user()->id;
        });
    }

    protected $fillable = [
        // 'uuid',
        'name',
        'name_short',
        'email',
        // 'user_id',
        'about',
        'about_short',
        'phone_no',
        'address',
        'city',
        'state',
        'postal_code',
        'map_location',
        'latitude',
        'longitude',
        'url',
        'logo',
        'status',
        'created_by',
        'updated_by',
        'sitting_capacity',
        'service_charge_agreement',
        'denied_reason',
        'groceries_service_charge_agreement',
        'paypal_email',
    ];

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

    public static function getTableName()
    {
        return with(new static)->getTable();
    }

    public static function options($column)
    {
        if($column == 'status'){
            $options = [
                ['id' => 1,'caption' => 'Inactive', 'color' => 'bg-yellow-500'],
                ['id' => 2,'caption' => 'Active', 'color' => 'bg-green-500'],
            ];
        }
        if($column == 'user_category'){
            $options = [
                ['id' => 2,'caption' => 'Regular', 'color' => 'bg-yellow-500'],
                ['id' => 100,'caption' => 'Admin', 'color' => 'bg-green-500'],
            ];
        }
        if(isset($options)){
            return $options;
        } else {
            return null;
        }
    }

    public function receivesBroadcastNotificationOn(): string
    {
        return 'restaurants.'.$this->email;
    }

    /**
     * Get the logo
     *
     * @param  string  $value
     * @return string
     */
    public function getLogoAttribute($value)
    {
        if ($value) {
            return config('app.url').'/storage/'.$value;
        }
        return config('app.url').'/assets/user/default.png';
    }

    /**
     * Get the status of the restaurant
     */
    public function getStatusAttribute($value): string
    {
        switch ($value) {
            case '1':
                return RestaurantStatus::pending();
                break;
            case '2':
                return RestaurantStatus::approved();
                break;
            case '3':
                return RestaurantStatus::denied();
                break;
            default:
                return RestaurantStatus::pending();
                break;
        }

        return $value;
    }

    /**
     * Scope a query to only include approved
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 2);
    }

    /**
     * Scope a query to only include restaurant that are operational at the moment.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInOperation($query)
    {
        return $query->whereHas('operatingHours', function ($query) {
            $query->where('opening_time', '<=' ,now()->format('H:i'))
                    ->where('closing_time', '>=', now()->format('H:i'))
                    ->where('day', now()->format('l'));
        });
    }

    /**
     * Scope a query to only include restaurants that have menu items with prices
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasMenu($query)
    {
        return $query->whereHas('menus', function ($query) {
            $query->where('status', 2)
                    ->whereHas('menuPrices', function ($query) {
                        $query->where('status', 2);
                    });
        });
    }

    /**
     * Scope a query to only include rated restaurants
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRated($query)
    {
        return $query->whereHas('reviews');
    }

    /**
     * Get the questionnaire associated with the Restaurant
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function questionnaire(): HasOne
    {
        return $this->hasOne(Questionnaire::class, 'restaurant_id', 'id');
    }

    /**
     * Get all of the menus for the Restaurant
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class, 'restaurant_id', 'id');
    }

    /**
     * Get the user that owns the Restaurant
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    /**
     * Get all of the orders for the Restaurant
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'restaurant_id', 'id');
    }

    /**
     * The users that belong to the Restaurant
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_restaurants', 'restaurant_id', 'user_id');
    }

    /**
     * Get all of the operatingHours for the Restaurant
     */
    public function operatingHours(): HasMany
    {
        return $this->hasMany(RestaurantOperatingHour::class);
    }

    /**
     * Get all of the documents for the Restaurant
     */
    public function documents(): HasMany
    {
        return $this->hasMany(RestaurantDocument::class);
    }

    /**
     * Get all of the promoCodes for the Restaurant
     */
    public function promoCodes(): HasMany
    {
        return $this->hasMany(PromoCode::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Get all of the tables for the Restaurant
     */
    public function restaurantTables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class);
    }

    public function averageRating()
    {
        $total_reviews_count = $this->reviews->count();
        if ($total_reviews_count > 0) {
            $total_reviews = $this->reviews->sum('rating');

            return $total_reviews / $total_reviews_count;
        }

        return 0;
    }
}
