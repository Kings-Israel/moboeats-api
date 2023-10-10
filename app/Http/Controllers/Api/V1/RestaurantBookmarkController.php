<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RestaurantBookmark;
use App\Http\Requests\V1\StoreRestaurantBookmarkRequest;
use App\Http\Requests\V1\UpdateRestaurantBookmarkRequest;
use App\Http\Resources\V1\RestaurantBookmarkResource;
use App\Http\Resources\V1\RestaurantBookmarkCollection;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @group Customer Restaurants Bookmark Management
 *
 * RestaurantBookmark API resource
 */
class RestaurantBookmarkController extends Controller
{
    use HttpResponses;
    /**
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
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
     * Display the specified resource.
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
     * Remove the specified resource from storage.
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
