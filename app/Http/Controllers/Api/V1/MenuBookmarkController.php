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
 * @group Customer menu Bookmark Management
 *
 * MenuBookmark API resource
 */
class MenuBookmarkController extends Controller
{
    use HttpResponses;

    /**
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
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
     * Display the specified resource.
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
     * Remove the specified resource from storage.
     */
    public function destroy(MenuBookmark $menu_bookmark)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role != 'orderer') {
                return $this->error('', 'Unauthorized', 401);
            }
            try {
                DB::beginTransaction();
                if ($this->isNotAuthorized($menu_bookmark)) {
                    $menu_bookmark->delete();
                } else {
                    return $this->isNotAuthorized($menu_bookmark);
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
