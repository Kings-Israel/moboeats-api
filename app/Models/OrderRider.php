<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderRider extends Model
{
    use HasFactory;

    protected $fillable = [
        'rider_id',
        'order_id',
        'assigned_at',
        'delivered_at',
        'status',
        'created_by', 
        'updated_by'
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
                ['id' => 3,'caption' => 'On the way, delivered', 'color' => 'bg-green-500'],
                ['id' => 4,'caption' => 'Delivered, delivered', 'color' => 'bg-green-500'],
            ];
        }
        if(isset($options)){
            return $options;
        }else{
            return null;
        }
    }

    
}
