<?php

namespace App\Models;

use App\Enums\OrderStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;
use Malhal\Geographical\Geographical;

class Order extends Model
{
    use HasFactory, Geographical;

    const LATITUDE  = 'delivery_location_lat';
    const LONGITUDE = 'delivery_location_lng';

    protected $fillable = [
        'user_id',
        'restaurant_id',
        'total_amount',
        'delivery',
        'delivery_address',
        'delivery_location_lat',
        'delivery_location_lng',
        'delivery_fee',
        'delivery_status',
        'status',
        'created_by',
        'updated_by',
        'rider_id',
        'booking_time',
        'service_charge',
        'discount',
        'promo_code_id',
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
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'delivery_location_lat' => 'double',
        'delivery_location_lng' => 'double',
        'delivery' => 'bool'
    ];

    public static function options($column)
    {
        if($column == 'status'){
            $options = [
                ['id' => 1,'caption' => 'Pending', 'color' => 'bg-yellow-500'],
                ['id' => 2,'caption' => 'Confirmed', 'color' => 'bg-green-500'],
            ];
        }

        if(isset($options)){
            return $options;
        }else{
            return null;
        }
    }

    /**
     * Get the status of the order
     */
    public function getStatusAttribute($value): string
    {
        switch ($value) {
            case '0':
                return OrderStatusEnum::denied();
                break;
            case '1':
                return OrderStatusEnum::pending();
                break;
            case '2':
                return OrderStatusEnum::in_progress();
                break;
            case '3':
                return OrderStatusEnum::awaiting_pick_up();
                break;
            case '4':
                return OrderStatusEnum::on_delivery();
                break;
            case '5':
                return OrderStatusEnum::delivered();
                break;
            default:
                return OrderStatusEnum::pending();
                break;
        }
        return $value;
    }

    /**
     * Get the user that owns the Order
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get all of the orderItems for the Order
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Get all of the transactions for the Order
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'order_id', 'id');
    }

    /**
     * Get the restaurant that owns the Order
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id', 'id');
    }

    /**
     * Get the rider associated with the Order
     */
    public function rider(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'rider_id');
    }

    /**
     * Get all of the reviews for the Order
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the reservation associated with the Order
     */
    public function reservation(): HasOne
    {
        return $this->hasOne(Reservation::class);
    }

    /**
     * Get all of the orderTables for the Order
     */
    public function orderTables(): HasMany
    {
        return $this->hasMany(OrderTable::class);
    }

    /**
     * Get the promoCode that owns the Order
     */
    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function getTotalPreparationTime(): int
    {
        $total_preparation_time = 0;

        foreach ($this->orderItems as $order_item) {
            $total_preparation_time += $order_item->menu->preparation_time;
        }

        return $total_preparation_time;
    }
}
