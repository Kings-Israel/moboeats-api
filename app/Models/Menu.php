<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Menu extends Model implements UrlRoutable
{
    use HasFactory;

    protected $keyType = 'int';
    public $incrementing = true;
    // protected $guarded = [];
    protected $fillable = [
        'title',
        'description',
        'restaurant_id',
        'status',
        'preparation_time',
        'created_by',
        'updated_by',
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

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    /**
     * Scope a query to only include active
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 2);
    }

    /**
     * Scope a query to only include has set prices
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasActivePrices($query)
    {
        return $query->whereHas('menuPrices', fn ($q) => $q->where('status', 2));
    }

    /**
     * The categories that belong to the Post
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(FoodCommonCategory::class, 'category_menus', 'menu_id', 'category_id');
    }

    /**
     * The sub_categories that belong to the Post
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function subCategories(): BelongsToMany
    {
        return $this->belongsToMany(FooSubCategory::class, 'sub_category_menus', 'menu_id', 'sub_category_id');
    }

    /**
     * Get all of the images for the Menu
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images(): HasMany
    {
        return $this->hasMany(MenuImage::class, 'menu_id', 'id');
    }

    /**
     * Get the restaurant that owns the Menu
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id', 'id');
    }

    /**
     * Get all of the bookmarks for the Menu
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(MenuBookmark::class, 'menu_id', 'id');
    }

    /**
     * Get all of the menuPrices for the Menu
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function menuPrices(): HasMany
    {
        return $this->hasMany(MenuPrice::class, 'menu_id', 'id');
    }

    /**
     * Get all of the cartItems for the Menu
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'menu_id', 'id');
    }

    /**
     * Get the discount associated with the Menu
     */
    public function discount(): HasOne
    {
        return $this->hasOne(Discount::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Get all of the orderItems for the Menu
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'menu_id', 'id');
    }

    public function getOrdersValue(): int
    {
        $total_value = 0;

        $orders = $this->orderItems()
                        ->whereHas('order', function ($query) {
                            $query->where('status', 5);
                        })
                        ->get()
                        ->groupBy('order_id');

        foreach ($orders as $key => $order) {
            $order_details = Order::find($key);
            $total_value += $order_details->total_amount - $order_details->service_charge;
        }

        return $total_value;
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
