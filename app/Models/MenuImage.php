<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuImage extends Model implements UrlRoutable
{
    use HasFactory;

    protected $keyType = 'int';
    public $incrementing = true;

    protected $guarded = [];

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

    public function getImageUrlAttribute($value)
    {
        if (config('app.env') == 'production') {
            return 'https://moboeats.com/storage/menus/images/' .$value;
        }
        return config('app.url').'/storage/menus/images/' .$value;
    }

    /**
     * Get the menu that owns the MenuImage
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id', 'id');
    }
}
