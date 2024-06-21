<?php

namespace App\Http\Controllers;

use App\Mail\NewMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

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
     * Geocode
     * @urlParam latitude string required The latitude
     * @urlParam longitude string required The longitude
     */
    public function geocode($latitude, $longitude)
    {
        return Http::get('https://maps.googleapis.com/maps/api/geocode/json?latlng='.$latitude.','.$longitude.'&key='.config('services.map.key'));
    }

    /**
     * Directions
     * @urlParam origin_latitude required string The origin latitude
     * @urlParam origin_longitude required string The origin longitude
     * @urlParam dest_latitude required string The destination latitude
     * @urlParam dest_longitude required string The destination longitude
     */
    public function directions($origin_latitude, $origin_longitude, $dest_latitude, $dest_longitude)
    {
        return Http::get('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$origin_latitude.','.$origin_longitude.'&destinations='.$dest_latitude.','.$dest_longitude.'&key='.config('services.map.key'));
    }

    /**
     * Autocomplete
     * @urlParam text string required The text to be autocompleted
     */
    public function autocomplete($text)
    {
        return Http::get("https://maps.googleapis.com/maps/api/place/autocomplete/json?input=".$text."&key=".config('services.map.key'));
    }

    /**
     * Get Place Details
     * @urlParam place_id string required The Place ID to retrieve
     */
    public function place($place_id)
    {
        return Http::get('https://maps.googleapis.com/maps/api/place/details/json?place_id='.$place_id.'&key='.config('services.map.key'));
    }
}
