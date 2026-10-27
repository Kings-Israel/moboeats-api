<?php

namespace App\Http\Controllers;

use App\Mail\NewMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/**
 * @group Maps & Location
 *
 * Thin proxies to the Google Maps APIs. Responses are passed through unchanged from Google.
 */
class HomeController extends Controller
{
    public function contactSubmit(Request $request)
    {
        $request->validate([
            'message' => ['required']
        ],[
            'message.required' => 'Enter a message'
        ]);

        toastr()->success('', 'Thanks for contacting us. We\'ll get back to you soon');

        Mail::to('info@moboeats.com')->send(new NewMessage($request->name, $request->email, $request->message));

        return back();
    }

    /**
     * Reverse geocode coordinates
     *
     * Proxies the Google Geocoding API and returns its raw JSON response.
     *
     * @urlParam lat number required Latitude. Example: -1.2921
     * @urlParam long number required Longitude. Example: 36.8219
     *
     * @response 200 {"results": [{"formatted_address": "Kenyatta Ave, Nairobi, Kenya", "geometry": {"location": {"lat": -1.2921, "lng": 36.8219}}, "place_id": "ChIJp0lN2HIRLxgRTJKXslQCz_c"}], "status": "OK"}
     * @responseField results object[] Geocoding results from Google.
     * @responseField status string Google status code (e.g. `OK`, `ZERO_RESULTS`).
     */
    public function geocode($latitude, $longitude)
    {
        return Http::get('https://maps.googleapis.com/maps/api/geocode/json?latlng='.$latitude.','.$longitude.'&key='.config('services.map.key'));
    }

    /**
     * Get distance and travel time
     *
     * Proxies the Google Distance Matrix API between an origin and a destination and returns its raw JSON response.
     *
     * @urlParam origin_lat number required Origin latitude. Example: -1.2921
     * @urlParam origin_long number required Origin longitude. Example: 36.8219
     * @urlParam dest_lat number required Destination latitude. Example: -1.3000
     * @urlParam dest_long number required Destination longitude. Example: 36.8000
     *
     * @response 200 {"destination_addresses": ["Ngong Rd, Nairobi, Kenya"], "origin_addresses": ["Kenyatta Ave, Nairobi, Kenya"], "rows": [{"elements": [{"distance": {"text": "3.4 km", "value": 3400}, "duration": {"text": "10 mins", "value": 600}, "status": "OK"}]}], "status": "OK"}
     * @responseField rows object[] Distance/duration elements from Google.
     * @responseField status string Google status code.
     */
    public function directions($origin_latitude, $origin_longitude, $dest_latitude, $dest_longitude)
    {
        return Http::get('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$origin_latitude.','.$origin_longitude.'&destinations='.$dest_latitude.','.$dest_longitude.'&key='.config('services.map.key'));
    }

    /**
     * Autocomplete a place
     *
     * Proxies the Google Places Autocomplete API and returns its raw JSON response.
     *
     * @urlParam text string required The partial text to autocomplete. Example: Westlands
     *
     * @response 200 {"predictions": [{"description": "Westlands, Nairobi, Kenya", "place_id": "ChIJK7mWv9YQLxgRUXhOcm8zNkE"}], "status": "OK"}
     * @responseField predictions object[] Place predictions from Google.
     * @responseField status string Google status code.
     */
    public function autocomplete($text)
    {
        return Http::get("https://maps.googleapis.com/maps/api/place/autocomplete/json?input=".$text."&key=".config('services.map.key'));
    }

    /**
     * Get place details
     *
     * Proxies the Google Place Details API and returns its raw JSON response.
     *
     * @urlParam place_id string required The Google place ID. Example: ChIJK7mWv9YQLxgRUXhOcm8zNkE
     *
     * @response 200 {"result": {"formatted_address": "Westlands, Nairobi, Kenya", "geometry": {"location": {"lat": -1.2676, "lng": 36.8108}}, "name": "Westlands", "place_id": "ChIJK7mWv9YQLxgRUXhOcm8zNkE"}, "status": "OK"}
     * @responseField result object Place details from Google.
     * @responseField status string Google status code.
     */
    public function place($place_id)
    {
        return Http::get('https://maps.googleapis.com/maps/api/place/details/json?place_id='.$place_id.'&key='.config('services.map.key'));
    }
}
