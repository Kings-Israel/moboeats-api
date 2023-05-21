<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\Admin\SearchTrait;
use App\Traits\Admin\ColumnsTrait;
use App\Traits\Admin\UuidTrait;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FoodCommonCategory extends Model implements UrlRoutable
{
    use HasFactory;
    
    // protected $primaryKey = 'uuid';
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
        'title',
        'description',
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
     * The food_sub_categories that belong to the CommonCategory
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function food_sub_categories(): BelongsToMany
    {
        return $this->belongsToMany(FooSubCategory::class, 'f_category_sub_categories', 'category_id', 'sub_category_id');
    }

    /**
     * The menus that belong to the Category
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'category_menus', 'category_id', 'menu_id');
    }

}
