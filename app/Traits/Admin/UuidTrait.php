<?php

namespace App\Traits\Admin;

use Illuminate\Support\Str;
// use Webpatser\Uuid\Uuid;

trait UuidTrait
{
     /**
    * Boot function from laravel.
         */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid =  (string) Str::uuid();
        });
    }
}