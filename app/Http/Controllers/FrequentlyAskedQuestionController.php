<?php

namespace App\Http\Controllers;

use App\Models\FrequentlyAskedQuestion;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group FAQs
 *
 * Frequently asked questions (`v1/frequently-asked-questions`).
 */
class FrequentlyAskedQuestionController extends Controller
{
    use HttpResponses;

    /**
     * List FAQs
     *
     * Returns all frequently asked questions, 10 per page.
     *
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "questions": {
     *       "current_page": 1,
     *       "data": [{"id": 1, "section": "customer", "question": "How do I track my order?", "answer": "Open the Orders tab and select your order.", "created_at": "2024-03-24T10:00:00.000000Z", "updated_at": "2024-03-24T10:00:00.000000Z"}],
     *       "first_page_url": "https://moboeats.com/api/v1/frequently-asked-questions?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "https://moboeats.com/api/v1/frequently-asked-questions?page=1",
     *       "links": [],
     *       "next_page_url": null,
     *       "path": "https://moboeats.com/api/v1/frequently-asked-questions",
     *       "per_page": 10,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *     }
     *   }
     * }
     * @responseField data.questions object Paginated questions (Laravel paginator).
     */
    public function index($section = NULL)
    {
        $questions = FrequentlyAskedQuestion::
                            when($section && $section != NULL, function ($query) use ($section) {
                                $query->where('section', $section);
                            })
                            ->paginate(10);

        return $this->success([
            'questions' => $questions,
        ]);
    }

    /**
     * Create a FAQ
     *
     * Creates a frequently asked question.
     *
     * @bodyParam section string required One of: `general`, `customer`, `partner`, `rider`. Example: customer
     * @bodyParam question string required The question. Example: How do I track my order?
     * @bodyParam answer string required The answer. Example: Open the Orders tab and select your order.
     *
     * @response 200 scenario="Created" {"status": "Request was succesful", "message": "Question stored successfully", "data": ""}
     * @response 400 scenario="Validation error" {"status": "Error has occured...", "message": "Invalid data", "data": {"section": ["The selected section is invalid."]}}
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'section' => ['required', 'in:general,customer,partner,rider'],
            'question' => ['required'],
            'answer' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Invalid data', 400);
        }

        FrequentlyAskedQuestion::create($request->all());

        return $this->success('', 'Question stored successfully');
    }

    /**
     * Update a FAQ
     *
     * Updates a frequently asked question. Available as `PUT` and `PATCH`.
     *
     * @urlParam frequently_asked_question integer required The numeric ID of the question. Example: 1
     * @bodyParam section string required One of: `general`, `customer`, `partner`, `rider`. Example: customer
     * @bodyParam question string required The question. Example: How do I track my order?
     * @bodyParam answer string required The answer. Example: Open the Orders tab and select your order.
     *
     * @response 200 scenario="Updated" {"status": "Request was succesful", "message": "Question successfully updated", "data": ""}
     * @response 400 scenario="Validation error" {"status": "Error has occured...", "message": "Invalid data", "data": {"section": ["The selected section is invalid."]}}
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\FrequentlyAskedQuestion] 99"}
     */
    public function update(Request $request, FrequentlyAskedQuestion $frequentlyAskedQuestion)
    {
        $validator = Validator::make($request->all(), [
            'section' => ['required', 'in:general,customer,partner,rider'],
            'question' => ['required'],
            'answer' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Invalid data', 400);
        }

        $frequentlyAskedQuestion->update($request->all());

        return $this->success('', 'Question successfully updated');
    }

    /**
     * Delete a FAQ
     *
     * Deletes a frequently asked question.
     *
     * @urlParam frequently_asked_question integer required The numeric ID of the question. Example: 1
     *
     * @response 200 scenario="Deleted" {"status": "Request was succesful", "message": "Question successfully deleted", "data": ""}
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\FrequentlyAskedQuestion] 99"}
     */
    public function destroy(FrequentlyAskedQuestion $frequentlyAskedQuestion)
    {
        $frequentlyAskedQuestion->delete();

        return $this->success('', 'Question successfully deleted');
    }
}
