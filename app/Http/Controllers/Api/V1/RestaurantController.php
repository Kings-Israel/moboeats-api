<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Restaurant;
use App\Http\Requests\V1\UpdateRestaurantRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RestaurantCollection;
use App\Http\Resources\V1\RestaurantResource;
use App\Filters\V1\RestaurantFilter;
use App\Http\Requests\V1\StoreRestaurantRequest;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RestaurantController extends Controller
{
    use HttpResponses;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter =  new RestaurantFilter();
        $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
        $includeQuestionnaire = $request->query('questionnaire');

        $restaurants = Restaurant::where('user_id', Auth::user()->id)
        ->where($filterItems);
        if ($includeQuestionnaire) {
            $restaurants = $restaurants->with('questionnaire');
        } 
        return new RestaurantCollection($restaurants->paginate()->appends($request->query()));
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRestaurantRequest $request)
    {
        info(Auth::user()->id);
        try {
            DB::beginTransaction();
            $restaurant = Restaurant::create($request->all(), [
                'user_id' => Auth::user()->id
            ]);
            DB::commit();
            return new RestaurantResource($restaurant);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
       
    }

    /**
     * Display the specified resource.
     */
    public function show(Restaurant $restaurant)
    {
        return $this->isNotAuthorized($restaurant) ?  $this->isNotAuthorized($restaurant) : new RestaurantResource($restaurant);
    }

    
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant)
    {
        try {
            DB::beginTransaction();
            if ($this->isNotAuthorized($restaurant)) {
                return $this->isNotAuthorized($restaurant);
            }
            $restaurant->update($request->all());

            DB::commit();
            return new RestaurantResource($restaurant);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Restaurant $restaurant)
    {
        try {
            DB::beginTransaction();
            return $this->isNotAuthorized($restaurant) ?  $this->isNotAuthorized($restaurant) :  $restaurant->delete();
            DB::commit();
            // return response(null, 204);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
        
    }

    public function isNotAuthorized($restaurant)
    {
        if (Auth::user()->id !== $restaurant->user_id) {
            return $this->error('', 'You are not authorized to make this request', 403);
        }
    }
}
