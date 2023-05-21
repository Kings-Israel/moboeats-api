<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Restaurant;
use App\Http\Requests\V1\UpdateRestaurantRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RestaurantCollection;
use App\Http\Resources\V1\RestaurantResource;
use App\Filters\V1\RestaurantFilter;
use App\Http\Requests\V1\StoreRestaurantRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RestaurantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter =  new RestaurantFilter();
        $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
        $includeQuestionnaire = $request->query('questionnaire');

        $restaurants = Restaurant::where($filterItems);
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
        try {
            DB::beginTransaction();
            $restaurant = Restaurant::create($request->all());
            DB::commit();
            return new RestaurantResource($restaurant);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
        }
       
    }

    /**
     * Display the specified resource.
     */
    public function show(Restaurant $restaurant)
    {
        return new RestaurantResource($restaurant);
    }

    
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant)
    {
        try {
            DB::beginTransaction();
            $restaurant->update($request->all());

            DB::commit();
            return new RestaurantResource($restaurant);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Restaurant $restaurant)
    {
        //
    }
}
