<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdvertPoster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MarketingController extends Controller
{
    public function index()
    {
        $posters = AdvertPoster::paginate(10);

        return response()->json([
            'posters' => $posters,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required'],
            'poster' => ['required', 'max:10000', 'mimes:png,jpg'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        AdvertPoster::create([
            'title' => $request->title,
            'description' => $request->has('description') && !empty($request->description) ? $request->description : NULL,
            'poster' => pathinfo($request->poster->store('poster', 'ad'), PATHINFO_BASENAME),
            'link' => $request->has('link') && !empty($request->link) ? $request->link : NULL
        ]);

        return response()->json(['message' => 'Ad Poster added successfully']);
    }

    public function update(Request $request, AdvertPoster $advert_poster)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required'],
            'poster' => ['nullable', 'max:10000', 'mimes:png,jpg'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        $advert_poster->update([
            'title' => $request->title,
            'description' => $request->has('description') && !empty($request->description) ? $request->description : $advert_poster->description,
            'link' => $request->has('link') && !empty($request->link) ? $request->link : $advert_poster->link,
        ]);

        if ($request->hasFile('poster')) {
            self::deleteFile($advert_poster);
            $advert_poster->update([
                'poster' => $request->hasFile('poster')
            ]);
        }

        return response()->json(['message' => 'Ad Poster updated successfully']);
    }

    public function delete(AdvertPoster $advert_poster)
    {
        self::deleteFile($advert_poster);

        $advert_poster->delete();

        return response()->json(['message' => 'Ad Poster deleted successfully']);
    }

    private function deleteFile(AdvertPoster $advert_poster)
    {
        $poster = explode('/', $advert_poster->poster);

        Storage::disk('ad')->delete('poster/'.end($poster));
    }
}
