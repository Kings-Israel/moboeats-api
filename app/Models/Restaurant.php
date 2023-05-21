<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Session;
use App\Traits\Admin\SearchTrait;
use App\Traits\Admin\ColumnsTrait;
use App\Traits\Admin\UuidTrait;
use Illuminate\Support\Str;
use Illuminate\Contracts\Routing\UrlRoutable;

class Restaurant extends Model implements UrlRoutable
{
    use HasFactory;
    // protected $primaryKey = 'uuid';
    // protected $guarded = [];
    protected $keyType = 'int';
    public $incrementing = true;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    protected $fillable = [
        // 'uuid',
        'name',
        'name_short',
        'email',
        'about',
        'about_short',
        'phone_no',
        'address',
        'city',
        'state',
        'postal_code',
        'map_location',
        'url',
        'logo',
        'status',
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
        }else{
            return null;
        }
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
    
}
