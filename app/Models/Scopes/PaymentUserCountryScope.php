<?php

namespace App\Models\Scopes;

use App\Models\Country;
use App\Models\UserCountry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PaymentUserCountryScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check() && count(auth()->user()->countries) > 0) {
            $countries = UserCountry::where('user_id', auth()->id())->pluck('country_id');

            $country_names = Country::whereIn('id', $countries)->pluck('name');

            $builder->whereHas('orderable', function ($query) use ($country_names) {
                $query->whereHas('restaurant', function ($query) use ($country_names) {
                    $query->whereIn('country', $country_names);
                });
            });
        }
    }
}
