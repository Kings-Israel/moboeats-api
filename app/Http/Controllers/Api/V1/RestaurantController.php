<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Restaurant;
use App\Http\Requests\V1\UpdateRestaurantRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RestaurantCollection;
use App\Http\Resources\V1\RestaurantResource;
use App\Filters\V1\RestaurantFilter;
use App\Http\Requests\V1\StoreRestaurantRequest;
use App\Http\Resources\V1\RiderCollection;
use App\Http\Resources\V1\UserCollection;
use App\Http\Resources\V1\UserResource;
use App\Models\Order;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\Admin\UploadFileTrait;
use Illuminate\Support\Facades\Http;

/**
 * @group Restaurant Management
 *
 * Restaurant API resource
 */

class RestaurantController extends Controller
{
    use HttpResponses;
    use UploadFileTrait;

    public $settings = [
        'model' =>  '\\App\\Models\\Restaurant',
        'caption' =>  "Restaurant",
        'storageName' =>  "companyLogos",
    ];
    /**
     * Display a listing of the resource.
     *
     * @queryParam questionnaire to fetch associated restaurant questionnaire answers
     */
    public function index(Request $request)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;

            if ($role === 'orderer') {
                $radius = 10;
                $latitude = $request->latitude;
                $longitude = $request->longitude;
                $filter =  new RestaurantFilter();
                $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
                $includeQuestionnaire = $request->query('questionnaire');
                $restaurants = Restaurant::where($filterItems);

                // $restaurants = Restaurant::select(DB::raw("*,
                //             (6371 * acos(cos(radians($request->latitude))
                //             * cos(radians(latitude))
                //             * cos(radians(longitude)
                //             - radians($request->longitude))
                //             + sin(radians($request->latitude))
                //             * sin(radians(latitude))))
                //             AS distance"))
                //     ->having('distance', '<=', $radius)
                //     ->orderBy('distance');


                // if ($includeQuestionnaire) {
                //     $restaurants = $restaurants->with('questionnaire');
                // }
                return new RestaurantCollection($restaurants->with('questionnaire')->paginate());
            }
            if ($role === 'restaurant') {
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

        } else {
            return $this->error('', 'Unauthorized', 401);
        }


    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRestaurantRequest $request)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'orderer') {
                return $this->error('', 'Unauthorized', 401);
            }

            try {
                DB::beginTransaction();
                $fileName= '';
                if($request->hasFile('logo')){
                    $fileName = $this->generateFileName2($request->file('logo'));
                }
                $request->merge([
                    'user_id' => Auth::user()->id,
                    'logo' => $fileName
                ]);
                $restaurant = Restaurant::create($request->all());
                if($request->hasFile('logo')){
                    $fileData = ['file' => $request->file('logo'),'fileName' => $fileName, 'storageName' => $this->settings['storageName'].'\\logos','prevFile' => null];
                    if(!$this->uploadFile($fileData)){
                        DB::rollBack();
                    }
                }
                DB::commit();
                return new RestaurantResource($restaurant);
            } catch (\Throwable $th) {
                info($th);
                DB::rollBack();
                return $this->error('', $th->getMessage(), 403);
            }
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Restaurant $restaurant)
    {
        // $restaurant = Restaurant::where('id', $restaurant)->orWhere('uuid', $restaurant)->first();
        return $this->isNotAuthorized($restaurant) ?  $this->isNotAuthorized($restaurant) : new RestaurantResource($restaurant);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'orderer') {
                return $this->error('', 'Unauthorized', 401);
            }

            try {
                DB::beginTransaction();
                if ($this->isNotAuthorized($restaurant)) {
                    return $this->isNotAuthorized($restaurant);
                }
                info('Authorized');
                if($request->hasFile('logo')){
                    $fileName = $this->generateFileName2($request->file('logo'));
                    $restaurant->update($request->all(),['logo' => $fileName]);
                    if($request->hasFile('logo')){
                        $fileData = ['file' => $request->file('logo'),'fileName' => $fileName, 'storageName' => $this->settings['storageName'].'\\logos','prevFile' => null];
                        if(!$this->uploadFile($fileData)){
                            DB::rollBack();
                        }
                    }
                } else {
                    $restaurant->update($request->all());
                }

                DB::commit();
                return new RestaurantResource($restaurant);
            } catch (\Throwable $th) {
                info($th);
                DB::rollBack();
                return $this->error('', $th->getMessage(), 403);
            }
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Restaurant $restaurant)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'orderer') {
                return $this->error('', 'Unauthorized', 401);
            }
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
    }

    public function isNotAuthorized($restaurant)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'orderer') {
                return '';
            } else {
                if (Auth::user()->id !== $restaurant->user_id) {
                    return $this->error('', 'You are not authorized to make this request', 401);
                } else {
                    return '';
                }
            }
        }
        return '';
    }

    public function riders($restaurant_id)
    {
        $restaurant = Restaurant::find($restaurant_id);

        // Get Unassigned Couriers and order by closest
        $riders = User::where('device_token', '!=', NULL)
                        ->whereHas('roles', function($query) {
                            $query->where('name', 'rider');
                        })
                        // ->where('status', 'Active')
                        ->where(function($query) {
                            $assigned_riders = Order::where('rider_id', '!=', NULL)->get()->pluck('rider_id');
                            $query->whereNotIn('id', $assigned_riders);
                        })
                        ->get()
                        // Filter by distance to restaurant
                        ->each(function($rider, $key) use ($restaurant) {
                            if ($rider->latitude != NULL && $rider->lognitude != NULL) {
                                $business_location = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$rider->latitude.','.$rider->longitude.'&destinations='.$restaurant->latitude.','.$restaurant->longitude.'&key='.config('services.map.key'));

                                if (json_decode($business_location)->rows[0]->elements[0]->status != "NOT_FOUND" && json_decode($business_location)->rows[0]->elements[0]->status != "ZERO_RESULTS") {
                                    $distance = json_decode($business_location)->rows[0]->elements[0]->distance->text;
                                    $time = json_decode($business_location)->rows[0]->elements[0]->duration->text;
                                    $rider['distance'] = $distance;
                                    $rider['time_away'] = $time;
                                }
                            } else {
                                $rider['distance'] = NULL;
                                $rider['time_away'] = NULL;
                            }
                        })
                        // Order by distance and time
                        ->sortBy([
                            fn($a, $b) => (double) explode(' ', $a['distance'])[0] >= (double) explode(' ',$b['distance'])[0],
                        ]);

        return $this->success(UserResource::collection($riders));
    }
}
