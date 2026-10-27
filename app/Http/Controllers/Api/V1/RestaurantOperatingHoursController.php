<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantOperatingHour;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;

/**
 * @group Restaurant Management
 *
 * Restaurant opening and closing hours per day.
 */
class RestaurantOperatingHoursController extends Controller
{
    use HttpResponses;

    /**
     * List restaurant operating hours
     *
     * Intended to return the operating hours of a restaurant. Currently the response body is empty (the method does not
     * return the result).
     *
     * @urlParam id integer required The numeric ID of the restaurant. Example: 1
     *
     * @response 200 scenario="Empty body" ""
     * @response 422 scenario="Restaurant owner has no restaurant" {"data": "no_restaurant"}
     */
    public function index($id)
    {
        $restaurant = Restaurant::find($id);

        $this->success($restaurant->operatingHours);
    }

    /**
     * Add restaurant operating hours
     *
     * Adds operating hours for a restaurant. Days without both an opening and closing time are skipped. The activity is logged.
     *
     * @urlParam uuid string required The UUID of the restaurant. Example: 5e6f7a8b-9c0d-4e1f-a2b3-c4d5e6f7a8b9
     * @bodyParam days string[] required Day names. May be sent as an array or as a JSON-encoded string. Example: ["Monday","Tuesday"]
     * @bodyParam opening_times string[] required Opening times (`H:i`), matched to `days` by index. Example: ["08:00","08:00"]
     * @bodyParam closing_times string[] required Closing times (`H:i`), matched to `days` by index. Example: ["22:00","22:00"]
     *
     * @response 200 {"status": "Request was succesful", "message": "Operating Hours saved successfully", "data": {"id": 1, "uuid": "5e6f7a8b-9c0d-4e1f-a2b3-c4d5e6f7a8b9", "user_id": 3, "name": "Mama Oliech", "email": "info@mamaoliech.co.ke", "phone_no": "254712345678", "address": "Marcus Garvey Rd", "city": "Nairobi", "logo": "https://moboeats.com/storage/restaurant/logos/logo.png", "status": "Pending", "created_at": "2024-01-10T10:00:00.000000Z", "updated_at": "2024-01-10T10:00:00.000000Z", "operating_hours": [{"id": 1, "restaurant_id": 1, "day": "Monday", "opening_time": "08:00", "closing_time": "22:00", "created_at": "2024-01-10T10:00:00.000000Z", "updated_at": "2024-01-10T10:00:00.000000Z"}]}}
     * @response 422 scenario="Restaurant owner has no restaurant" {"data": "no_restaurant"}
     * @responseField data object The restaurant with its `operating_hours`.
     */
    public function store(Request $request, $uuid)
    {
        info(gettype($request->days));
        $restaurant = Restaurant::where('uuid', $uuid)->first();

        if (gettype($request->days) == 'array') {
            foreach ($request->days as $key => $day) {
                if (array_key_exists($key, $request->opening_times) && $request->opening_times[$key] != null  && array_key_exists($key, $request->closing_times) && $request->closing_times[$key] != null) {
                    RestaurantOperatingHour::create([
                        'restaurant_id' => $restaurant->id,
                        'day' => $day,
                        'opening_time' => $request->opening_times[$key],
                        'closing_time' => $request->closing_times[$key]
                    ]);
                }
            }
        } else {
            foreach (json_decode($request->days) as $key => $day) {
                if (array_key_exists($key, json_decode($request->opening_times)) && json_decode($request->opening_times)[$key] != null  && array_key_exists($key, json_decode($request->closing_times)) && json_decode($request->closing_times)[$key] != null) {
                    RestaurantOperatingHour::create([
                        'restaurant_id' => $restaurant->id,
                        'day' => $day,
                        'opening_time' => json_decode($request->opening_times)[$key],
                        'closing_time' => json_decode($request->closing_times)[$key]
                    ]);
                }
            }
        }

        activity()->causedBy(auth()->user())->performedOn($restaurant)->log('added new operating hours');

        return $this->success($restaurant->load('operatingHours'), 'Operating Hours saved successfully');
    }

    /**
     * Replace restaurant operating hours
     *
     * Deletes all operating hours of the restaurant and recreates them from the request. The arrays must be sent as
     * JSON-encoded strings. The activity is logged.
     *
     * @urlParam uuid string required The UUID of the restaurant. Example: 5e6f7a8b-9c0d-4e1f-a2b3-c4d5e6f7a8b9
     * @bodyParam days string required JSON-encoded array of day names. Example: ["Monday","Tuesday"]
     * @bodyParam opening_times string required JSON-encoded array of opening times (`H:i`), matched to `days` by index. Example: ["08:00","08:00"]
     * @bodyParam closing_times string required JSON-encoded array of closing times (`H:i`), matched to `days` by index. Example: ["22:00","22:00"]
     *
     * @response 200 {"status": "Request was succesful", "message": "Operating Hour updated successfully", "data": [{"id": 1, "restaurant_id": 1, "day": "Monday", "opening_time": "08:00", "closing_time": "22:00", "created_at": "2024-01-10T10:00:00.000000Z", "updated_at": "2024-01-10T10:00:00.000000Z"}]}
     * @response 422 scenario="Validation error" {"message": "The days field is required.", "errors": {"days": ["The days field is required."]}}
     * @response 422 scenario="Restaurant owner has no restaurant" {"data": "no_restaurant"}
     * @responseField data object[] The restaurant's new operating hours.
     */
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
            if (array_key_exists($key, json_decode($request->opening_times)) && json_decode($request->opening_times)[$key] != null && array_key_exists($key, json_decode($request->closing_times)) && json_decode($request->closing_times)[$key] != null) {
                RestaurantOperatingHour::create([
                    'restaurant_id' => $restaurant->id,
                    'day' => $day,
                    'opening_time' => json_decode($request->opening_times)[$key],
                    'closing_time' => json_decode($request->closing_times)[$key]
                ]);
            }
        }

        $operating_hours = RestaurantOperatingHour::where('restaurant_id', $restaurant->id)->get();

        activity()->causedBy(auth()->user())->performedOn($restaurant)->log('updated operating hours');

        return $this->success($operating_hours, 'Operating Hour updated successfully');
    }
}
