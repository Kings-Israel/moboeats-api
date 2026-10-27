<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RestaurantBookmark;
use App\Http\Requests\V1\StoreRestaurantBookmarkRequest;
use App\Http\Resources\V1\RestaurantBookMarkResource;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @group Bookmarks
 *
 * Customer bookmarks (favourites) for restaurants.
 */
class RestaurantBookmarkController extends Controller
{
    use HttpResponses;
    /**
     * List restaurant bookmarks
     *
     * Returns the authenticated customer's bookmarked restaurants (paginated). Only users with role `orderer` may call this.
     * Bookmarks whose restaurant is not active (status 2) have a `null` restaurant.
     *
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "data": [{"id": 4, "uuid": "e5f6a7b8-c9d0-4e1f-8a2b-3c4d5e6f7a8b", "relationships": {"restaurant": {"id": 1, "uuid": "5e6f7a8b-9c0d-4e1f-a2b3-c4d5e6f7a8b9", "attributes": {"name": "Mama Oliech", "nameShort": "MO", "email": "info@mamaoliech.co.ke", "about": "Authentic Kenyan cuisine", "phoneNo": "254712345678", "address": "Marcus Garvey Rd", "city": "Nairobi", "latitude": "-1.2921", "longitude": "36.8219", "logo": "https://moboeats.com/storage/logos/logo.png", "status": "2", "average_rating": 4.2, "is_open": true, "country": "Kenya", "country_code": "KE"}, "relationships": {"menus": [], "orders_count": 10, "menus_count": 5}}}}],
     *   "links": {"first": "https://moboeats.com/api/v1/orderer/restaurant-bookmark?page=1", "last": "https://moboeats.com/api/v1/orderer/restaurant-bookmark?page=1", "prev": null, "next": null},
     *   "meta": {"current_page": 1, "from": 1, "last_page": 1, "links": [], "path": "https://moboeats.com/api/v1/orderer/restaurant-bookmark", "per_page": 15, "to": 1, "total": 1}
     * }
     * @response 401 scenario="Not an orderer" {"status": "Error has occured...", "message": "Unauthorized. This is a customer feature only.", "data": ""}
     * @response 401 scenario="Role mismatch" {"status": "Error has occured...", "message": "Unauthorized", "data": ""}
     * @responseField data object[] The bookmarks.
     * @responseField data[].relationships.restaurant object The bookmarked restaurant.
     */
    public function index()
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'orderer') {
                $favorites = RestaurantBookmark::where('user_id', Auth::user()->id)
                                                ->with(['restaurant' => function ($query) {
                                                    $query->where('status', 2);
                                                }, 'user'])
                                                ->paginate();
                return RestaurantBookMarkResource::collection($favorites);
            } else {
                return $this->error('', 'Unauthorized. This is a customer feature only.', 401);
            }
        } else {
            return $this->error('', 'Unauthorized', 401);
        }
    }


    /**
     * Bookmark a restaurant
     *
     * Adds a restaurant to the authenticated customer's bookmarks. Only users with role `orderer` may call this.
     *
     * @bodyParam restaurantId integer required The ID of the restaurant to bookmark. Example: 1
     *
     * @response 201 scenario="Created" {"data": {"id": 4, "uuid": "e5f6a7b8-c9d0-4e1f-8a2b-3c4d5e6f7a8b", "relationships": {"restaurant": {"id": 1, "uuid": "5e6f7a8b-9c0d-4e1f-a2b3-c4d5e6f7a8b9", "attributes": {"name": "Mama Oliech", "nameShort": "MO", "email": "info@mamaoliech.co.ke", "about": "Authentic Kenyan cuisine", "phoneNo": "254712345678", "address": "Marcus Garvey Rd", "city": "Nairobi", "latitude": "-1.2921", "longitude": "36.8219", "logo": "https://moboeats.com/storage/logos/logo.png", "status": "2", "average_rating": 4.2, "is_open": true, "country": "Kenya", "country_code": "KE"}, "relationships": {"menus": [], "orders_count": 10, "menus_count": 5}}}}}
     * @response 401 scenario="Not an orderer" {"status": "Error has occured...", "message": "Unauthorized", "data": ""}
     * @response 402 scenario="Already bookmarked" {"status": "Error has occured...", "message": "Item already exists in the bookmark", "data": "Bookmark"}
     * @response 403 scenario="Server error" {"status": "Error has occured...", "message": "SQLSTATE[HY000]: General error", "data": ""}
     * @response 422 scenario="Validation error" {"message": "The restaurant id field is required.", "errors": {"restaurantId": ["The restaurant id field is required."]}}
     * @responseField data.uuid string The bookmark UUID.
     */
    public function store(StoreRestaurantBookmarkRequest $request)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role != 'orderer') {
                return $this->error('', 'Unauthorized', 401);
            }
            try {
                DB::beginTransaction();
                if (RestaurantBookmark::where('restaurant_id', $request->restaurant_id)->where('user_id', Auth::user()->id)->exists()) {
                    return $this->error('Bookmark', 'Item already exists in the bookmark', 402);
                }
                $request->merge([
                    'user_id' => Auth::user()->id,
                    'status' => 2,
                ]);
                $resBookmark = RestaurantBookmark::create($request->all());
                DB::commit();
                return new RestaurantBookMarkResource($resBookmark->loadMissing(['restaurant', 'user']));
            } catch (\Throwable $th) {
                info($th);
                DB::rollBack();
                return $this->error('', $th->getMessage(), 403);
            }
        } else {
            return $this->error('', 'Unauthorized', 401);
        }
    }

    /**
     * Get a restaurant bookmark
     *
     * Returns a single restaurant bookmark with its restaurant.
     *
     * @urlParam restaurant_bookmark string required The UUID of the bookmark. Example: e5f6a7b8-c9d0-4e1f-8a2b-3c4d5e6f7a8b
     *
     * @response 200 {"data": {"id": 4, "uuid": "e5f6a7b8-c9d0-4e1f-8a2b-3c4d5e6f7a8b", "relationships": {"restaurant": {"id": 1, "uuid": "5e6f7a8b-9c0d-4e1f-a2b3-c4d5e6f7a8b9", "attributes": {"name": "Mama Oliech", "nameShort": "MO", "email": "info@mamaoliech.co.ke", "about": "Authentic Kenyan cuisine", "phoneNo": "254712345678", "address": "Marcus Garvey Rd", "city": "Nairobi", "latitude": "-1.2921", "longitude": "36.8219", "logo": "https://moboeats.com/storage/logos/logo.png", "status": "2", "average_rating": 4.2, "is_open": true, "country": "Kenya", "country_code": "KE"}, "relationships": {"menus": [], "orders_count": 10, "menus_count": 5}}}}}
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\RestaurantBookmark] e5f6a7b8-c9d0-4e1f-8a2b-3c4d5e6f7a8b"}
     * @responseField data.relationships.restaurant object The bookmarked restaurant.
     */
    public function show(RestaurantBookmark $restaurantBookmark)
    {
        if ($this->isNotAuthorized($restaurantBookmark)) {
            return new RestaurantBookMarkResource($restaurantBookmark->loadMissing(['restaurant', 'user']));
        } else {
            return $this->isNotAuthorized($restaurantBookmark);
        }
    }


    /**
     * Remove a restaurant bookmark
     *
     * Deletes one of the authenticated customer's restaurant bookmarks. Only users with role `orderer` may call this.
     *
     * @urlParam restaurant_bookmark string required The UUID of the bookmark. Example: e5f6a7b8-c9d0-4e1f-8a2b-3c4d5e6f7a8b
     *
     * @response 204 scenario="Deleted" ""
     * @response 401 scenario="Not an orderer" {"status": "Error has occured...", "message": "Unauthorized", "data": ""}
     * @response 403 scenario="Not owner" {"status": "Error has occured...", "message": "You are not authorized to make this request", "data": ""}
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\RestaurantBookmark] e5f6a7b8-c9d0-4e1f-8a2b-3c4d5e6f7a8b"}
     */
    public function destroy(RestaurantBookmark $restaurantBookmark)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role != 'orderer') {
                return $this->error('', 'Unauthorized', 401);
            }
            try {
                DB::beginTransaction();
                if ($this->isNotAuthorized($restaurantBookmark)) {
                    $restaurantBookmark->delete();
                } else {
                    return $this->isNotAuthorized($restaurantBookmark);
                }
                DB::commit();
                return response(null, 204);
            } catch (\Throwable $th) {
                info($th);
                DB::rollBack();
                return $this->error('', $th->getMessage(), 403);
            }
        }
    }

    public function isNotAuthorized($restaurantBookmark)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'orderer') {
                if (Auth::user()->id == $restaurantBookmark->user_id) {
                    return true;
                } else {
                    return $this->error('', 'You are not authorized to make this request', 403);
                }
            } else {
                return false;
                if (Auth::user()->id != $restaurantBookmark->user_id) {
                    return $this->error('', 'You are not authorized to make this request', 403);
                }
            }
        }
    }
}
