<?php

namespace App\Models;

use App\Enums\OrderStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;
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
        'discount'
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
}
