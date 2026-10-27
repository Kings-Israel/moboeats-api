<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MenuBookmark;
use App\Http\Requests\V1\StoreMenuBookmarkRequest;
use App\Http\Requests\V1\UpdateMenuBookmarkRequest;
use App\Http\Resources\V1\MenuBookMarkResource;
use App\Http\Resources\V1\MenuBookMarkCollection;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Bookmarks
 *
 * Customer bookmarks (favourites) for menu items.
 */
class MenuBookmarkController extends Controller
{
    use HttpResponses;

    /**
     * List menu bookmarks
     *
     * Returns the authenticated customer's bookmarked menu items (paginated). Only users with role `orderer` may call this.
     * Bookmarks whose menu is not active (status 2) have a `null` menu.
     *
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "data": [{"id": 7, "uuid": "d4c3b2a1-0f9e-4d8c-b7a6-5f4e3d2c1b0a", "relationships": {"menu": {"id": 10, "uuid": "a1b2c3d4-e5f6-4789-90ab-cdef01234567", "attributes": {"title": "Chicken Burger", "description": "Grilled chicken burger", "createdBy": "3", "updatedBy": "3", "status": "2", "preparation_time": "20", "average_rating": 4.5}, "relationships": {"images": [], "menuPrices": [], "orders_count": 0, "total_orders_value": 0}}}}],
     *   "links": {"first": "https://moboeats.com/api/v1/orderer/menu-bookmark?page=1", "last": "https://moboeats.com/api/v1/orderer/menu-bookmark?page=1", "prev": null, "next": null},
     *   "meta": {"current_page": 1, "from": 1, "last_page": 1, "links": [], "path": "https://moboeats.com/api/v1/orderer/menu-bookmark", "per_page": 15, "to": 1, "total": 1}
     * }
     * @response 401 scenario="Not an orderer" {"status": "Error has occured...", "message": "Unauthorized. This is a customer feature only.", "data": ""}
     * @response 401 scenario="Role mismatch" {"status": "Error has occured...", "message": "Unauthorized", "data": ""}
     * @responseField data object[] The bookmarks.
     * @responseField data[].relationships.menu object The bookmarked menu item.
     */
    public function index(Request $request)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'orderer') {
                $favorites = MenuBookmark::where('user_id', Auth::user()->id)
                ->with(['menu' => function ($query) {
                    $query->where('status', 2);
                }, 'user'])
                ->paginate();
                return new MenuBookMarkCollection($favorites);
            } else {
                return $this->error('', 'Unauthorized. This is a customer feature only.', 401);
            }
        } else {
            return $this->error('', 'Unauthorized', 401);
        }
    }


    /**
     * Bookmark a menu item
     *
     * Adds a menu item to the authenticated customer's bookmarks. Only users with role `orderer` may call this.
     *
     * @bodyParam menuId integer required The ID of the menu item to bookmark. Example: 10
     *
     * @response 201 scenario="Created" {"data": {"id": 7, "uuid": "d4c3b2a1-0f9e-4d8c-b7a6-5f4e3d2c1b0a", "relationships": {"menu": {"id": 10, "uuid": "a1b2c3d4-e5f6-4789-90ab-cdef01234567", "attributes": {"title": "Chicken Burger", "description": "Grilled chicken burger", "createdBy": "3", "updatedBy": "3", "status": "2", "preparation_time": "20", "average_rating": 4.5}, "relationships": {"images": [], "menuPrices": [], "orders_count": 0, "total_orders_value": 0}}}}}
     * @response 401 scenario="Not an orderer" {"status": "Error has occured...", "message": "Unauthorized", "data": ""}
     * @response 402 scenario="Already bookmarked" {"status": "Error has occured...", "message": "Item already exists in the bookmark", "data": "Bookmark"}
     * @response 403 scenario="Server error" {"status": "Error has occured...", "message": "SQLSTATE[HY000]: General error", "data": ""}
     * @response 422 scenario="Validation error" {"message": "The menu id field is required.", "errors": {"menuId": ["The menu id field is required."]}}
     * @responseField data.uuid string The bookmark UUID.
     */
    public function store(StoreMenuBookmarkRequest $request)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role != 'orderer') {
                return $this->error('', 'Unauthorized', 401);
            }
            try {
                DB::beginTransaction();
                if (MenuBookmark::where('menu_id', $request->menu_id)->where('user_id', Auth::user()->id)->exists()) {
                    return $this->error('Bookmark', 'Item already exists in the bookmark', 402);
                }
                $request->merge([
                    'user_id' => Auth::user()->id,
                    'status' => 2,
                ]);
                $menuBookmark = MenuBookmark::create($request->all());
                DB::commit();
                // $menuBookmark = $menuBookmark->with(['menu', 'user']);
                return new MenuBookMarkResource($menuBookmark->loadMissing(['menu', 'user']));
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
     * Get a menu bookmark
     *
     * Returns a single menu bookmark with its menu item.
     *
     * @urlParam menu_bookmark string required The UUID of the bookmark. Example: d4c3b2a1-0f9e-4d8c-b7a6-5f4e3d2c1b0a
     *
     * @response 200 {"data": {"id": 7, "uuid": "d4c3b2a1-0f9e-4d8c-b7a6-5f4e3d2c1b0a", "relationships": {"menu": {"id": 10, "uuid": "a1b2c3d4-e5f6-4789-90ab-cdef01234567", "attributes": {"title": "Chicken Burger", "description": "Grilled chicken burger", "createdBy": "3", "updatedBy": "3", "status": "2", "preparation_time": "20", "average_rating": 4.5}, "relationships": {"images": [], "menuPrices": [], "orders_count": 0, "total_orders_value": 0}}}}}
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\MenuBookmark] d4c3b2a1-0f9e-4d8c-b7a6-5f4e3d2c1b0a"}
     * @responseField data.relationships.menu object The bookmarked menu item.
     */
    public function show(MenuBookmark $menuBookmark)
    {
        if ($this->isNotAuthorized($menuBookmark)) {
            return new MenuBookMarkResource($menuBookmark->loadMissing(['menu', 'user']));
        } else {
            return $this->isNotAuthorized($menuBookmark);
        }

    }


    /**
     * Remove a menu bookmark
     *
     * Deletes one of the authenticated customer's menu bookmarks. Only users with role `orderer` may call this.
     * Reachable both as `DELETE v1/orderer/menu-bookmark/{menu_bookmark}` and `GET v1/orderer/menu-bookmark/{menuId}/delete`;
     * in both cases the placeholder is the bookmark's numeric ID (not the menu ID).
     *
     * @urlParam menuId integer The numeric ID of the bookmark (GET `.../{menuId}/delete` route). Example: 7
     * @urlParam menu_bookmark integer The numeric ID of the bookmark (DELETE route). Example: 7
     *
     * @response 204 scenario="Deleted" ""
     * @response 401 scenario="Not an orderer" {"status": "Error has occured...", "message": "Unauthorized", "data": ""}
     * @response 403 scenario="Not owner" {"status": "Error has occured...", "message": "You are not authorized to make this request", "data": ""}
     * @response 403 scenario="Bookmark not found / server error" {"status": "Error has occured...", "message": "Attempt to read property \"user_id\" on null", "data": ""}
     */
    // public function destroy(MenuBookmark $menuBookmark)
    public function destroy($menu_id)
    {
        $user = User::where('id',Auth::user()->id)->first();

        $menuBookmark = MenuBookmark::find($menu_id);
        
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role != 'orderer') {
                return $this->error('', 'Unauthorized', 401);
            }
            try {
                DB::beginTransaction();
                if ($this->isNotAuthorized($menuBookmark)) {
                    $menuBookmark->delete();
                } else {
                    return $this->isNotAuthorized($menuBookmark);
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

    public function isNotAuthorized($menuBookmark)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'orderer') {
                if (Auth::user()->id == $menuBookmark->user_id) {
                    return true;
                } else {
                    return $this->error('', 'You are not authorized to make this request', 403);
                }
            } else {
                return false;
                if (Auth::user()->id != $menuBookmark->user_id) {
                    return $this->error('', 'You are not authorized to make this request', 403);
                }
            }
        }
    }
}
