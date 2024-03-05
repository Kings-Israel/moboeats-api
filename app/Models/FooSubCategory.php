<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Illuminate\Contracts\Routing\UrlRoutable;

class FooSubCategory extends Model implements UrlRoutable
{
    use HasFactory;

    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [
        'title',
        'description',
        'status',
        'created_by',
        'updated_by',
        'image'
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
     * Get the image
     *
     * @param  string  $value
     * @return string
     */
    public function getImageAttribute($value)
    {
        if ($value) {
            return config('app.url').'/storage/category/subcategory/' . $value;
        }
        return config('app.url').'/assets/category/default.png';
    }

    /**
     * The foodCategories that belong to the FooSubCategory
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function foodCategories(): BelongsToMany
    {
        return $this->belongsToMany(FoodCommonCategory::class, 'f_category_sub_categories', 'sub_category_id', 'category_id');
    }
    /**
     * The menus that belong to the Category
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'sub_category_menus', 'sub_category_id', 'menu_id');
    }

}
