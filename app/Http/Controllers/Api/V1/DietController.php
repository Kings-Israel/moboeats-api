<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DietPlan;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;

class DietController extends Controller
{
    use HttpResponses;

    public function plans(Request $request)
    {
        $date = $request->query('date');

        $plans = DietPlan::where('user_id', auth()->id())->whereDate('date', '<=', now()->format('Y-m-d'))->paginate(10);

        return $this->success($plans);
    }
}
