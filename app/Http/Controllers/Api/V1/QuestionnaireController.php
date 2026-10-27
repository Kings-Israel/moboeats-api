<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\V1\QuestionnareFilter;
use App\Models\Questionnaire;
use App\Http\Requests\V1\StoreQuestionnaireRequest;
use App\Http\Requests\V1\UpdateQuestionnaireRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\QuestionnaireCollection;
use App\Http\Resources\V1\QuestionnaireResource;
use App\Models\Restaurant;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @group Restaurant Management
 *
 * Restaurant questionnaire ("more info"): whether the restaurant offers delivery and/or table booking.
 */
class QuestionnaireController extends Controller
{
    use HttpResponses;
    /**
     * List restaurant questionnaires
     *
     * Returns the questionnaires of the authenticated user's first approved restaurant (paginated).
     *
     * @queryParam uuid[eq] string Filter by questionnaire UUID. Example: 3c2b1a0f-9e8d-4c7b-a6f5-e4d3c2b1a0f9
     * @queryParam restaurantId[eq] integer Filter by restaurant ID. Example: 1
     * @queryParam delivery[eq] boolean Filter by delivery flag. Example: 1
     * @queryParam booking[eq] boolean Filter by booking flag. Example: 0
     * @queryParam status[eq] integer Filter by status. Example: 1
     * @queryParam createdBy[eq] string Filter by creator. Example: 3
     * @queryParam updatedBy[eq] string Filter by last updater. Example: 3
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "data": [{"uuid": "3c2b1a0f-9e8d-4c7b-a6f5-e4d3c2b1a0f9", "restaurantId": 1, "delivery": 1, "booking": 0}],
     *   "links": {"first": "https://moboeats.com/api/v1/restaurant/more-info?page=1", "last": "https://moboeats.com/api/v1/restaurant/more-info?page=1", "prev": null, "next": null},
     *   "meta": {"current_page": 1, "from": 1, "last_page": 1, "links": [], "path": "https://moboeats.com/api/v1/restaurant/more-info", "per_page": 15, "to": 1, "total": 1}
     * }
     * @response 403 scenario="Error" {"status": "Error has occured...", "message": "Attempt to read property \"id\" on null", "data": ""}
     * @response 422 scenario="Restaurant owner has no restaurant" {"data": "no_restaurant"}
     * @responseField data object[] The questionnaires.
     */
    public function index(Request $request)
    {
        try {

            //code...
            DB::beginTransaction();
            $filter =  new QuestionnareFilter();
            $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
            $restaurant = Auth::user()->restaurants->where('status', 2)->first();
            $questionnaires = Questionnaire::where('restaurant_id', $restaurant->id)
            ->where($filterItems);
            DB::commit();
            return new QuestionnaireCollection($questionnaires->paginate()->appends($request->query()));

        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
            //throw $th;
        }
    }

    
    /**
     * Save restaurant questionnaire
     *
     * Creates the questionnaire for a restaurant, replacing any existing one.
     *
     * @bodyParam restaurantUuid string required The UUID of the restaurant. Example: 5e6f7a8b-9c0d-4e1f-a2b3-c4d5e6f7a8b9
     * @bodyParam delivery boolean required Whether the restaurant offers delivery. Example: true
     * @bodyParam booking boolean required Whether the restaurant accepts table bookings. Example: false
     *
     * @response 201 scenario="Created" {"data": {"uuid": "3c2b1a0f-9e8d-4c7b-a6f5-e4d3c2b1a0f9", "restaurantId": 1, "delivery": true, "booking": false}}
     * @response 403 scenario="Error" {"status": "Error has occured...", "message": "Attempt to read property \"id\" on null", "data": ""}
     * @response 422 scenario="Validation error" {"message": "The restaurant uuid field is required.", "errors": {"restaurantUuid": ["The restaurant uuid field is required."]}}
     * @responseField data.delivery boolean Delivery offered.
     * @responseField data.booking boolean Booking offered.
     */
    public function store(StoreQuestionnaireRequest $request)
    {
        try {
            DB::beginTransaction();
            $restaurant = Restaurant::where('uuid', $request->restaurantUuid)->first();
            Questionnaire::where('restaurant_id', $restaurant->id)->get();
            if (Questionnaire::where('restaurant_id', $restaurant->id)->exists()) {
                Questionnaire::where('restaurant_id', $restaurant->id)->delete();
            }
            $questionnaire = Questionnaire::create([
                'restaurant_id' =>$restaurant->id,
                'delivery' =>$request->delivery,
                'booking' =>$request->booking,
            ]);
            DB::commit();
            return new QuestionnaireResource($questionnaire);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * Get a restaurant questionnaire
     *
     * Returns a questionnaire. Only the owner of the questionnaire's restaurant may view it.
     *
     * @urlParam more_info string required The UUID of the questionnaire. Example: 3c2b1a0f-9e8d-4c7b-a6f5-e4d3c2b1a0f9
     *
     * @response 200 {"data": {"uuid": "3c2b1a0f-9e8d-4c7b-a6f5-e4d3c2b1a0f9", "restaurantId": 1, "delivery": true, "booking": false}}
     * @response 403 scenario="Not the restaurant owner" {"status": "Error has occured...", "message": "You are not authorized to make this request", "data": ""}
     */
    public function show(Questionnaire $questionnaire)
    {
        $restaurant = Restaurant::where('id', $questionnaire->restaurant_id)->first();
        return $this->isNotAuthorized($restaurant) ?  $this->isNotAuthorized($restaurant) :  new QuestionnaireResource($questionnaire);
    }

    
    /**
     * Update a restaurant questionnaire
     *
     * Updates a questionnaire. Only the owner of the questionnaire's restaurant may update it.
     *
     * @urlParam more_info string required The UUID of the questionnaire. Example: 3c2b1a0f-9e8d-4c7b-a6f5-e4d3c2b1a0f9
     * @bodyParam delivery boolean Whether the restaurant offers delivery. Example: true
     * @bodyParam booking boolean Whether the restaurant accepts table bookings. Example: true
     *
     * @response 200 {"data": {"uuid": "3c2b1a0f-9e8d-4c7b-a6f5-e4d3c2b1a0f9", "restaurantId": 1, "delivery": true, "booking": true}}
     * @response 403 scenario="Not the restaurant owner" {"status": "Error has occured...", "message": "You are not authorized to make this request", "data": ""}
     */
    public function update(UpdateQuestionnaireRequest $request, Questionnaire $questionnaire)
    {
        try {
            DB::beginTransaction();
            $restaurant = Restaurant::where('id', $questionnaire->restaurant_id)->first();
            if ($this->isNotAuthorized($restaurant)) {
                return $this->isNotAuthorized($restaurant);
            }
            $questionnaire->update($request->all());

            DB::commit();
            return new QuestionnaireResource($questionnaire);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * Delete a restaurant questionnaire
     *
     * Deletes a questionnaire. Only the owner of the questionnaire's restaurant may delete it.
     *
     * @urlParam more_info string required The UUID of the questionnaire. Example: 3c2b1a0f-9e8d-4c7b-a6f5-e4d3c2b1a0f9
     *
     * @response 200 scenario="Deleted" 1
     * @response 403 scenario="Not the restaurant owner" {"status": "Error has occured...", "message": "You are not authorized to make this request", "data": ""}
     */
    public function destroy(Questionnaire $questionnaire)
    {
        try {
            DB::beginTransaction();
            $restaurant = Restaurant::where('id', $questionnaire->restaurant_id)->first();
            return $this->isNotAuthorized($restaurant) ?  $this->isNotAuthorized($restaurant) :  $questionnaire->delete();
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
