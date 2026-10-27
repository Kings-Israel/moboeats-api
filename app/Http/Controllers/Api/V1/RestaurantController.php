<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Restaurant;
use App\Http\Requests\V1\UpdateRestaurantRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RestaurantCollection;
use App\Http\Resources\V1\RestaurantResource;
use App\Http\Resources\V1\ReviewResource;
use App\Filters\V1\RestaurantFilter;
use App\Http\Requests\V1\StoreRestaurantRequest;
use App\Http\Resources\V1\FoodCommonCategoryCollection;
use App\Http\Resources\V1\RiderCollection;
use App\Http\Resources\V1\UserCollection;
use App\Http\Resources\V1\UserResource;
use App\Models\FoodCommonCategory;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\UserRestaurant;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\Admin\UploadFileTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewAccount;
use App\Notifications\UpdatedRestaurant;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RestaurantExport;
use App\Exports\PaymentExport;
use App\Jobs\SendCommunication;
use App\Jobs\SendNotification;
use App\Models\Payout;
use App\Models\SeatingArea;
use App\Models\RestaurantTable;
use App\Models\Review;
use App\Models\PromoCode;
use App\Models\Discount;
use App\Models\Role;
use App\Models\Setting;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Query\JoinClause;

/**
 * @group Restaurant Management
 *
 * Restaurant listings, details, registration and the restaurant owner/employee panel (dashboard, tables,
 * reservations, payments, categories, employees). Endpoints under `/v1/restaurant` (except registering a restaurant)
 * pass through the `has_restaurant` middleware, which returns `422 {"data":"no_restaurant"}` when a user with role
 * `restaurant` has not registered any restaurant yet.
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
     * List restaurants
     *
     * Returns a paginated list of restaurants. The result depends on the caller:
     * guests (`GET /v1/restaurants`, `GET /v1/orderer/orderer-restaurants`) and users with role `orderer` get approved restaurants
     * (sorted by distance within 200,000 km when `lat`/`lng` are given) with questionnaire, reviews and tables loaded;
     * role `rider` (`GET /v1/rider/restaurants`) gets approved restaurants filtered by the filter params (15 per page);
     * role `restaurant` (`GET /v1/restaurant/restaurants`) gets only their own restaurants (any status, 10 per page, newest first) with orders and menus;
     * role `restaurant employee` gets the single restaurant they are assigned to.
     *
     * @queryParam lat number Latitude of the user, used with `lng` to sort guests/orderers by distance. Example: -1.2833
     * @queryParam lng number Longitude of the user, used with `lat`. Example: 36.8167
     * @queryParam per_page integer Items per page for guests/orderers. Defaults to 100. Example: 20
     * @queryParam search string Restaurant/employee callers only: search by name or address. Example: Kitchen
     * @queryParam status string Role `restaurant` only: filter own restaurants by raw status (1 = Pending, 2 = Approved, 3 = Denied). Orderers/riders may instead use the filter syntax `status[eq]`. Example: 2
     * @queryParam uuid[eq] string Filter by restaurant UUID. Applied for `orderer` (when no lat/lng) and `rider` callers. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     * @queryParam name[eq] string Filter by exact restaurant name. Applied for `orderer` (when no lat/lng) and `rider` callers. Example: Mama's Kitchen
     * @queryParam nameShort[eq] string Filter by exact short name. Applied for `orderer` (when no lat/lng) and `rider` callers. Example: MK
     * @queryParam email[eq] string Filter by exact email. Applied for `orderer` (when no lat/lng) and `rider` callers. Example: info@mamaskitchen.co.ke
     * @queryParam aboutShort[eq] string Filter by exact short description. Applied for `orderer` (when no lat/lng) and `rider` callers. Example: Kenyan cuisine
     * @queryParam phoneNo[eq] string Filter by exact phone number. Applied for `orderer` (when no lat/lng) and `rider` callers. Example: +254712345678
     * @queryParam address[eq] string Filter by exact address. Applied for `orderer` (when no lat/lng) and `rider` callers. Example: Moi Avenue, Nairobi
     * @queryParam city[eq] string Filter by city. Applied for `orderer` (when no lat/lng) and `rider` callers. Example: Nairobi
     * @queryParam state[eq] string Filter by state/county. Applied for `orderer` (when no lat/lng) and `rider` callers. Example: Nairobi
     * @queryParam mapLocation[eq] string Filter by map location (operators: eq, gt, lt). Applied for `orderer` (when no lat/lng) and `rider` callers. Example: Nairobi
     * @queryParam latitude[gt] string Filter by latitude (operators: eq, gt, lt). Applied for `orderer` (when no lat/lng) and `rider` callers. Example: -1.5
     * @queryParam longitude[lt] string Filter by longitude (operators: eq, gt, lt). Applied for `orderer` (when no lat/lng) and `rider` callers. Example: 37.0
     * @queryParam postalCode[eq] string Filter by postal code (operators: eq, gt, lt). Applied for `orderer` (when no lat/lng) and `rider` callers. Example: 00100
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"uuid":"9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b","attributes":{"name":"Mama's Kitchen","nameShort":"MK","email":"info@mamaskitchen.co.ke","about":"Authentic Kenyan cuisine served fresh daily.","aboutShort":"Kenyan cuisine","phoneNo":"+254712345678","address":"Moi Avenue, Nairobi","city":"Nairobi","state":"Nairobi","postalCode":"00100","latitude":"-1.2833","longitude":"36.8167","mapLocation":"Nairobi","url":"https://mamaskitchen.co.ke","logo":"https://moboeats.co.uk/storage/companyLogos/logos/logo.png","sitting_capacity":40,"status":"Approved","denied_reason":"","created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","average_rating":4.5,"is_open":true,"country":"Kenya","country_code":"KE"},"relationships":{"menus":[],"reviews":[],"restaurantTables":[]}}],"links":{"first":"http://localhost/api/v1/restaurants?page=1","last":"http://localhost/api/v1/restaurants?page=1","prev":null,"next":null},"meta":{"current_page":1,"from":1,"last_page":1,"links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"http://localhost/api/v1/restaurants?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"path":"http://localhost/api/v1/restaurants","per_page":100,"to":1,"total":1}}
     *
     * @responseField data object[] List of restaurants.
     * @responseField data[].uuid string Restaurant UUID (used as route key).
     * @responseField data[].attributes object Restaurant details (name, contact, location, status, rating, is_open...).
     * @responseField data[].relationships object Loaded relations (menus, reviews, restaurantTables, questionnaire, orders...).
     * @responseField meta object Pagination metadata.
     */
    public function index(Request $request)
    {
        $radius = 200000;
        $latitude = $request->query('lat');
        $longitude = $request->query('lng');
        $per_page = $request->query('per_page');


        if(!$per_page || $per_page == '') $per_page = 100;

        if (auth()->check()) {
            if (auth()->user()->hasRole('orderer')) {
                $filter =  new RestaurantFilter();
                $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
                if ($latitude && $longitude) {
                    $restaurants = Restaurant::Approved()->select(DB::raw("*,
                                (6371 * acos(cos(radians($latitude))
                                * cos(radians(latitude))
                                * cos(radians(longitude)
                                - radians($longitude))
                                + sin(radians($latitude))
                                * sin(radians(latitude))))
                                AS distance"))
                        ->having('distance', '<=', $radius)
                        ->orderBy('distance');
                } else {
                    $restaurants = Restaurant::Approved()->where($filterItems);
                }


                return new RestaurantCollection($restaurants->with('questionnaire', 'reviews', 'restaurantTables.seatingArea')->paginate($per_page));
            }

            if (auth()->user()->hasRole('rider')) {
                $radius = 100;
                $latitude = $request->latitude;
                $longitude = $request->longitude;
                $filter =  new RestaurantFilter();
                $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
                $includeQuestionnaire = $request->query('questionnaire');
                $restaurants = Restaurant::Approved()->where($filterItems);

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

            if (auth()->user()->hasRole('restaurant')) {
                $search = $request->query('search');
                $status = $request->query('status');

                $restaurants = Restaurant::withCount('orders', 'menus')
                                        ->with('orders.payment', 'orders.reservation', 'menus', 'restaurantTables.seatingArea')
                                        ->where('user_id', Auth::user()->id)
                                        ->when($search && $search != '', function ($query) use ($search) {
                                            $query->where(function ($query) use ($search) {
                                                $query->where('name', 'LIKE', '%'.$search.'%')
                                                        ->orWhere('address', 'LIKE', '%'.$search.'%');
                                            });
                                        })
                                        ->when($status && $status != '', function ($query) use ($status) {
                                            $query->where(function ($query) use ($status) {
                                                $query->where('status', $status);
                                            });
                                        })
                                        ->orderBy('created_at', 'DESC')
                                        ->paginate(10);

                return new RestaurantCollection($restaurants);
            }

            if (auth()->user()->hasRole('restaurant employee')) {
                $search = $request->query('search');

                $restaurant = UserRestaurant::where('user_id', auth()->id())->first();

                $restaurants = Restaurant::withCount('orders', 'menus')
                                            ->with('orders.payment', 'orders.reservation', 'menus', 'restaurantTables.seatingArea')
                                            ->where('id', $restaurant->restaurant_id)
                                            ->when($search && $search != '', function ($query) use ($search) {
                                                $query->where(function ($query) use ($search) {
                                                    $query->where('name', 'LIKE', '%'.$search.'%')
                                                            ->orWhere('address', 'LIKE', '%'.$search.'%');
                                                });
                                            })
                                            ->first();

                return new RestaurantCollection($restaurants);
            }
        } else {
            if ($latitude && $longitude) {
                $restaurants = Restaurant::Approved()
                    ->select(DB::raw("*,
                            (6371 * acos(cos(radians($latitude))
                            * cos(radians(latitude))
                            * cos(radians(longitude)
                            - radians($longitude))
                            + sin(radians($latitude))
                            * sin(radians(latitude))))
                            AS distance"))
                    ->having('distance', '<=', $radius)
                    ->orderBy('distance');
            } else {
                $restaurants = Restaurant::Approved();
            }

            return new RestaurantCollection($restaurants->with('questionnaire', 'reviews', 'restaurantTables.seatingArea')->paginate($per_page));
        }
    }

    /**
     * List rated restaurants in operation
     *
     * Public endpoint for the orderer app. Returns approved restaurants that are currently open (per operating hours),
     * have at least one active menu and at least one review, paginated 15 per page.
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"uuid":"9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b","attributes":{"name":"Mama's Kitchen","nameShort":"MK","email":"info@mamaskitchen.co.ke","about":"Authentic Kenyan cuisine served fresh daily.","aboutShort":"Kenyan cuisine","phoneNo":"+254712345678","address":"Moi Avenue, Nairobi","city":"Nairobi","state":"Nairobi","postalCode":"00100","latitude":"-1.2833","longitude":"36.8167","mapLocation":"Nairobi","url":"https://mamaskitchen.co.ke","logo":"https://moboeats.co.uk/storage/companyLogos/logos/logo.png","sitting_capacity":40,"status":"Approved","denied_reason":"","created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","average_rating":4.5,"is_open":true,"country":"Kenya","country_code":"KE"},"relationships":{"menus":[],"reviews":[],"restaurantTables":[]}}],"links":{"first":"http://localhost/api/v1/orderer/orderer-restaurants/rated?page=1","last":"http://localhost/api/v1/orderer/orderer-restaurants/rated?page=1","prev":null,"next":null},"meta":{"current_page":1,"from":1,"last_page":1,"links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"http://localhost/api/v1/orderer/orderer-restaurants/rated?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"path":"http://localhost/api/v1/orderer/orderer-restaurants/rated","per_page":15,"to":1,"total":1}}
     *
     * @responseField data object[] List of restaurants.
     * @responseField meta object Pagination metadata.
     */
    public function rating(Request $request)
    {
        $restaurants = Restaurant::InOperation()->Approved()->hasMenu()->rated();

        return new RestaurantCollection($restaurants->with('questionnaire', 'reviews', 'restaurantTables.seatingArea')->paginate());
    }

    /**
     * List seating areas
     *
     * Returns all seating area types (e.g. Indoor, Outdoor) available for restaurant tables. Requires authentication; any role.
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":[{"id":1,"name":"Outdoor","created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z"}]}
     *
     * @responseField data object[] All seating areas.
     * @responseField data[].id integer Seating area ID.
     * @responseField data[].name string Seating area name.
     */
    public function seatingAreas()
    {
        $seating_areas = SeatingArea::all();

        return $this->success($seating_areas);
    }

    /**
     * Export restaurants to Excel
     *
     * Generates and downloads an `.xlsx` file of restaurants, optionally filtered. Intended for restaurant owners.
     *
     * @queryParam search string Search term passed to the export (name/address). Example: Kitchen
     * @queryParam status string Restaurant status to filter by (1 = Pending, 2 = Approved, 3 = Denied). Example: 2
     *
     * @response 200 scenario="File download" <<binary>> The exported Excel file (restaurants{id}.xlsx).
     */
    public function export(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $unique = explode('-', Str::uuid())[0];

        Excel::store(new RestaurantExport($search, $status), 'restaurants'.$unique.'.xlsx', 'exports');

        return Storage::disk('exports')->download('restaurants'.$unique.'.xlsx');
    }

    /**
     * Register a restaurant
     *
     * Creates a restaurant owned by the authenticated user (served by `POST /v1/restaurant/restaurant` and the `restaurants` resource routes).
     * The `restaurant` role is added to the user if missing. Country is set to Kenya and the status is set to Approved.
     * A "partner account created" email is queued to the owner after the response.
     *
     * @bodyParam name string required Restaurant name. Example: Mama's Kitchen
     * @bodyParam name_short string required Short name. Example: MK
     * @bodyParam email string required Restaurant contact email (valid email). Example: info@mamaskitchen.co.ke
     * @bodyParam about string required Full description. Example: Authentic Kenyan cuisine served fresh daily.
     * @bodyParam about_short string required Short description. Example: Kenyan cuisine
     * @bodyParam phone_no string Phone number. Example: +254712345678
     * @bodyParam address string Street address. Example: Moi Avenue, Nairobi
     * @bodyParam city string City. Example: Nairobi
     * @bodyParam state string State/county. Example: Nairobi
     * @bodyParam postal_code string Postal code. Example: 00100
     * @bodyParam latitude string Latitude of the restaurant. Example: -1.2833
     * @bodyParam longitude string Longitude of the restaurant. Example: 36.8167
     * @bodyParam url string Website URL. Example: https://mamaskitchen.co.ke
     * @bodyParam logo file required Restaurant logo image.
     * @bodyParam map_location string Ignored; always set to `Nairobi`. Example: Nairobi
     * @bodyParam sitting_capacity integer Seating capacity. Example: 40
     * @bodyParam paypal_email string PayPal/Venmo email for payouts. Example: payouts@mamaskitchen.co.ke
     *
     * @response 201 scenario="Created" {"data":{"id":1,"uuid":"9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b","attributes":{"name":"Mama's Kitchen","nameShort":"MK","email":"info@mamaskitchen.co.ke","about":"Authentic Kenyan cuisine served fresh daily.","aboutShort":"Kenyan cuisine","phoneNo":"+254712345678","address":"Moi Avenue, Nairobi","city":"Nairobi","state":"Nairobi","postalCode":"00100","latitude":"-1.2833","longitude":"36.8167","mapLocation":"Nairobi","url":"https://mamaskitchen.co.ke","logo":"https://moboeats.co.uk/storage/companyLogos/logos/logo.png","sitting_capacity":40,"status":"Approved","denied_reason":"","created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","average_rating":4.5,"is_open":true,"country":"Kenya","country_code":"KE"},"relationships":{"menus":[],"reviews":[],"restaurantTables":[]}}}
     * @response 403 scenario="Creation failed" {"status":"Error has occured...","message":"SQLSTATE[23000]: Integrity constraint violation","data":""}
     * @response 422 scenario="Validation error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     *
     * @responseField data.id integer Restaurant ID.
     * @responseField data.uuid string Restaurant UUID.
     * @responseField data.attributes object Restaurant details.
     */
    public function store(StoreRestaurantRequest $request)
    {
        $role = Role::where('name', 'restaurant')->first();
        if (!auth()->user()->hasRole($role->name)) {
            auth()->user()->addRole($role->name);
        }

        try {
            DB::beginTransaction();
            $request->merge([
                'user_id' => Auth::user()->id,
                'map_location' => 'Nairobi'
            ]);

            $restaurant = Restaurant::create($request->all());
            if($request->hasFile('logo')){
                $restaurant->update([
                    'logo' => $request->logo->store('companyLogos/logos', 'public')
                ]);
            }

            // if ($request->latitude && $request->longitude) {
            //     try {
            //         $user_location = Http::withOptions(['verify' => false])
            //                                 ->get('https://maps.googleapis.com/maps/api/geocode/json?latlng='.$request->latitude.','.$request->longitude.'&key='.config('services.map.key'));
            //     } catch (ConnectionException $e) {
            //         $user_country = 'Kenya';
            //         $user_short_country_name = 'KE';
            //     }

            //     if($user_location->failed() || $user_location->clientError() || $user_location->serverError()) {
            //         $user_country = 'Kenya';
            //         $user_short_country_name = 'KE';
            //     }

            //     foreach ($user_location['results'][0]['address_components'] as $place) {
            //         if (collect($place['types'])->contains('country')) {
            //             $user_country = $place['long_name'];
            //         }

            //         if (collect($place['types'])->contains('country')) {
            //             $user_short_country_name = $place['short_name'];
            //         }
            //     }

            //     $restaurant->update([
            //         'country' => $user_country,
            //         'country_code' => $user_short_country_name
            //     ]);
            // }

            $restaurant->update([
                'country' => 'Kenya',
                'country_code' => 'Ke'
            ]);

            $restaurant->update([
                'status' => '2'
            ]);

            activity()->causedBy(auth()->user())->performedOn($restaurant)->log('registered a new restaurant');

            DB::commit();

            SendCommunication::dispatchAfterResponse('mail', $restaurant->user->email, 'PartnerAccountCreated', ['restaurant' => $restaurant->id]);

            return new RestaurantResource($restaurant);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * List rated restaurants
     *
     * Public endpoint returning approved restaurants that have at least one review, ordered by `average_rating` (ascending).
     * When `lat` and `lng` are given, only restaurants within 200,000 km are included.
     *
     * @queryParam lat number Latitude of the user. Example: -1.2833
     * @queryParam lng number Longitude of the user. Example: 36.8167
     * @queryParam per_page integer Items per page. Defaults to 15. Example: 10
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"uuid":"9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b","attributes":{"name":"Mama's Kitchen","nameShort":"MK","email":"info@mamaskitchen.co.ke","about":"Authentic Kenyan cuisine served fresh daily.","aboutShort":"Kenyan cuisine","phoneNo":"+254712345678","address":"Moi Avenue, Nairobi","city":"Nairobi","state":"Nairobi","postalCode":"00100","latitude":"-1.2833","longitude":"36.8167","mapLocation":"Nairobi","url":"https://mamaskitchen.co.ke","logo":"https://moboeats.co.uk/storage/companyLogos/logos/logo.png","sitting_capacity":40,"status":"Approved","denied_reason":"","created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","average_rating":4.5,"is_open":true,"country":"Kenya","country_code":"KE"},"relationships":{"menus":[],"reviews":[],"restaurantTables":[]}}],"links":{"first":"http://localhost/api/v1/rated/restaurants?page=1","last":"http://localhost/api/v1/rated/restaurants?page=1","prev":null,"next":null},"meta":{"current_page":1,"from":1,"last_page":1,"links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"http://localhost/api/v1/rated/restaurants?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"path":"http://localhost/api/v1/rated/restaurants","per_page":15,"to":1,"total":1}}
     *
     * @responseField data object[] List of restaurants.
     * @responseField meta object Pagination metadata.
     */
    public function topRated(Request $request)
    {
        $radius = 200000;
        $latitude = $request->query('lat');
        $longitude = $request->query('lng');
        $per_page = $request->query('per_page');

        if ($latitude && $longitude) {
            $restaurants = Restaurant::Approved()
                ->whereHas('reviews')
                ->select(DB::raw("*,
                        (6371 * acos(cos(radians($latitude))
                        * cos(radians(latitude))
                        * cos(radians(longitude)
                        - radians($longitude))
                        + sin(radians($latitude))
                        * sin(radians(latitude))))
                        AS distance"))
                ->having('distance', '<=', $radius)
                ->orderBy('average_rating');
        } else {
            $restaurants = Restaurant::Approved()->whereHas('reviews')->orderBy('average_rating');
        }

        return new RestaurantCollection($restaurants->with('questionnaire', 'reviews', 'restaurantTables.seatingArea')->paginate($per_page));
    }

    /**
     * Show restaurant details
     *
     * Returns a single restaurant with operating hours, reviews and tables (with seating areas). Served publicly
     * (`/v1/restaurants/{restaurant}`, `/v1/orderer/orderer-restaurants/{restaurant}`) and to authenticated riders and restaurant owners.
     * When authenticated, documents are also loaded (they are only shown to the `restaurant` role).
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     *
     * @response 200 scenario="Success" {"data":{"id":1,"uuid":"9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b","attributes":{"name":"Mama's Kitchen","nameShort":"MK","email":"info@mamaskitchen.co.ke","about":"Authentic Kenyan cuisine served fresh daily.","aboutShort":"Kenyan cuisine","phoneNo":"+254712345678","address":"Moi Avenue, Nairobi","city":"Nairobi","state":"Nairobi","postalCode":"00100","latitude":"-1.2833","longitude":"36.8167","mapLocation":"Nairobi","url":"https://mamaskitchen.co.ke","logo":"https://moboeats.co.uk/storage/companyLogos/logos/logo.png","sitting_capacity":40,"status":"Approved","denied_reason":"","created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","average_rating":4.5,"is_open":true,"country":"Kenya","country_code":"KE"},"relationships":{"menus":[],"reviews":[],"restaurantTables":[]}}}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\Restaurant] 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b"}
     *
     * @responseField data.uuid string Restaurant UUID.
     * @responseField data.attributes object Restaurant details.
     * @responseField data.relationships.operating_hours object[] Operating hours.
     * @responseField data.relationships.reviews object[] Reviews.
     * @responseField data.relationships.restaurantTables object[] Tables with their seating area.
     */
    public function show(Restaurant $restaurant)
    {
        if(auth()->check()) {
            return new RestaurantResource($restaurant->load('operatingHours', 'documents', 'reviews', 'restaurantTables.seatingArea'));
        } else {
            return new RestaurantResource($restaurant->load('operatingHours', 'reviews', 'restaurantTables.seatingArea'));
        }
    }

    /**
     * List seating areas with tables
     *
     * Returns the tables of the caller's restaurants grouped by seating area. Role `restaurant` sees all of their restaurants;
     * role `restaurant employee` sees the restaurant they are assigned to. Note: the `{restaurant}` placeholder is not used for filtering.
     * Group keys are the JSON-serialized seating area.
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"seating_areas":{"{\"id\":1,\"name\":\"Outdoor\",\"created_at\":\"2025-01-10T09:00:00.000000Z\",\"updated_at\":\"2025-01-10T09:00:00.000000Z\"}":[{"id":3,"seating_area_id":1,"restaurant_id":1,"name":"Table 3","seat_number":4,"created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","seating_area":{"id":1,"name":"Outdoor","created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z"}}]}}}
     *
     * @responseField data.seating_areas object Tables grouped by serialized seating area.
     */
    public function restaurantSeatingAreas(Request $request, Restaurant $restaurant)
    {
        if (auth()->user()->hasRole('restaurant')) {
            $restaurant_ids = auth()->user()->restaurants->pluck('id');
        } else {
            $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();
            $restaurant_ids = Restaurant::where('id', $user_restaurant->restaurant_id)->get()->pluck('id');
        }

        $seating_areas = RestaurantTable::with('seatingArea')
                                        ->whereIn('restaurant_id', $restaurant_ids)
                                        ->get()
                                        ->groupBy('seatingArea');

        return $this->success([
            'seating_areas' => $seating_areas,
        ]);
    }

    /**
     * List restaurant reviews
     *
     * Returns all reviews of the restaurant, newest first (not paginated). Restaurant panel endpoint.
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     *
     * @response 200 scenario="Success" {"data":[{"reviewable":{"id":4,"user_id":7,"order_id":null,"reviewable_type":"App\\Models\\Restaurant","reviewable_id":1,"rating":5,"review":"Great food!","created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","reviewable":{"id":1,"uuid":"9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b","name":"Mama's Kitchen"}},"rating":5,"review":"Great food!"}]}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\Restaurant] 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b"}
     *
     * @responseField data object[] Reviews.
     * @responseField data[].reviewable object The full review record with the reviewed restaurant loaded.
     * @responseField data[].rating integer Rating from 1 to 5.
     * @responseField data[].review string Review text (nullable).
     */
    public function reviews(Restaurant $restaurant)
    {
        $review = Review::where('reviewable_type', Restaurant::class)->where('reviewable_id', $restaurant->id)->orderBy('created_at', 'DESC')->get();

        return ReviewResource::collection($review);
    }

    /**
     * Update a restaurant
     *
     * Updates the restaurant details. Served to restaurant owners/employees (`PUT /v1/restaurant/restaurants/{restaurant}`,
     * `POST /v1/restaurant/restaurants/{restaurant}/update`), riders (resource route) and admins (`POST /v1/admin/restaurants/{restaurant}/update`).
     * Users with role `orderer` get 401. When `latitude`/`longitude` are sent the country is resolved via Google Geocoding.
     * If a restaurant/employee updates a Pending or Denied restaurant, a Denied one is moved back to Pending and the admin is notified;
     * an admin update sets the status to Approved. Send as `multipart/form-data` (with POST) when uploading a logo.
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     *
     * @bodyParam name string required Restaurant name. Example: Mama's Kitchen
     * @bodyParam name_short string required Short name. Example: MK
     * @bodyParam email string required Contact email (valid email). Example: info@mamaskitchen.co.ke
     * @bodyParam about string required Full description. Example: Authentic Kenyan cuisine served fresh daily.
     * @bodyParam about_short string required Short description. Example: Kenyan cuisine
     * @bodyParam phone_no string required Phone number. Example: +254712345678
     * @bodyParam address string required Street address. Example: Moi Avenue, Nairobi
     * @bodyParam state string required State/county. Example: Nairobi
     * @bodyParam postal_code string required Postal code. Example: 00100
     * @bodyParam latitude string required Latitude (must not be the string `null`). Example: -1.2833
     * @bodyParam longitude string required Longitude (must not be the string `null`). Example: 36.8167
     * @bodyParam city string City. Example: Nairobi
     * @bodyParam map_location string Map location label. Example: Nairobi
     * @bodyParam url string Website URL. Example: https://mamaskitchen.co.ke
     * @bodyParam logo file New logo image; replaces the existing one.
     * @bodyParam paypal_email string PayPal/Venmo email for payouts. Example: payouts@mamaskitchen.co.ke
     *
     * @response 200 scenario="Success" {"data":{"id":1,"uuid":"9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b","attributes":{"name":"Mama's Kitchen","nameShort":"MK","email":"info@mamaskitchen.co.ke","about":"Authentic Kenyan cuisine served fresh daily.","aboutShort":"Kenyan cuisine","phoneNo":"+254712345678","address":"Moi Avenue, Nairobi","city":"Nairobi","state":"Nairobi","postalCode":"00100","latitude":"-1.2833","longitude":"36.8167","mapLocation":"Nairobi","url":"https://mamaskitchen.co.ke","logo":"https://moboeats.co.uk/storage/companyLogos/logos/logo.png","sitting_capacity":40,"status":"Approved","denied_reason":"","created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","average_rating":4.5,"is_open":true,"country":"Kenya","country_code":"KE"},"relationships":{"menus":[],"reviews":[],"restaurantTables":[]}}}
     * @response 401 scenario="Orderer not allowed" {"status":"Error has occured...","message":"Unauthorized","data":""}
     * @response 403 scenario="Update failed" {"status":"Error has occured...","message":"Undefined array key 0","data":""}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\Restaurant] 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b"}
     * @response 422 scenario="Validation error" {"message":"Please select restaurant location","errors":{"latitude":["Please select restaurant location"]}}
     *
     * @responseField data.uuid string Restaurant UUID.
     * @responseField data.attributes object Updated restaurant details.
     */
    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if (auth()->user()->hasRole('orderer')) {
            return $this->error('', 'Unauthorized', 401);
        }

        try {
            DB::beginTransaction();

            $restaurant->update(collect($request->all())->except('sitting_capacity', 'logo')->toArray());

            if($request->hasFile('logo')) {
                $restaurant_logo = explode('/', $restaurant->logo);
                Storage::disk('public')->delete('/companyLogos/logos/'.end($restaurant_logo));
                $restaurant->update([
                    'logo' => $request->logo->store('companyLogos/logos', 'public')
                ]);
            }

            if ($request->latitude && $request->longitude) {
                try {
                    $user_location = Http::withOptions(['verify' => false])
                                            ->get('https://maps.googleapis.com/maps/api/geocode/json?latlng='.$request->latitude.','.$request->longitude.'&key='.config('services.map.key'));
                } catch (ConnectionException $e) {
                    $user_country = 'Kenya';
                    $user_short_country_name = 'KE';
                }

                if($user_location->failed() || $user_location->clientError() || $user_location->serverError()) {
                    $user_country = 'Kenya';
                    $user_short_country_name = 'KE';
                }

                foreach ($user_location['results'][0]['address_components'] as $place) {
                    if (collect($place['types'])->contains('country')) {
                        $user_country = $place['long_name'];
                    }

                    if (collect($place['types'])->contains('country')) {
                        $user_short_country_name = $place['short_name'];
                    }
                }

                $restaurant->update([
                    'country' => $user_country,
                    'country_code' => $user_short_country_name
                ]);
            }

            if ((auth()->user()->hasRole('restaurant') || auth()->user()->hasRole('restaurant employee')) && $restaurant->status == 'Pending' || $restaurant->status == 'Denied') {
                // Update restaurant status to pending
                if ($restaurant->status == 'Denied') {
                    $restaurant->update([
                        'status' => '1'
                    ]);
                }
                // Notify admin to review the restaurant
                $admin = User::where('email', 'info@moboeats.co.uk')->first();
                $admin->notify(new UpdatedRestaurant($restaurant));
            } elseif (auth()->user()->hasRole('admin')) {
                $restaurant->update([
                    'status' => '2'
                ]);
            }

            activity()->causedBy(auth()->user())->performedOn($restaurant)->log('update the restaurant');

            DB::commit();

            return new RestaurantResource($restaurant);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * Delete a restaurant
     *
     * Deletes the restaurant. Only the restaurant owner may delete it; orderers and non-owners get 401.
     * If the caller's role check does not pass, an empty response is returned. On success the response body is `1`.
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     *
     * @response 200 scenario="Deleted" 1
     * @response 200 scenario="Role check not passed" ""
     * @response 401 scenario="Not authorized" {"status":"Error has occured...","message":"You are not authorized to make this request","data":""}
     * @response 403 scenario="Delete failed" {"status":"Error has occured...","message":"SQLSTATE[23000]: Integrity constraint violation","data":""}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\Restaurant] 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b"}
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
                return $this->error('', 'You are not authorized to make this request', 401);
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

    /**
     * List available riders for a restaurant
     *
     * Returns approved riders with a device token who are not currently assigned to an in-transit order. Distance and travel
     * time to the restaurant are computed through Google Distance Matrix and used for sorting. Restaurant panel endpoint.
     *
     * @urlParam id integer required The numeric ID of the restaurant. Example: 1
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":[{"id":21,"uuid":"7f6e5d4c-3b2a-4190-8f7e-6d5c4b3a2910","name":"Peter Rider","email":"peter@example.com","phone_number":"+254722222222","role":null,"avatar":null,"relationships":{"orderer":null},"type":"food","referral_code":null,"is_guided":true}]}
     *
     * @responseField data object[] Available riders.
     * @responseField data[].uuid string Rider user UUID.
     * @responseField data[].name string Rider name.
     * @responseField data[].phone_number string Rider phone number.
     */
    public function riders($restaurant_id)
    {
        $restaurant = Restaurant::find($restaurant_id);

        $riders = User::where('device_token', '!=', NULL)
                                ->whereHas('roles', function($query) {
                                    $query->where('name', 'rider');
                                })
                                ->whereHas('rider')
                                ->where('status', 2)
                                ->where(function($query) use ($restaurant_id) {
                                    $assigned_riders = Order::where('rider_id', '!=', NULL)->where('status', 4)->pluck('rider_id');
                                    info($assigned_riders);

                                    $query->when(count($assigned_riders) > 0, function ($query) use ($assigned_riders) {
                                        $query->whereNotIn('id', $assigned_riders);
                                    });
                                })
                                ->get()
                                ->each(function($rider, $key) use ($restaurant) {
                                    if ($rider->latitude != NULL && $rider->longitude != NULL && $restaurant->latitude != NULL && $restaurant->longitude != NULL) {
                                        $business_location = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$rider->latitude.','.$rider->longitude.'&destinations='.$restaurant->latitude.','.$restaurant->longitude.'&key='.config('services.map.key'));
                                        if (json_decode($business_location)->rows[0]->elements[0]->status != "NOT_FOUND" && json_decode($business_location)->rows[0]->elements[0]->status != "ZERO_RESULTS") {
                                            $distance = json_decode($business_location)->rows[0]->elements[0]->distance->text;
                                            $time = json_decode($business_location)->rows[0]->elements[0]->duration->text;
                                            $rider['distance'] = $distance;
                                            $rider['time_away'] = $time;
                                        } else {
                                            $rider['distance'] = NULL;
                                            $rider['time_away'] = NULL;
                                        }
                                    } else {
                                       $rider['distance'] = NULL;
                                       $rider['time_away'] = NULL;
                                    }
                                    })->sortBy([
                                        fn($a, $b) => (double) explode(' ', $a['distance'])[0] <= (double) explode(' ', $b['distance'])[0],
                                    ]);

        // // Get Unassigned Couriers and order by closest
        // $riders = User::where('device_token', '!=', NULL)
        //                 ->whereHas('roles', function($query) {
        //                     $query->where('name', 'rider');
        //                 })
        //                 ->whereHas('rider')
        //                 // ->where('status', 'Active')
        //                 ->where(function($query) {
        //                     $assigned_riders = Order::where('rider_id', '!=', NULL)->get()->pluck('rider_id');
        //                     $query->whereNotIn('id', $assigned_riders);
        //                 })
        //                 ->get()
        //                 // Filter by distance to restaurant
        //                 ->each(function($rider, $key) use ($restaurant) {
        //                     if ($rider->latitude != NULL && $rider->lognitude != NULL) {
        //                         $business_location = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$rider->latitude.','.$rider->longitude.'&destinations='.$restaurant->latitude.','.$restaurant->longitude.'&key='.config('services.map.key'));

        //                         if (json_decode($business_location)->rows[0]->elements[0]->status != "NOT_FOUND" && json_decode($business_location)->rows[0]->elements[0]->status != "ZERO_RESULTS") {
        //                             $distance = json_decode($business_location)->rows[0]->elements[0]->distance->text;
        //                             $time = json_decode($business_location)->rows[0]->elements[0]->duration->text;
        //                             $rider['distance'] = $distance;
        //                             $rider['time_away'] = $time;
        //                         }
        //                     } else {
        //                         $rider['distance'] = NULL;
        //                         $rider['time_away'] = NULL;
        //                     }
        //                 })
        //                 // Order by distance and time
        //                 ->sortBy([
        //                     fn($a, $b) => (double) explode(' ', $a['distance'])[0] >= (double) explode(' ',$b['distance'])[0],
        //                 ]);

        return $this->success(UserResource::collection($riders));
    }

    /**
     * Restaurant dashboard statistics
     *
     * Returns dashboard figures for the authenticated restaurant owner (`restaurant`) or employee (`restaurant employee`):
     * counts, revenue, 13-month series (orders, payments, delivery and booking payments; filled for role `restaurant` only),
     * top 3 restaurants, top 10 popular menus, deliveries, dine-ins and latest 5 promo codes.
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"months":["10-2024","11-2024","12-2024","01-2025"],"restaurants_count":2,"orders_count":120,"delivered_orders_count":98,"revenue":145000.5,"popular_menus":[{"id":14,"uuid":"d4e5f6a7-b8c9-4d0e-9f1a-2b3c4d5e6f7a","title":"Chicken Pilau","description":"Spiced rice with chicken","restaurant_id":1,"status":2,"preparation_time":20,"order_items_count":32,"created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","restaurant":{"id":1,"uuid":"9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b","name":"Mama's Kitchen"},"images":[],"menu_prices":[]}],"top_restaurants":[{"id":1,"uuid":"9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b","name":"Mama's Kitchen","orders_count":80,"orders":[],"payments":{"labels":["10-2024","11-2024","12-2024","01-2025"],"amounts":[12000,15000,9000,18000]}}],"pending_approval":0,"approved_restaurants":2,"rejected_restaurants":0,"orders_series":[10,12,9,15],"payments_series":[12000,15000,9000,18000],"delivery_payment_series":[8000,10000,6000,12000],"booking_payment_series":[4000,5000,3000,6000],"deliveries":[{"id":101,"uuid":"ORD-7F3A21","user_id":7,"restaurant_id":1,"total_amount":1500,"delivery":true,"delivery_address":"Kenyatta Avenue, Nairobi","delivery_fee":200,"delivery_status":"Pending","status":"Pending","service_charge":50,"discount":0,"rider_id":null,"booking_time":null,"reservation_date":null,"created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z"}],"dineins":[],"latest_discounts":[{"id":3,"code":"WELCOME10","restaurant_id":1,"orders_count":5,"created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z"}]}}
     *
     * @responseField data.months string[] Month labels (m-Y) for the series.
     * @responseField data.revenue number Paid order revenue minus service charges.
     * @responseField data.orders_series integer[] Orders per month.
     * @responseField data.payments_series number[] Paid amounts per month.
     * @responseField data.top_restaurants object[] Top 3 restaurants by order count with monthly payment amounts.
     * @responseField data.popular_menus object[] Top 10 menus by number of order items.
     */
    public function dashboard()
    {
        $months = [];
        // Get past 12 months
        $months = [];
        for ($i = 12; $i >= 0; $i--) {
            $month = Carbon::today()->startOfMonth()->subMonth($i);
            $year = Carbon::today()->startOfMonth()->subMonth($i)->format('Y');
            array_push($months, $month);
        }

        // Format months
        $months_formatted = [];
        foreach ($months as $key => $month) {
            array_push($months_formatted, Carbon::parse($month)->format('m-Y'));
        }


        $restaurants_count = Restaurant::where('user_id', auth()->id())->count();
        $restaurant_ids = auth()->user()->restaurants->pluck('id');
        $orders_count = Order::whereIn('restaurant_id', $restaurant_ids)->count();
        $delivered_orders_count = Order::whereIn('restaurant_id', $restaurant_ids)->where('status', 'Delivered')->count();

        $orders = [];

        if (auth()->user()->hasRole('restaurant')) {
            $orders = Order::whereIn('restaurant_id', $restaurant_ids)->get();
            $deliveries = Order::whereIn('restaurant_id', $restaurant_ids)->where('delivery', true)->get();
            $dineins = Order::whereIn('restaurant_id', $restaurant_ids)->where('delivery', false)->get();
        }

        if (auth()->user()->hasRole('restaurant employee')) {
            $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();
            $restaurant_ids = Restaurant::where('id', $user_restaurant->restaurant_id)->get()->pluck('id');

            $orders = Order::whereIn('restaurant_id', $restaurant_ids)->get();
            $deliveries = Order::whereIn('restaurant_id', $restaurant_ids)->where('delivery', true)->get();
            $dineins = Order::whereIn('restaurant_id', $restaurant_ids)->where('delivery', false)->get();
        }

        $revenue = Payment::where('orderable_type', Order::class)->whereIn('orderable_id', $orders->pluck('id'))->where('status', '2')->sum('amount');
        $service_charges = $orders->where('delivery_status', 'delivered')->sum('service_charge');
        $revenue = (float) $revenue - (float) $service_charges;

        // Top Restaurants
        $top_restaurants = Restaurant::where('user_id', auth()->id())
                                        // ->whereHas('orders')
                                        ->withCount('orders')
                                        ->with('orders.payment')
                                        ->orderBy('orders_count', 'DESC')
                                        ->get()
                                        ->take(3);

        $pending_approval = Restaurant::where('user_id', auth()->id())->where('status', 1)->count();
        $approved_restaurants = Restaurant::where('user_id', auth()->id())->where('status', 2)->count();
        $rejected_restaurants = Restaurant::where('user_id', auth()->id())->where('status', 3)->count();

        $top_restaurants_formatted = [];
        $orders_series = [];
        $payments_series = [];
        $delivery_payment_series = [];
        $bookings_payments_series = [];
        if (auth()->user()->hasRole('restaurant')) {
            foreach ($top_restaurants as $restaurant) {
                $payment['payments']['labels'] = $months_formatted;
                $payment['payments']['amounts'] = [];

                $order_ids = $restaurant->orders->pluck('id');

                foreach ($months as $month) {
                    $sum = Payment::where('orderable_type', Order::class)->whereIn('orderable_id', $order_ids)->where('status', 2)->whereBetween('updated_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->sum('amount');
                    $delivery_fees = Order::whereIn('id', $order_ids)->where('delivery_status', 'Delivered')->whereBetween('updated_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->sum('delivery_fee');
                    $amount = $sum - $delivery_fees;
                    array_push($payment['payments']['amounts'], $amount);
                }

                $restaurant = array_merge($restaurant->toArray(), $payment);
                array_push($top_restaurants_formatted, $restaurant);
            }

            $restaurants_ids = auth()->user()->restaurants->pluck('id');

            foreach ($months as $month) {
                $orders = Order::whereIn('restaurant_id', $restaurants_ids)->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->count();
                array_push($orders_series, $orders);
            }

            $orders_ids = Order::whereIn('restaurant_id', $restaurants_ids)->get()->pluck('id');

            foreach ($months as $month) {
                $payments = Payment::where('orderable_type', Order::class)->whereIn('orderable_id', $orders_ids)->where('status', 2)->whereBetween('updated_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->sum('amount');
                array_push($payments_series, $payments);
                $delivery_payments = Payment::where('orderable_type', Order::class)
                                        ->whereIn('orderable_id', $orders_ids)
                                        ->whereHas('orderable', function ($query) {
                                            $query->where('delivery', true);
                                        })
                                        ->where('status', 2)
                                        ->whereBetween('updated_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
                                        ->sum('amount');

                array_push($delivery_payment_series, $delivery_payments);
                $booking_payments = Payment::where('orderable_type', Order::class)
                                            ->whereIn('orderable_id', $orders_ids)
                                            ->whereHas('orderable', function ($query) {
                                                $query->where('delivery', false);
                                            })
                                            ->where('status', 2)
                                            ->whereBetween('updated_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
                                            ->sum('amount');

                array_push($bookings_payments_series, $booking_payments);
            }
        }

        // Most Popular Menu Items
        // $popular_menus = OrderItem::with('menu')
        //                         ->whereIn('order_id', $orders_ids)
        //                         ->get()
        //                         ->take(10)
        //                         ->groupBy(function ($item, $key) {
        //                             return Menu::where('id', $item->menu_id)->first()->title;
        //                         });
        $popular_menus = Menu::whereHas('orderItems')
                            ->withCount('orderItems')
                            ->with('restaurant', 'images', 'menuPrices')
                            ->whereIn('restaurant_id', $restaurant_ids)
                            ->orderBy('order_items_count', 'DESC')
                            ->get()
                            ->take(10);

        $latest_discounts = PromoCode::withCount('orders')->whereIn('restaurant_id', $restaurant_ids)->orderBy('created_at', 'DESC')->get()->take(5);

        return $this->success([
            'months' => $months_formatted,
            'restaurants_count' => $restaurants_count,
            'orders_count' => $orders_count,
            'delivered_orders_count' => $delivered_orders_count,
            'revenue' => $revenue,
            'popular_menus' => $popular_menus,
            'top_restaurants' => $top_restaurants_formatted,
            'pending_approval' => $pending_approval,
            'approved_restaurants' => $approved_restaurants,
            'rejected_restaurants' => $rejected_restaurants,
            'orders_series' => $orders_series,
            'payments_series' => $payments_series,
            'delivery_payment_series' => $delivery_payment_series,
            'booking_payment_series' => $bookings_payments_series,
            'deliveries' => $deliveries,
            'dineins' => $dineins,
            'latest_discounts' => $latest_discounts,
        ]);
    }

    /**
     * List restaurant payments and earnings
     *
     * Returns paginated payments (15 per page) for delivered orders of the caller's restaurants plus earnings totals.
     * Role `restaurant` covers all owned restaurants; `restaurant employee` covers the assigned restaurant.
     *
     * @queryParam search string Search by transaction ID, order UUID, restaurant name/short name or customer name. Example: pi_3NxYz
     * @queryParam from_created_at string Only payments created on/after this date. Example: 2025-01-01
     * @queryParam to_created_at string Only payments created on/before this date. Example: 2025-01-31
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"payments":{"current_page":1,"data":[{"id":55,"uuid":"8e7d6c5b-4a3f-4e2d-9c1b-0a9f8e7d6c5b","transaction_id":"pi_3NxYzAbC123","orderable_id":101,"orderable_type":"App\\Models\\Order","payment_method":"stripe","amount":1500,"status":2,"created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","orderable":{"id":101,"uuid":"ORD-7F3A21","restaurant_id":1,"total_amount":1500,"user":{"id":7,"name":"John Customer","email":"john@example.com"},"restaurant":{"id":1,"uuid":"9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b","name":"Mama's Kitchen"}}}],"first_page_url":"http://localhost/api/v1/restaurant/payments?page=1","from":1,"last_page":1,"last_page_url":"http://localhost/api/v1/restaurant/payments?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"http://localhost/api/v1/restaurant/payments?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"http://localhost/api/v1/restaurant/payments","per_page":15,"prev_page_url":null,"to":1,"total":1},"total_amount":125000,"paid_amount":100000,"unpaid_amount":25000,"country":"Kenya"}}
     *
     * @responseField data.payments object Paginated payments with order, customer and restaurant.
     * @responseField data.total_amount number Total of delivered orders minus delivery fees and service charges.
     * @responseField data.paid_amount number Total already paid out to the restaurant(s).
     * @responseField data.unpaid_amount integer Outstanding amount (total - paid).
     * @responseField data.country string Country of the first order, defaults to `United Kingdom`.
     */
    public function payments(Request $request)
    {
        if (auth()->user()->hasRole('restaurant')) {
            $restaurant_ids = auth()->user()->restaurants->pluck('id');
        } else {
            $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();
            $restaurant_ids = Restaurant::where('id', $user_restaurant->restaurant_id)->get()->pluck('id');
        }

        $total_amount = 0;
        $paid_amount = 0;
        $unpaid_amount = 0;

        $orders = Order::whereIn('restaurant_id', $restaurant_ids)
                            ->where('delivery_status', 'delivered')
                            ->get();

        $total_amount = $orders->sum('total_amount');
        $delivery_fees = $orders->sum('delivery_fee');
        $service_charges = $orders->sum('service_charge');
        $total_amount = $total_amount - $delivery_fees - $service_charges;

        $paid_amount = Payout::whereIn('payable_id', $restaurant_ids)->where('payable_type', Restaurant::class)->sum('amount');

        $unpaid_amount = (int) $total_amount - (int) $paid_amount;

        $search = $request->query('search');
        $from_created_at = $request->query('from_created_at');
        $to_created_at = $request->query('to_created_at');
        $payments = Payment::with('orderable.user', 'orderable.restaurant')
                            ->where('orderable_type', Order::class)
                            ->whereIn('orderable_id', $orders->pluck('id'))
                            ->where('transaction_id', '!=', NULL)
                            ->when($from_created_at && $from_created_at != '', function ($query) use ($from_created_at) {
                                $query->whereDate('created_at', '>=', Carbon::parse($from_created_at));
                            })
                            ->when($to_created_at && $to_created_at != '', function ($query) use ($to_created_at) {
                                $query->whereDate('created_at', '<=', Carbon::parse($to_created_at));
                            })
                            ->when($search && $search != '', function ($query) use ($search) {
                                $query->where(function($query) use ($search) {
                                    $query->where('transaction_id', 'LIKE', '%' . $search . '%')
                                        ->orWhereHas('orderable', function ($query) use ($search) {
                                            $query->where('uuid', 'LIKE', '%' . $search . '%')
                                                ->whereHas('restaurant', function ($query) use ($search) {
                                                    $query->where('name', 'LIKE', '%' . $search . '%')->orWhere('name_short', 'LIKE', '%' . $search . '%');
                                                })
                                                ->orWhereHas('user', function ($query) use ($search) {
                                                    $query->where('name', 'LIKE', '%' . $search . '%');
                                                });
                                    });
                                });
                            })
                            ->orderBy('created_at', 'DESC')
                            ->paginate();

        return $this->success([
            'payments' => $payments,
            'total_amount' => $total_amount,
            'paid_amount' => $paid_amount,
            'unpaid_amount' => $unpaid_amount,
            'country' => $orders->count() ? $orders->first()->country : 'United Kingdom',
        ]);
    }

    /**
     * List restaurant tables
     *
     * Returns the tables (with seating area) of the caller's restaurants, 10 per page. Role `restaurant` sees all owned
     * restaurants; `restaurant employee` sees the assigned restaurant.
     *
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"current_page":1,"data":[{"id":3,"seating_area_id":1,"restaurant_id":1,"name":"Table 3","seat_number":4,"created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","seating_area":{"id":1,"name":"Outdoor","created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z"}}],"first_page_url":"http://localhost/api/v1/restaurant/tables?page=1","from":1,"last_page":1,"last_page_url":"http://localhost/api/v1/restaurant/tables?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"http://localhost/api/v1/restaurant/tables?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"http://localhost/api/v1/restaurant/tables","per_page":10,"prev_page_url":null,"to":1,"total":1}}
     *
     * @responseField data.data object[] Tables with their seating area.
     * @responseField data.total integer Total number of tables.
     */
    public function tables(Request $request)
    {
        if (auth()->user()->hasRole('restaurant')) {
            $restaurant_ids = auth()->user()->restaurants->pluck('id');
        } else {
            $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();
            $restaurant_ids = Restaurant::where('id', $user_restaurant->restaurant_id)->get()->pluck('id');
        }

        $tables = RestaurantTable::with('seatingArea')
                                    ->whereIn('restaurant_id', $restaurant_ids)
                                    ->paginate(10);

        return $this->success($tables);
    }

    /**
     * List table reservations
     *
     * Returns all orders with a table reservation for the caller's restaurants, ordered by reservation date (not paginated).
     * Role `restaurant` covers all owned restaurants; `restaurant employee` covers the assigned restaurant.
     *
     * @queryParam search string Search term (transaction ID, order UUID, restaurant or customer name). Example: ORD-7F3A
     * @queryParam from_created_at string Only reservations on/after this date. Example: 2025-01-01
     * @queryParam to_created_at string Only reservations on/before this date. Example: 2025-01-31
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"orders":[{"id":101,"uuid":"ORD-7F3A21","user_id":7,"restaurant_id":1,"total_amount":1500,"delivery":false,"delivery_address":"Kenyatta Avenue, Nairobi","delivery_fee":200,"delivery_status":"Pending","status":"Pending","service_charge":50,"discount":0,"rider_id":null,"booking_time":null,"reservation_date":"2025-01-20 19:00:00","created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z"}]}}
     *
     * @responseField data.orders object[] Reservation orders.
     */
    public function reservations(Request $request)
    {
        if (auth()->user()->hasRole('restaurant')) {
            $restaurant_ids = auth()->user()->restaurants->pluck('id');
        } else {
            $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();
            $restaurant_ids = Restaurant::where('id', $user_restaurant->restaurant_id)->get()->pluck('id');
        }

        $search = $request->query('search');
        $from_created_at = $request->query('from_created_at');
        $to_created_at = $request->query('to_created_at');

        $orders = Order::whereIn('restaurant_id', $restaurant_ids)
                            ->whereHas('reservation')
                            ->when($from_created_at && $from_created_at != '', function ($query) use ($from_created_at) {
                                $query->whereDate('reservation_date', '>=', Carbon::parse($from_created_at));
                            })
                            ->when($to_created_at && $to_created_at != '', function ($query) use ($to_created_at) {
                                $query->whereDate('reservation_date', '<=', Carbon::parse($to_created_at));
                            })
                            ->when($search && $search != '', function ($query) use ($search) {
                                $query->where(function($query) use ($search) {
                                    $query->where('transaction_id', 'LIKE', '%' . $search . '%')
                                        ->orWhereHas('order', function ($query) use ($search) {
                                            $query->where('uuid', 'LIKE', '%' . $search . '%')
                                                ->whereHas('restaurant', function ($query) use ($search) {
                                                    $query->where('name', 'LIKE', '%' . $search . '%')->orWhere('name_short', 'LIKE', '%' . $search . '%');
                                                })
                                                ->orWhereHas('user', function ($query) use ($search) {
                                                    $query->where('name', 'LIKE', '%' . $search . '%');
                                                });
                                    });
                                });
                            })
                            ->orderBy('reservation_date')
                            ->get();

        return $this->success([
            'orders' => $orders,
        ]);
    }

    /**
     * Export payments to Excel
     *
     * Generates and downloads an `.xlsx` file of payments, optionally filtered. Restaurant panel endpoint.
     *
     * @queryParam search string Search term passed to the export. Example: pi_3NxYz
     * @queryParam from_created_at string Only payments created on/after this date. Example: 2025-01-01
     * @queryParam to_created_at string Only payments created on/before this date. Example: 2025-01-31
     *
     * @response 200 scenario="File download" <<binary>> The exported Excel file (payments{id}.xlsx).
     */
    public function exportPayments(Request $request)
    {
        $search = $request->query('search');
        $from_created_at = $request->query('from_created_at');
        $to_created_at = $request->query('to_created_at');

        $unique = explode('-', Str::uuid())[0];

        Excel::store(new PaymentExport($search, $from_created_at, $to_created_at), 'payments'.$unique.'.xlsx', 'exports');

        return Storage::disk('exports')->download('payments'.$unique.'.xlsx');
    }

    /**
     * List a restaurant's menu
     *
     * Returns the restaurant's menu items (9 per page, newest first) with images, prices and categories, plus the food
     * categories available to it (global ones and its own). Restaurant panel endpoint.
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     * @queryParam search string Search menu items by title or description. Example: pilau
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"menu":{"current_page":1,"data":[{"id":14,"uuid":"d4e5f6a7-b8c9-4d0e-9f1a-2b3c4d5e6f7a","title":"Chicken Pilau","description":"Spiced rice with chicken","restaurant_id":1,"status":2,"preparation_time":20,"order_items_count":32,"created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","images":[],"menu_prices":[],"sub_categories":[],"categories":[]}],"first_page_url":"http://localhost/api/v1/restaurant/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/menu?page=1","from":1,"last_page":1,"last_page_url":"http://localhost/api/v1/restaurant/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/menu?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"http://localhost/api/v1/restaurant/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/menu?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"http://localhost/api/v1/restaurant/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/menu","per_page":9,"prev_page_url":null,"to":1,"total":1},"categories":[{"id":9,"uuid":"c1d2e3f4-a5b6-4c7d-8e9f-0a1b2c3d4e5f","title":"Breakfast","description":"Morning meals","status":2,"createdBy":"owner@mamaskitchen.co.ke","updatedBy":null,"image":"https://moboeats.co.uk/storage/category/images/breakfast.png","subCategories":[]}]}}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\Restaurant] 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b"}
     *
     * @responseField data.menu object Paginated menu items.
     * @responseField data.categories object[] Food categories with sub-categories.
     */
    public function restaurantMenu(Request $request, Restaurant $restaurant)
    {
        $search = $request->query('search');

        $menu = Menu::with('images', 'menuPrices', 'subCategories', 'categories.food_sub_categories')
                    ->withCount('orderItems')
                    ->where('restaurant_id', $restaurant->id)
                    ->when($search && $search != '', function ($query) use ($search) {
                        $query->where(function ($query) use ($search) {
                            $query->where('title', 'LIKE', '%' . $search . '%')
                                    ->orWhere('description', 'LIKE', '%' . $search . '%');
                        });
                    })
                    ->orderBy('created_at', 'DESC')
                    ->paginate(9);

        $categories = FoodCommonCategory::with('food_sub_categories')->where('restaurant_id', NULL)->orWhere('restaurant_id', $restaurant->id)->get();

        return $this->success(['menu' => $menu, 'categories' => new FoodCommonCategoryCollection($categories)]);
    }

    /**
     * List a restaurant's own categories
     *
     * Returns the food categories created by the restaurant (8 per page, with sub-categories) and the discounts on its menus.
     * Restaurant panel endpoint.
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"categories":{"current_page":1,"data":[{"id":9,"uuid":"c1d2e3f4-a5b6-4c7d-8e9f-0a1b2c3d4e5f","title":"Breakfast","description":"Morning meals","status":2,"created_by":"owner@mamaskitchen.co.ke","updated_by":null,"image":"https://moboeats.co.uk/storage/category/images/breakfast.png","restaurant_id":1,"created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","food_sub_categories":[]}],"first_page_url":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/categories?page=1","from":1,"last_page":1,"last_page_url":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/categories?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/categories?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/categories","per_page":8,"prev_page_url":null,"to":1,"total":1},"discounts":[{"id":2,"menu_id":14,"percentage":10,"created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","menu":{"id":14,"title":"Chicken Pilau"}}]}}
     *
     * @responseField data.categories object Paginated categories created by the restaurant.
     * @responseField data.discounts object[] Discounts on the restaurant's menus.
     */
    public function categories($id)
    {
        $restaurant = Restaurant::where('uuid', $id)->orWhere('uuid', $id)->first();

        $categories = FoodCommonCategory::with('food_sub_categories')->where('restaurant_id', $restaurant->id)->paginate(8);

        $menu_ids = $restaurant->menus->pluck('id');

        $discounts = Discount::with('menu')->whereIn('menu_id', $menu_ids)->get();

        return $this->success(['categories' => $categories, 'discounts' => $discounts]);
    }

    /**
     * Add a restaurant category
     *
     * Creates an approved food category owned by the restaurant. Returns the first page (7 per page) of all food categories.
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     * @bodyParam title string required Category title. Example: Breakfast
     * @bodyParam description string required Category description. Example: Morning meals
     * @bodyParam image file Category image.
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"current_page":1,"data":[{"id":9,"uuid":"c1d2e3f4-a5b6-4c7d-8e9f-0a1b2c3d4e5f","title":"Breakfast","description":"Morning meals","status":2,"created_by":"owner@mamaskitchen.co.ke","updated_by":null,"image":"https://moboeats.co.uk/storage/category/images/breakfast.png","restaurant_id":1,"created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z"}],"first_page_url":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/categories/add?page=1","from":1,"last_page":1,"last_page_url":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/categories/add?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/categories/add?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/categories/add","per_page":7,"prev_page_url":null,"to":1,"total":1}}
     * @response 403 scenario="Creation failed" {"status":"Error has occured...","message":"SQLSTATE[23000]: Integrity constraint violation","data":""}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\Restaurant] 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b"}
     * @response 422 scenario="Validation error" {"message":"The title field is required.","errors":{"title":["The title field is required."]}}
     *
     * @responseField data.data object[] Food categories.
     */
    public function addCategory(Request $request, Restaurant $restaurant)
    {
        $request->validate([
            'title' => ['required'],
            'description' => ['required'],
        ]);

        try {
            DB::beginTransaction();
            $request->merge([
                'restaurant_id' => $restaurant->id,
                'created_by' => auth()->user()->email,
                'status' => 2
            ]);
            $food_category = FoodCommonCategory::create(collect($request->all())->except('image')->toArray());
            if ($request->hasFile('image')) {
                $food_category->update([
                    'image' => pathinfo($request->image->store('images', 'category'), PATHINFO_BASENAME)
                ]);
            }
            DB::commit();

            $categories = FoodCommonCategory::paginate(7);
            return $this->success($categories);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * Update a restaurant category
     *
     * Updates the title, description and optionally image of a food category. Returns the first page (7 per page) of all food categories.
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     * @urlParam id integer required The numeric ID of the food category. Example: 9
     * @bodyParam title string required Category title. Example: Breakfast
     * @bodyParam description string required Category description. Example: Morning meals
     * @bodyParam image file Category image.
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"current_page":1,"data":[{"id":9,"uuid":"c1d2e3f4-a5b6-4c7d-8e9f-0a1b2c3d4e5f","title":"Breakfast","description":"Morning meals","status":2,"created_by":"owner@mamaskitchen.co.ke","updated_by":null,"image":"https://moboeats.co.uk/storage/category/images/breakfast.png","restaurant_id":1,"created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z"}],"first_page_url":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/categories/9/update?page=1","from":1,"last_page":1,"last_page_url":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/categories/9/update?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/categories/9/update?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/categories/9/update","per_page":7,"prev_page_url":null,"to":1,"total":1}}
     * @response 403 scenario="Category not found / update failed" {"status":"Error has occured...","message":"Call to a member function update() on null","data":""}
     * @response 422 scenario="Validation error" {"message":"The title field is required.","errors":{"title":["The title field is required."]}}
     *
     * @responseField data.data object[] Food categories.
     */
    public function updateCategory(Request $request, $uuid, $id)
    {
        $request->validate([
            'title' => ['required'],
            'description' => ['required'],
        ]);

        try {
            DB::beginTransaction();
            $food_category = FoodCommonCategory::find($id);

            $food_category->update([
                'title' => $request->title,
                'description' => $request->description
            ]);

            if ($request->hasFile('image')) {
                $food_category->update([
                    'image' => pathinfo($request->image->store('images', 'category'), PATHINFO_BASENAME)
                ]);
            }
            DB::commit();

            // return new FoodCommonCategoryResource($food_category);
            $categories = FoodCommonCategory::paginate(7);
            return $this->success($categories);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * Add a restaurant table
     *
     * Creates a table for the restaurant. Returns the first page (7 per page) of all tables.
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     * @bodyParam name string required Table name. Example: Table 3
     * @bodyParam seating_area_id integer required ID of the seating area. Example: 1
     * @bodyParam seat_number integer required Number of seats. Example: 4
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"current_page":1,"data":[{"id":3,"seating_area_id":1,"restaurant_id":1,"name":"Table 3","seat_number":4,"created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z"}],"first_page_url":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/tables/add?page=1","from":1,"last_page":1,"last_page_url":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/tables/add?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/tables/add?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/tables/add","per_page":7,"prev_page_url":null,"to":1,"total":1}}
     * @response 403 scenario="Creation failed" {"status":"Error has occured...","message":"SQLSTATE[23000]: Integrity constraint violation","data":""}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\Restaurant] 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b"}
     * @response 422 scenario="Validation error" {"message":"The seating area id field is required.","errors":{"seating_area_id":["The seating area id field is required."]}}
     *
     * @responseField data.data object[] Tables.
     */
    public function addTable(Request $request, Restaurant $restaurant)
    {
        $request->validate([
            'name' => ['required'],
            'seating_area_id' => ['required'],
            'seat_number' => ['required'],
        ]);

        try {
            DB::beginTransaction();
            $request->merge([
                'restaurant_id' => $restaurant->id,
            ]);

            RestaurantTable::create($request->all());

            DB::commit();

            $tables = RestaurantTable::paginate(7);
            return $this->success($tables);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * Update a restaurant table
     *
     * Updates a table's name, seating area and seat number. Returns the first page (7 per page) of all tables.
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     * @urlParam id integer required The numeric ID of the table. Example: 3
     * @bodyParam name string required Table name. Example: Table 3
     * @bodyParam seating_area_id integer required ID of the seating area. Example: 1
     * @bodyParam seat_number integer required Number of seats. Example: 4
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"current_page":1,"data":[{"id":3,"seating_area_id":1,"restaurant_id":1,"name":"Table 3","seat_number":4,"created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z"}],"first_page_url":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/tables/3/update?page=1","from":1,"last_page":1,"last_page_url":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/tables/3/update?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/tables/3/update?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"http://localhost/api/v1/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/tables/3/update","per_page":7,"prev_page_url":null,"to":1,"total":1}}
     * @response 403 scenario="Table not found / update failed" {"status":"Error has occured...","message":"Call to a member function update() on null","data":""}
     * @response 422 scenario="Validation error" {"message":"The name field is required.","errors":{"name":["The name field is required."]}}
     *
     * @responseField data.data object[] Tables.
     */
    public function updateTable(Request $request, $uuid, $id)
    {
        $request->validate([
            'name' => ['required'],
            'seating_area_id' => ['required'],
            'seat_number' => ['required'],
        ]);

        try {
            DB::beginTransaction();

            $table = RestaurantTable::find($id);

            $table->update($request->all());

            DB::commit();

            $tables = RestaurantTable::paginate(7);
            return $this->success($tables);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * List a restaurant's orders
     *
     * Returns the restaurant's non-reservation orders with customer, 5 per page, newest first. Restaurant panel endpoint.
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     * @queryParam search string Search by order UUID. Example: ORD-7F3A
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"current_page":1,"data":[{"id":101,"uuid":"ORD-7F3A21","user_id":7,"restaurant_id":1,"total_amount":1500,"delivery":true,"delivery_address":"Kenyatta Avenue, Nairobi","delivery_fee":200,"delivery_status":"Pending","status":"Pending","service_charge":50,"discount":0,"rider_id":null,"booking_time":null,"reservation_date":null,"created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","user":{"id":7,"uuid":"1a2b3c4d-5e6f-4a7b-8c9d-0e1f2a3b4c5d","name":"John Customer","email":"john@example.com","phone_number":"+254711111111"}}],"first_page_url":"http://localhost/api/v1/restaurant/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/orders?page=1","from":1,"last_page":1,"last_page_url":"http://localhost/api/v1/restaurant/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/orders?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"http://localhost/api/v1/restaurant/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/orders?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"http://localhost/api/v1/restaurant/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/orders","per_page":5,"prev_page_url":null,"to":1,"total":1}}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\Restaurant] 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b"}
     *
     * @responseField data.data object[] Orders with the customer.
     * @responseField data.total integer Total number of orders.
     */
    public function restaurantOrders(Request $request, Restaurant $restaurant)
    {
        $search = $request->query('search');

        $orders = Order::with('user')
                        ->whereDoesntHave('reservation')
                        ->where('restaurant_id', $restaurant->id)
                        ->when($search && $search != '', function ($query) use ($search) {
                            $query->where('uuid', 'LIKE', '%' . $search . '%')
                                ->where(function ($query) use ($search) {
                                    $query->orWhereHas('user', function ($query) use ($search) {
                                        $query->where('name', 'LIKE', '%' . $query . '%');
                                    });
                                });
                        })
                        ->orderBy('created_at', 'DESC')
                        ->paginate(5);

        return $this->success($orders);
    }

    /**
     * List a restaurant's payments
     *
     * Returns payments (with a transaction ID) for the restaurant's orders, 5 per page, newest first. Restaurant panel endpoint.
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     * @queryParam search string Search by order UUID or customer name. Example: John
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"current_page":1,"data":[{"id":55,"uuid":"8e7d6c5b-4a3f-4e2d-9c1b-0a9f8e7d6c5b","transaction_id":"pi_3NxYzAbC123","orderable_id":101,"orderable_type":"App\\Models\\Order","payment_method":"stripe","amount":1500,"status":2,"created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","orderable":{"id":101,"uuid":"ORD-7F3A21","restaurant_id":1,"total_amount":1500,"user":{"id":7,"name":"John Customer","email":"john@example.com"},"restaurant":{"id":1,"uuid":"9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b","name":"Mama's Kitchen"}}}],"first_page_url":"http://localhost/api/v1/restaurant/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/payments?page=1","from":1,"last_page":1,"last_page_url":"http://localhost/api/v1/restaurant/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/payments?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"http://localhost/api/v1/restaurant/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/payments?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"http://localhost/api/v1/restaurant/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/payments","per_page":5,"prev_page_url":null,"to":1,"total":1}}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\Restaurant] 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b"}
     *
     * @responseField data.data object[] Payments with order, customer and restaurant.
     * @responseField data.total integer Total number of payments.
     */
    public function restaurantPayments(Request $request, Restaurant $restaurant)
    {
        $search = $request->query('search');

        $payments = Payment::with('orderable.restaurant', 'orderable.user')
                            ->where('transaction_id', '!=', NULL)
                            ->whereHas('orderable', function ($query) use ($restaurant) {
                                $query->whereHas('restaurant', function ($query) use ($restaurant) {
                                    $query->where('id', '=', $restaurant->id);
                                });
                            })
                            ->when($search && $search != '', function ($query) use ($search) {
                                $query->whereHas('orderable', function ($query) use ($search) {
                                    $query->where(function ($query) use ($search) {
                                        $query->where('uuid', 'LIKE', '%'.$search.'%')
                                            ->orWhereHas('user', function ($query) use ($search) {
                                                $query->where('name', 'LIKE', '%'.$search.'%');
                                            });
                                    });
                                });
                            })
                            ->orderBy('created_at', 'DESC')
                            ->paginate(5);

        return $this->success($payments);
    }

    /**
     * List a restaurant's reservations
     *
     * Returns the restaurant's orders that have a table reservation, with customer, 5 per page, latest reservation date first.
     * Restaurant panel endpoint.
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     * @queryParam search string Search by order UUID. Example: ORD-7F3A
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"current_page":1,"data":[{"id":101,"uuid":"ORD-7F3A21","user_id":7,"restaurant_id":1,"total_amount":1500,"delivery":false,"delivery_address":"Kenyatta Avenue, Nairobi","delivery_fee":200,"delivery_status":"Pending","status":"Pending","service_charge":50,"discount":0,"rider_id":null,"booking_time":null,"reservation_date":"2025-01-20 19:00:00","created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","user":{"id":7,"uuid":"1a2b3c4d-5e6f-4a7b-8c9d-0e1f2a3b4c5d","name":"John Customer","email":"john@example.com","phone_number":"+254711111111"}}],"first_page_url":"http://localhost/api/v1/restaurant/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/reservations?page=1","from":1,"last_page":1,"last_page_url":"http://localhost/api/v1/restaurant/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/reservations?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"http://localhost/api/v1/restaurant/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/reservations?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"http://localhost/api/v1/restaurant/restaurant/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/reservations","per_page":5,"prev_page_url":null,"to":1,"total":1}}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\Restaurant] 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b"}
     *
     * @responseField data.data object[] Reservation orders with the customer.
     */
    public function restaurantReservations(Request $request, Restaurant $restaurant)
    {
        $search = $request->query('search');

        $orders = Order::with('user')
                        ->whereHas('reservation')
                        ->where('restaurant_id', $restaurant->id)
                        ->when($search && $search != '', function ($query) use ($search) {
                            $query->where('uuid', 'LIKE', '%' . $search . '%')
                                ->where(function ($query) use ($search) {
                                    $query->orWhereHas('user', function ($query) use ($search) {
                                        $query->where('name', 'LIKE', '%' . $query . '%');
                                    });
                                });
                        })
                        ->orderBy('reservation_date', 'DESC')
                        ->paginate(5);

        return $this->success($orders);
    }

    /**
     * List employees
     *
     * Returns the users employed at any of the authenticated owner's restaurants, 10 per page. Intended for role `restaurant`.
     *
     * @queryParam search string Search by first name, email or phone number. Example: jane
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"current_page":1,"data":[{"id":12,"uuid":"5c3e2a1b-8d7f-4e6a-9b0c-1d2e3f4a5b6c","name":"Jane Doe","email":"jane@example.com","phone_number":"+254700000001","user_type":"restaurant employee","status":2,"image":null,"type":"food","created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z"}],"first_page_url":"http://localhost/api/v1/restaurant/employees?page=1","from":1,"last_page":1,"last_page_url":"http://localhost/api/v1/restaurant/employees?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"http://localhost/api/v1/restaurant/employees?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"http://localhost/api/v1/restaurant/employees","per_page":10,"prev_page_url":null,"to":1,"total":1}}
     *
     * @responseField data.data object[] Employee users.
     * @responseField data.total integer Total number of employees.
     */
    public function employees(Request $request)
    {
        $search = $request->query('search');

        $user_restaurants = auth()->user()->restaurants->pluck('id');
        $restaurants = UserRestaurant::whereIn('restaurant_id', $user_restaurants)->get()->pluck('user_id');

        $users = User::whereIn('id', $restaurants)
                        ->when($search && $search != '', function ($query) use ($search) {
                            $query->where(function ($query) use ($search) {
                                $query->where('first_name', 'LIKE', '%'.$search.'%')
                                        ->orWhere('email', 'LIKE', '%'.$search.'%')
                                        ->orWhere('phone_number', 'LIKE', '%'.$search.'%');
                            });
                        })
                        ->paginate(10);

        return $this->success($users);
    }

    /**
     * List a restaurant's tables
     *
     * Returns the restaurant's tables with their seating area, 10 per page. Served publicly to the orderer app
     * (`/v1/orderer/orderer-restaurants/{restaurant}/tables`) and to the restaurant panel (`/v1/restaurant/restaurant/{restaurant}/tables`).
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"current_page":1,"data":[{"id":3,"seating_area_id":1,"restaurant_id":1,"name":"Table 3","seat_number":4,"created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","seating_area":{"id":1,"name":"Outdoor","created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z"}}],"first_page_url":"http://localhost/api/v1/orderer/orderer-restaurants/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/tables?page=1","from":1,"last_page":1,"last_page_url":"http://localhost/api/v1/orderer/orderer-restaurants/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/tables?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"http://localhost/api/v1/orderer/orderer-restaurants/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/tables?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"http://localhost/api/v1/orderer/orderer-restaurants/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b/tables","per_page":10,"prev_page_url":null,"to":1,"total":1}}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\Restaurant] 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b"}
     *
     * @responseField data.data object[] Tables with their seating area.
     * @responseField data.total integer Total number of tables.
     */
    public function restaurantTables(Request $request, Restaurant $restaurant)
    {
        if (!$restaurant) {
            return $this->error('Restaurant Not Found', 'Restaurant Not Found', 404);
        }
        $search = $request->query('search');

        $tables = RestaurantTable::with('seatingArea')->where('restaurant_id', $restaurant->id)->paginate(10);

        return $this->success($tables);
    }

    /**
     * List a restaurant's employees
     *
     * Returns the users employed at the given restaurant, 10 per page. Restaurant panel endpoint.
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     * @queryParam search string Search by first name, email or phone number. Example: jane
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"current_page":1,"data":[{"id":12,"uuid":"5c3e2a1b-8d7f-4e6a-9b0c-1d2e3f4a5b6c","name":"Jane Doe","email":"jane@example.com","phone_number":"+254700000001","user_type":"restaurant employee","status":2,"image":null,"type":"food","created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z"}],"first_page_url":"http://localhost/api/v1/restaurant/employees/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b?page=1","from":1,"last_page":1,"last_page_url":"http://localhost/api/v1/restaurant/employees/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"http://localhost/api/v1/restaurant/employees/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"http://localhost/api/v1/restaurant/employees/9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b","per_page":10,"prev_page_url":null,"to":1,"total":1}}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\Restaurant] 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b"}
     *
     * @responseField data.data object[] Employee users.
     */
    public function restaurantEmployees(Request $request, Restaurant $restaurant)
    {
        $search = $request->query('search');

        $restaurants = UserRestaurant::where('restaurant_id', $restaurant->id)->get()->pluck('user_id');

        $users = User::whereIn('id', $restaurants)
                        ->when($search && $search != '', function ($query) use ($search) {
                            $query->where(function ($query) use ($search) {
                                $query->where('first_name', 'LIKE', '%'.$search.'%')
                                        ->orWhere('email', 'LIKE', '%'.$search.'%')
                                        ->orWhere('phone_number', 'LIKE', '%'.$search.'%');
                            });
                        })
                        ->paginate(10);

        return $this->success($users);
    }

    /**
     * Add a restaurant employee
     *
     * Creates a user with role `restaurant employee`, links them to the restaurant and emails them a generated password.
     * Restaurant panel endpoint.
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     * @bodyParam first_name string required First name. Example: Jane
     * @bodyParam last_name string required Last name. Example: Doe
     * @bodyParam email string required Email address. Example: jane@example.com
     * @bodyParam phone_number string required Phone number. Example: +254700000001
     * @bodyParam avatar file Profile image (jpg or png, max 9000 KB).
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":"User created successfully","data":{"id":12,"uuid":"5c3e2a1b-8d7f-4e6a-9b0c-1d2e3f4a5b6c","name":"Jane Doe","email":"jane@example.com","phone_number":"+254700000001","user_type":"restaurant employee","status":2,"image":null,"type":"food","created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z"}}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\Restaurant] 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b"}
     * @response 422 scenario="Validation error" {"message":"The email field is required.","errors":{"email":["The email field is required."]}}
     *
     * @responseField data object The created user.
     * @responseField message string Confirmation message.
     */
    public function addEmployee(Restaurant $restaurant, Request $request)
    {
        $request->validate([
            'first_name' => 'required', 'string',
            'last_name' => 'required', 'string',
            'email' => 'required', 'email',
            'phone_number' => 'required',
            'avatar' => ['nullable', 'mimes:jpg,png', 'max:9000'],
        ]);

        $generate_password = rand(1000000000, 99999999999);

        $user = User::create([
            'name' => $request->first_name.' '.$request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'user_type' => 'restaurant employee',
            'password' => bcrypt($generate_password),
            'status' => 2,
            'image' => $request->hasFile('avatar') ? pathinfo($request->avatar->store('avatar', 'user'), PATHINFO_BASENAME) : NULL,
            'type' => auth()->user()->type
        ]);

        UserRestaurant::create([
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
        ]);

        $user->addRole('restaurant employee');

        Mail::to($request->email)->send(new NewAccount($user, $generate_password, 'restaurant'));

        return $this->success($user, 'User created successfully');
    }

    /**
     * Update a restaurant employee
     *
     * Updates an employee's name, contact details, status and optionally avatar. A new password is generated on every
     * update and emailed to the employee. Restaurant panel endpoint.
     *
     * @urlParam user string required The UUID of the employee user. Example: 5c3e2a1b-8d7f-4e6a-9b0c-1d2e3f4a5b6c
     * @bodyParam first_name string required First name. Example: Jane
     * @bodyParam last_name string required Last name. Example: Doe
     * @bodyParam email string required Email address. Example: jane@example.com
     * @bodyParam phone_number string required Phone number. Example: +254700000001
     * @bodyParam avatar file Profile image (max 9000 KB); replaces the existing one.
     * @bodyParam suspend integer required New account status stored on the user: `1` or `2`. Example: 2
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":"User update successfully","data":{"id":12,"uuid":"5c3e2a1b-8d7f-4e6a-9b0c-1d2e3f4a5b6c","name":"Jane Doe","email":"jane@example.com","phone_number":"+254700000001","user_type":"restaurant employee","status":2,"image":null,"type":"food","created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z"}}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\User] 5c3e2a1b-8d7f-4e6a-9b0c-1d2e3f4a5b6c"}
     * @response 422 scenario="Validation error" {"message":"The suspend field is required.","errors":{"suspend":["The suspend field is required."]}}
     *
     * @responseField data object The updated user.
     * @responseField message string Confirmation message.
     */
    public function updateEmployee(User $user, Request $request)
    {
        $request->validate([
            'first_name' => 'required', 'string',
            'last_name' => 'required', 'string',
            'email' => 'required', 'email',
            'phone_number' => 'required',
            'avatar' => ['nullable', 'max:9000'],
            'suspend' => ['required', 'in:1,2'],
        ]);

        $generate_password = rand(1000000000, 99999999999);

        $user->update([
            'name' => $request->first_name.' '.$request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'status' => 2,
            'password' => bcrypt($generate_password),
            'status' => $request->suspend,
        ]);

        if ($request->hasFile('avatar')) {
            $image = explode('/', $user->image);
            Storage::disk('user')->delete('/avatar/'.end($image));
            $user->update([
                'image' => pathinfo($request->avatar->store('avatar', 'user'), PATHINFO_BASENAME),
            ]);
        }

        Mail::to($request->email)->send(new NewAccount($user, $generate_password));

        return $this->success($user, 'User update successfully');
    }

    /**
     * Review a restaurant
     *
     * Stores a rating (and optional review text) by the authenticated user for a restaurant and recalculates the
     * restaurant's average rating.
     *
     * @bodyParam restaurant_id integer required The numeric ID of the restaurant. Example: 1
     * @bodyParam rating integer required Rating from 1 to 5. Example: 5
     * @bodyParam review string A review of the restaurant. Example: Great food and fast service!
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":"Review created successfully"}
     * @response 400 scenario="Validation error" {"status":"Error has occured...","message":{"rating":["The rating field is required."]},"data":""}
     * @response 404 scenario="Restaurant not found" {"status":"Error has occured...","message":"No such restaurant","data":""}
     *
     * @responseField data string Confirmation text.
     */
    public function storeReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'restaurant_id' => ['required'],
            'rating' => ['required', 'integer', 'max:5', 'min:1'],
            'review' => ['nullable', 'sometimes', 'string']
        ]);

        if ($validator->fails()) {
            return $this->error('', $validator->messages(), 400);
        }

        $restaurant = Restaurant::find($request->restaurant_id);

        if (!$restaurant) {
            return $this->error('', 'No such restaurant', 404);
        }

        $restaurant->reviews()->create([
            'user_id' => auth()->id(),
            'review' => $request->has('review') && !empty($request->review) ? $request->review : NULL,
            'rating' => $request->rating
        ]);

        // Update restaurant average rating
        $total_reviews_count = $restaurant->reviews->count();
        if ($total_reviews_count > 0) {
            $total_reviews = $restaurant->reviews->sum('rating');

            $restaurant->update([
                'average_rating' => round($total_reviews / $total_reviews_count, 2)
            ]);
        }

        return $this->success('Review created successfully');
    }

    /**
     * List grocery shops
     *
     * Returns restaurants that sell groceries (have menus in the Groceries category), paginated. Guests get approved shops
     * with at least one active menu; role `orderer` gets approved shops filtered by the filter params; role `rider` gets
     * approved shops with menus; role `restaurant` gets their own shops (10 per page); role `restaurant employee` gets their assigned shop.
     *
     * @queryParam search string Restaurant/employee callers only: search by name or address. Example: Mart
     * @queryParam status string Role `restaurant` only: filter by raw status (1 = Pending, 2 = Approved, 3 = Denied). Example: 2
     * @queryParam uuid[eq] string Filter by restaurant UUID. Applied for `orderer` and `rider` callers. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     * @queryParam name[eq] string Filter by exact restaurant name. Applied for `orderer` and `rider` callers. Example: Mama's Kitchen
     * @queryParam nameShort[eq] string Filter by exact short name. Applied for `orderer` and `rider` callers. Example: MK
     * @queryParam email[eq] string Filter by exact email. Applied for `orderer` and `rider` callers. Example: info@mamaskitchen.co.ke
     * @queryParam aboutShort[eq] string Filter by exact short description. Applied for `orderer` and `rider` callers. Example: Kenyan cuisine
     * @queryParam phoneNo[eq] string Filter by exact phone number. Applied for `orderer` and `rider` callers. Example: +254712345678
     * @queryParam address[eq] string Filter by exact address. Applied for `orderer` and `rider` callers. Example: Moi Avenue, Nairobi
     * @queryParam city[eq] string Filter by city. Applied for `orderer` and `rider` callers. Example: Nairobi
     * @queryParam state[eq] string Filter by state/county. Applied for `orderer` and `rider` callers. Example: Nairobi
     * @queryParam mapLocation[eq] string Filter by map location (operators: eq, gt, lt). Applied for `orderer` and `rider` callers. Example: Nairobi
     * @queryParam latitude[gt] string Filter by latitude (operators: eq, gt, lt). Applied for `orderer` and `rider` callers. Example: -1.5
     * @queryParam longitude[lt] string Filter by longitude (operators: eq, gt, lt). Applied for `orderer` and `rider` callers. Example: 37.0
     * @queryParam postalCode[eq] string Filter by postal code (operators: eq, gt, lt). Applied for `orderer` and `rider` callers. Example: 00100
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"uuid":"9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b","attributes":{"name":"Mama's Kitchen","nameShort":"MK","email":"info@mamaskitchen.co.ke","about":"Authentic Kenyan cuisine served fresh daily.","aboutShort":"Kenyan cuisine","phoneNo":"+254712345678","address":"Moi Avenue, Nairobi","city":"Nairobi","state":"Nairobi","postalCode":"00100","latitude":"-1.2833","longitude":"36.8167","mapLocation":"Nairobi","url":"https://mamaskitchen.co.ke","logo":"https://moboeats.co.uk/storage/companyLogos/logos/logo.png","sitting_capacity":40,"status":"Approved","denied_reason":"","created_at":"2025-01-10T09:00:00.000000Z","updated_at":"2025-01-10T09:00:00.000000Z","average_rating":4.5,"is_open":true,"country":"Kenya","country_code":"KE"},"relationships":{"menus":[],"reviews":[],"restaurantTables":[]}}],"links":{"first":"http://localhost/api/v1/orderer/orderer-grocery-shops?page=1","last":"http://localhost/api/v1/orderer/orderer-grocery-shops?page=1","prev":null,"next":null},"meta":{"current_page":1,"from":1,"last_page":1,"links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"http://localhost/api/v1/orderer/orderer-grocery-shops?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"path":"http://localhost/api/v1/orderer/orderer-grocery-shops","per_page":15,"to":1,"total":1}}
     *
     * @responseField data object[] List of grocery shops.
     * @responseField meta object Pagination metadata.
     */
    public function groceryShops(Request $request)
    {
        if (auth()->check()) {
            if (auth()->user()->hasRole('orderer')) {
                $filter =  new RestaurantFilter();
                $filterItems = $filter->transform($request);

                $restaurants = Restaurant::Approved()->groceryShops()->where($filterItems);

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

                return new RestaurantCollection($restaurants->with('questionnaire', 'reviews', 'restaurantTables.seatingArea')->paginate());
            }

            if (auth()->user()->hasRole('rider')) {
                $radius = 10;
                $latitude = $request->latitude;
                $longitude = $request->longitude;
                $filter =  new RestaurantFilter();
                $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
                $includeQuestionnaire = $request->query('questionnaire');
                $restaurants = Restaurant::Approved()->hasMenu()->groceryShops()->where($filterItems);

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

            if (auth()->user()->hasRole('restaurant')) {
                $search = $request->query('search');
                $status = $request->query('status');

                $restaurants = Restaurant::withCount('orders', 'menus')
                                        ->groceryShops()
                                        ->with('orders.payment', 'orders.reservation', 'menus', 'restaurantTables.seatingArea')
                                        ->where('user_id', Auth::user()->id)
                                        ->when($search && $search != '', function ($query) use ($search) {
                                            $query->where(function ($query) use ($search) {
                                                $query->where('name', 'LIKE', '%'.$search.'%')
                                                        ->orWhere('address', 'LIKE', '%'.$search.'%');
                                            });
                                        })
                                        ->when($status && $status != '', function ($query) use ($status) {
                                            $query->where(function ($query) use ($status) {
                                                $query->where('status', $status);
                                            });
                                        })
                                        ->orderBy('created_at', 'DESC')
                                        ->paginate(10);

                return new RestaurantCollection($restaurants);
            }

            if (auth()->user()->hasRole('restaurant employee')) {
                $search = $request->query('search');

                $restaurant = UserRestaurant::where('user_id', auth()->id())->first();

                $restaurants = Restaurant::withCount('orders', 'menus')
                                            ->groceryShops()
                                            ->with('orders.payment', 'orders.reservation', 'menus', 'restaurantTables.seatingArea')
                                            ->where('id', $restaurant->restaurant_id)
                                            ->when($search && $search != '', function ($query) use ($search) {
                                                $query->where(function ($query) use ($search) {
                                                    $query->where('name', 'LIKE', '%'.$search.'%')
                                                            ->orWhere('address', 'LIKE', '%'.$search.'%');
                                                });
                                            })
                                            ->first();

                return new RestaurantCollection($restaurants);
            }
        } else {
            $restaurants = Restaurant::Approved()->hasMenu()->groceryShops();

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

            return new RestaurantCollection($restaurants->with('questionnaire', 'reviews', 'restaurantTables.seatingArea')->paginate());
        }
    }

    /**
     * Get registration fee status
     *
     * Returns `paid` when the restaurant's registration fee has been paid; otherwise returns the registration fee amount
     * configured in settings. Restaurant panel endpoint.
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     *
     * @response 200 scenario="Already paid" {"status":"Request was succesful","message":null,"data":{"message":"paid"}}
     * @response 200 scenario="Fee due" {"status":"Request was succesful","message":null,"data":{"data":"50"}}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\Restaurant] 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b"}
     *
     * @responseField data.message string `paid` when the fee has been paid.
     * @responseField data.data string Registration fee amount when unpaid.
     */
    public function registrationFeePayment(Restaurant $restaurant)
    {
        // Check if registration fee is paid
        $registration_fee = $restaurant->registration_fee;

        if ($registration_fee) {
            return $this->success(['message' => 'paid']);
        }

        $registration_fee = Setting::where('name', 'Registration Fee')->first();

        return $this->success(['data' => $registration_fee->variable]);
    }

    /**
     * Complete registration fee payment
     *
     * Marks the restaurant's registration fee as paid using the amount configured in settings. Restaurant panel endpoint.
     *
     * @urlParam restaurant string required The UUID of the restaurant. Example: 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"message":"Registration fee paid successfully"}}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\Restaurant] 9b1f7c2e-3a4d-4e5f-8a6b-7c8d9e0f1a2b"}
     *
     * @responseField data.message string Confirmation message.
     */
    public function completeRegistrationFeePayment(Restaurant $restaurant)
    {
        $registration_fee = Setting::where('name', 'Registration Fee')->first();

        $restaurant->update([
            $registration_fee->variable
        ]);

        return $this->success(['message' => 'Registration fee paid successfully']);
    }
}
