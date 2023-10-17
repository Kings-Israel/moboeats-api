<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantOperatingHour;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;

class RestaurantOperatingHoursController extends Controller
{
    use HttpResponses;

    public function index($id)
    {
        $restaurant = Restaurant::find($id);

        $this->success($restaurant->operatingHours);
    }

    public function store(Request $request, $uuid)
    {
        $restaurant = Restaurant::where('uuid', $uuid)->first();

        foreach (json_decode($request->days) as $key => $day) {
            if (array_key_exists($key, json_decode($request->opening_times)) && array_key_exists($key, json_decode($request->closing_times))) {
                RestaurantOperatingHour::create([
                    'restaurant_id' => $restaurant->id,
                    'day' => $day,
                    'opening_time' => json_decode($request->opening_times)[$key],
                    'closing_time' => json_decode($request->closing_times)[$key]
                ]);
            }
        }

        return $this->success($restaurant->load('operatingHours'), 'Operating Hours saved successfully');
    }

    public function update(Request $request, $uuid)
    {
        $request->validate([
            'days' => ['required'],
            'opening_times' => ['required'],
            'opening_times.*' => ['required', 'date_format:H:i'],
            'closing_times' => ['required'],
            'closing_times.*' => ['required', 'date_format:H:i'],
        ]);

        $restaurant = Restaurant::where('uuid', $uuid)->first();
        RestaurantOperatingHour::where('restaurant_id', $restaurant->id)->delete();

        foreach (json_decode($request->days) as $key => $day) {
            if (array_key_exists($key, json_decode($request->opening_times)) && array_key_exists($key, json_decode($request->closing_times))) {
                RestaurantOperatingHour::create([
                    'restaurant_id' => $restaurant->id,
                    'day' => $day,
                    'opening_time' => json_decode($request->opening_times)[$key],
                    'closing_time' => json_decode($request->closing_times)[$key]
                ]);
            }
        }

        $operating_hours = RestaurantOperatingHour::where('restaurant_id', $restaurant->id)->get();

        return $this->success($operating_hours, 'Operating Hour updated successfully');
    }
}
