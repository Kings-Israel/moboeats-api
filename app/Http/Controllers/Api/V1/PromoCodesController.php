<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\PromoCodeExport;
use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\Restaurant;
use App\Models\UserRestaurant;
use App\Traits\HttpResponses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

/**
 * @group Promo Codes & Discounts
 *
 * Restaurant promo codes managed by restaurant owners/employees (`v1/restaurant/promo-codes`).
 */
class PromoCodesController extends Controller
{
    use HttpResponses;

    /**
     * List promo codes
     *
     * Returns promo codes (10 per page) with their order counts. Users with role `restaurant` see codes for their own
     * restaurants (filterable) plus the list of their restaurants; users with role `restaurant employee` see their
     * restaurant's codes; other users see all codes.
     *
     * @queryParam search string Filter by promo code or restaurant name (restaurant owners only). Example: WELCOME
     * @queryParam from_created_at string Only codes created/starting/ending on or after this date (restaurant owners only). Example: 2024-01-01
     * @queryParam to_created_at string Only codes created/starting/ending on or before this date (restaurant owners only). Example: 2024-12-31
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "promo_codes": {
     *       "current_page": 1,
     *       "data": [{"id": 1, "restaurant_id": 1, "code": "WELCOME10", "type": "percentage", "value": 10, "status": "active", "start_date": "2024-01-01", "end_date": "2024-12-31", "created_at": "2024-01-01T10:00:00.000000Z", "updated_at": "2024-01-01T10:00:00.000000Z", "orders_count": 3, "restaurant": {"id": 1, "uuid": "5e6f7a8b-9c0d-4e1f-a2b3-c4d5e6f7a8b9", "name": "Mama Oliech", "status": "Approved"}}],
     *       "first_page_url": "https://moboeats.com/api/v1/restaurant/promo-codes?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "https://moboeats.com/api/v1/restaurant/promo-codes?page=1",
     *       "links": [],
     *       "next_page_url": null,
     *       "path": "https://moboeats.com/api/v1/restaurant/promo-codes",
     *       "per_page": 10,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *     },
     *     "restaurants": [{"id": 1, "uuid": "5e6f7a8b-9c0d-4e1f-a2b3-c4d5e6f7a8b9", "name": "Mama Oliech", "status": "Approved"}]
     *   }
     * }
     * @response 422 scenario="Restaurant owner has no restaurant" {"data": "no_restaurant"}
     * @responseField data.promo_codes object Paginated promo codes (Laravel paginator).
     * @responseField data.restaurants object[] Restaurant owners only: the owner's restaurants (sorted by name).
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $from_created_at = $request->query('from_created_at');
        $to_created_at = $request->query('to_created_at');

        $restaurants = [];

        if (auth()->user()->hasRole('restaurant')) {
            $restaurants_ids = auth()->user()->restaurants->pluck('id');

            $codes = PromoCode::with('restaurant')
                                ->withCount('orders')
                                ->whereIn('restaurant_id', $restaurants_ids)
                                ->when($from_created_at && $from_created_at != '', function ($query) use ($from_created_at) {
                                    $query->where(function ($query) use ($from_created_at) {
                                        $query->whereDate('created_at', '>=', Carbon::parse($from_created_at))
                                                ->orWhereDate('start_date', '>=', Carbon::parse($from_created_at))
                                                ->orWhereDate('end_date', '>=', Carbon::parse($from_created_at));
                                    });
                                })
                                ->when($to_created_at && $to_created_at != '', function ($query) use ($to_created_at) {
                                    $query->where(function ($query) use ($to_created_at) {
                                        $query->whereDate('created_at', '<=', Carbon::parse($to_created_at))
                                                ->orWhereDate('start_date', '<=', Carbon::parse($to_created_at))
                                                ->orWhereDate('end_date', '<=', Carbon::parse($to_created_at));
                                    });
                                })
                                ->when($search && $search != '', function ($query) use ($search) {
                                    $query->where(function ($query) use ($search) {
                                        $query->where('code', 'LIKE', '%'.$search.'%')
                                            ->orWhereHas('restaurant', function ($query) use ($search) {
                                                $query->where('name', 'LIKE', '%'.$search.'%');
                                            });
                                    });
                                })
                                ->paginate(10);
            $restaurants = Restaurant::whereIn('id', $restaurants_ids)->orderBy('name', 'ASC')->get();
        } else if (auth()->user()->hasRole('restaurant employee')) {
            $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();

            $codes = PromoCode::withCount('orders')->whereIn('restaurant_id', $user_restaurant->restaurant_id)->paginate(10);
        } else {
            $codes = PromoCode::with('restaurant')->withCount('orders')->paginate(10);
        }

        return $this->success([
            'promo_codes' => $codes,
            'restaurants' => $restaurants
        ]);
    }

    /**
     * Export promo codes
     *
     * Exports promo codes to an Excel file and downloads it.
     *
     * @queryParam search string Filter by promo code or restaurant name. Example: WELCOME
     * @queryParam from_created_at string Only codes created/starting/ending on or after this date. Example: 2024-01-01
     * @queryParam to_created_at string Only codes created/starting/ending on or before this date. Example: 2024-12-31
     *
     * @response 200 scenario="File download" <<binary>> The exported Excel (.xlsx) file.
     * @response 422 scenario="Restaurant owner has no restaurant" {"data": "no_restaurant"}
     */
    public function export(Request $request)
    {
        $search = $request->query('search');
        $from_created_at = $request->query('from_created_at');
        $to_created_at = $request->query('to_created_at');

        $unique = explode('-', Str::uuid())[0];

        Excel::store(new PromoCodeExport($search, $from_created_at, $to_created_at), 'promo-codes'.$unique.'.xlsx', 'exports');

        return Storage::disk('exports')->download('promo-codes'.$unique.'.xlsx');
    }

    /**
     * Create promo codes
     *
     * Creates the same promo code for each of the given restaurants.
     *
     * @bodyParam restaurant_ids string required JSON-encoded array of restaurant IDs. Example: [1,2]
     * @bodyParam code string required The promo code. Example: WELCOME10
     * @bodyParam start_date string required Start date. Example: 2024-01-01
     * @bodyParam end_date string required End date. Example: 2024-12-31
     * @bodyParam type string required Discount type. One of: `amount`, `percentage`. Example: percentage
     * @bodyParam value integer required Discount value. Example: 10
     * @bodyParam status string required One of: `active`, `inactive`. Example: active
     *
     * @response 200 scenario="Created" {"status": "Request was succesful", "message": "Promo Code added successfully", "data": ""}
     * @response 422 scenario="Validation error" {"message": "The code field is required.", "errors": {"code": ["The code field is required."]}}
     */
    public function store(Request $request)
    {
        $request->validate([
            'restaurant_ids' => ['required'],
            'code' => ['required'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'type' => ['required', 'in:amount,percentage'],
            'value' => ['required', 'integer'],
            'status' => ['required', 'in:active,inactive']
        ]);

        collect(json_decode($request->restaurant_ids, true))->each(function ($restaurant) use ($request) {
            $promo = collect($request)->merge(['restaurant_id' => $restaurant])->forget('restaurant_ids');

            PromoCode::create($promo->all());
        });

        return $this->success('', 'Promo Code added successfully');
    }

    /**
     * Update a promo code
     *
     * Updates a promo code.
     *
     * @urlParam promo_code integer required The numeric ID of the promo code. Example: 1
     * @bodyParam code string required The promo code. Example: WELCOME10
     * @bodyParam start_date string required Start date. Example: 2024-01-01
     * @bodyParam end_date string required End date. Example: 2024-12-31
     * @bodyParam type string required Discount type. One of: `amount`, `percentage`. Example: amount
     * @bodyParam value integer required Discount value. Example: 100
     * @bodyParam status string required One of: `active`, `inactive`. Example: inactive
     *
     * @response 200 scenario="Updated" {"status": "Request was succesful", "message": "Promo code updated successfully", "data": ""}
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\PromoCode] 99"}
     * @response 422 scenario="Validation error" {"message": "The selected status is invalid.", "errors": {"status": ["The selected status is invalid."]}}
     */
    public function update(Request $request, PromoCode $promo_code)
    {
        $request->validate([
            'code' => ['required'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'type' => ['required', 'in:amount,percentage'],
            'value' => ['required', 'integer'],
            'status' => ['required', 'in:active,inactive']
        ]);

        $promo_code->update($request->all());

        return $this->success('', 'Promo code updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PromoCode $promo_code)
    {
        $promo_code->delete();

        return $this->success('', 'Promo code deleted successfully');
    }
}
