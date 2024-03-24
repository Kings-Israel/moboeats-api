<?php

namespace App\Http\Controllers;

use App\Models\FrequentlyAskedQuestion;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FrequentlyAskedQuestionController extends Controller
{
    use HttpResponses;

    /**
     * Get all frequently asked questions
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

    public function destroy(FrequentlyAskedQuestion $frequentlyAskedQuestion)
    {
        $frequentlyAskedQuestion->delete();

        return $this->success('', 'Question successfully deleted');
    }
}
