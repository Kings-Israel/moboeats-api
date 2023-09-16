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
 * @group Restaurant Questionnaire
 * 
 * Questionnaire API resource
 */
class QuestionnaireController extends Controller
{
    use HttpResponses;
    /**
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
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
     * Display the specified resource.
     */
    public function show(Questionnaire $questionnaire)
    {
        $restaurant = Restaurant::where('id', $questionnaire->restaurant_id)->first();
        return $this->isNotAuthorized($restaurant) ?  $this->isNotAuthorized($restaurant) :  new QuestionnaireResource($questionnaire);
    }

    
    /**
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
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
