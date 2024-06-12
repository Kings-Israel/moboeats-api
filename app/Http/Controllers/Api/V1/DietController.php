<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DietSubscriptionPackagesResource;
use App\Models\DietPlan;
use App\Models\DietSubscription;
use App\Models\DietSubscriptionPackage;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group Diet and Diet Planning APIs
 */
class DietController extends Controller
{
    use HttpResponses;

    /**
     * Get subscription packages
     */
    public function packages(Request $request)
    {
        $per_page = $request->query('per_page');

        if (auth()->check() && auth()->user()->hasRole('admin')) {
            $packages = DietSubscriptionPackagesResource::collection(DietSubscriptionPackage::with('subscriptions.user')->paginate($per_page))->response()->getData(true);
        } else {
            $packages = DietSubscriptionPackagesResource::collection(DietSubscriptionPackage::paginate($per_page))->response()->getData(true);
        }

        return $this->success($packages);
    }

    /**
     * Get diet plans
     * @authenticated
     */
    public function plans(Request $request)
    {
        $date = $request->query('date');

        $plans = DietPlan::where('user_id', auth()->id())->whereDate('date', '<=', now()->format('Y-m-d'))->paginate(10);

        return $this->success($plans);
    }

    /**
     * Get user's diet plan subscriptions
     */
    public function subscription()
    {
        return $this->success(DietSubscription::with('package')->where('user_id', auth()->id())->get());
    }
}
