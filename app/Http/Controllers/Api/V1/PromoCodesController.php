<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\Restaurant;
use App\Models\UserRestaurant;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;

class PromoCodesController extends Controller
{
    use HttpResponses;

    /**
     * Get all the promo codes.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $restaurants = [];

        if (auth()->user()->hasRole('restaurant')) {
            $restaurants_ids = auth()->user()->restaurants->pluck('id');

            $codes = PromoCode::with('restaurant')->whereIn('restaurant_id', $restaurants_ids)->paginate(10);
            $restaurants = Restaurant::whereIn('id', $restaurants_ids)->get();
        } else if (auth()->user()->hasRole('restaurant employee')) {
            $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();

            $codes = PromoCode::whereIn('restaurant_id', $user_restaurant->restaurant_id)->paginate(10);
        } else {
            $codes = PromoCode::with('restaurant')->paginate(10);
        }

        return $this->success([
            'promo_codes' => $codes,
            'restaurants' => $restaurants
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'restaurant_ids' => ['required'],
            'code' => ['required'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'type' => ['required', 'in:amount,percentage'],
            'value' => ['required', 'integer'],
            'status' => ['required', 'in:active,inactive']
        ]);

        collect(json_decode($request->restaurant_ids, true))->each(function ($restaurant) use ($request) {
            $promo = collect($request)->merge(['restaurant_id' => $restaurant])->forget('restaurant_ids');

            PromoCode::create($promo->all());
        });


        return $this->success('', 'Promo Code added successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PromoCode $promo_code)
    {
        $request->validate([
            'code' => ['required'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'type' => ['required', 'in:amount,percentage'],
            'value' => ['required', 'integer'],
            'status' => ['required', 'in:active,inactive']
        ]);

        $promo_code->update($request->all());

        return $this->success('', 'Promo code updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PromoCode $promo_code)
    {
        $promo_code->delete();

        return $this->success('', 'Promo code deleted successfully');
    }
}
