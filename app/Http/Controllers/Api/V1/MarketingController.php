<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdvertPoster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @group Marketing
 *
 * Advert posters shown in the apps. Listing is public (`v1/ads`); management is under `v1/admin/marketing`.
 */
class MarketingController extends Controller
{
    /**
     * List advert posters
     *
     * Returns advert posters, 10 per page. Public at `GET v1/ads`; also available to admins at `GET v1/admin/marketing`.
     *
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "posters": {
     *     "current_page": 1,
     *     "data": [{"id": 1, "title": "Weekend Deals", "description": "20% off all burgers", "poster": "poster-1.jpg", "link": "https://moboeats.com/deals", "created_at": "2024-03-11T10:00:00.000000Z", "updated_at": "2024-03-11T10:00:00.000000Z"}],
     *     "first_page_url": "https://moboeats.com/api/v1/ads?page=1",
     *     "from": 1,
     *     "last_page": 1,
     *     "last_page_url": "https://moboeats.com/api/v1/ads?page=1",
     *     "links": [],
     *     "next_page_url": null,
     *     "path": "https://moboeats.com/api/v1/ads",
     *     "per_page": 10,
     *     "prev_page_url": null,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     * @responseField posters object Paginated advert posters (Laravel paginator).
     */
    public function index()
    {
        $posters = AdvertPoster::paginate(10);

        return response()->json([
            'posters' => $posters,
        ]);
    }

    /**
     * Create an advert poster
     *
     * Admin endpoint. Uploads a new advert poster.
     *
     * @group Admin - Marketing
     * @bodyParam poster file required Poster image (png or jpg, max 10 MB).
     * @bodyParam title string Poster title. Example: Weekend Deals
     * @bodyParam description string Poster description. Example: 20% off all burgers
     * @bodyParam link string URL the poster links to. Example: https://moboeats.com/deals
     *
     * @response 200 scenario="Created" {"message": "Ad Poster added successfully"}
     * @response 400 scenario="Validation error" {"poster": ["The poster field is required."]}
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'poster' => ['required', 'max:10000', 'mimes:png,jpg'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        AdvertPoster::create([
            'title' => $request->has('title') ? $request->title : NULL,
            'description' => $request->has('description') && !empty($request->description) ? $request->description : NULL,
            'poster' => pathinfo($request->poster->store('poster', 'ad'), PATHINFO_BASENAME),
            'link' => $request->has('link') && !empty($request->link) ? $request->link : NULL
        ]);

        return response()->json(['message' => 'Ad Poster added successfully']);
    }

    /**
     * Update an advert poster
     *
     * Admin endpoint. Updates an advert poster's details and, optionally, its image. `title` is cleared when not sent;
     * `description` and `link` keep their previous value when empty.
     *
     * @group Admin - Marketing
     * @urlParam advert_poster integer required The numeric ID of the advert poster. Example: 1
     * @bodyParam poster file New poster image (png or jpg, max 10 MB).
     * @bodyParam title string Poster title. Example: Weekend Deals
     * @bodyParam description string Poster description. Example: 20% off all burgers
     * @bodyParam link string URL the poster links to. Example: https://moboeats.com/deals
     *
     * @response 200 scenario="Updated" {"message": "Ad Poster updated successfully"}
     * @response 400 scenario="Validation error" {"poster": ["The poster field must be a file of type: png, jpg."]}
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\AdvertPoster] 99"}
     */
    public function update(Request $request, AdvertPoster $advert_poster)
    {
        $validator = Validator::make($request->all(), [
            'poster' => ['nullable', 'max:10000', 'mimes:png,jpg'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        $advert_poster->update([
            'title' => $request->has('title') ? $request->title : NULL,
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

    /**
     * Delete an advert poster
     *
     * Admin endpoint. Deletes an advert poster and its image file.
     *
     * @group Admin - Marketing
     * @urlParam advert_poster integer required The numeric ID of the advert poster. Example: 1
     *
     * @response 200 scenario="Deleted" {"message": "Ad Poster deleted successfully"}
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\AdvertPoster] 99"}
     */
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
