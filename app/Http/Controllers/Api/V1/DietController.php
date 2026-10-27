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
 * @group Diet Plans
 *
 * Diet subscription packages and user diet plan subscriptions.
 */
class DietController extends Controller
{
    use HttpResponses;

    /**
     * List diet subscription packages
     *
     * Returns a paginated list of diet subscription packages. Available publicly under `v1/orderer/diet/packages`
     * and to admins under `v1/admin/diet/packages`; users with role `admin` additionally receive each package's
     * subscriptions with the subscribed users.
     *
     * @queryParam per_page integer Number of items per page. Example: 15
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "data": [
     *       {"id": 1, "name": "Starter", "tag_line": "Eat better every day", "description": "Weekly personalised meal plans.", "price": "KSH 1500/Monthly"}
     *     ],
     *     "links": {"first": "https://moboeats.com/api/v1/orderer/diet/packages?page=1", "last": "https://moboeats.com/api/v1/orderer/diet/packages?page=1", "prev": null, "next": null},
     *     "meta": {"current_page": 1, "from": 1, "last_page": 1, "links": [], "path": "https://moboeats.com/api/v1/orderer/diet/packages", "per_page": 15, "to": 1, "total": 1}
     *   }
     * }
     * @responseField data.data object[] Packages on this page.
     * @responseField data.data[].price string Formatted price, e.g. `KSH 1500/Monthly`.
     * @responseField data.data[].subscriptions object[] Admin only. Subscriptions of this package with their users.
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
     * List my diet plan subscriptions
     *
     * Returns all diet plan subscriptions of the currently authenticated user, with their package.
     * The route is not behind `auth:sanctum`, so without a resolved user an empty list is returned.
     *
     * @response 200 {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": [
     *     {"id": 1, "user_id": 5, "diet_subscription_package_id": 1, "start_date": "2024-06-12", "end_date": "2024-07-12", "status": "paid", "created_at": "2024-06-12T10:00:00.000000Z", "updated_at": "2024-06-12T10:00:00.000000Z", "package": {"id": 1, "name": "Starter", "tag_line": "Eat better every day", "description": "Weekly personalised meal plans.", "price": 1500, "currency": "KSH", "duration": "monthly", "created_at": "2024-06-12T10:00:00.000000Z", "updated_at": "2024-06-12T10:00:00.000000Z"}}
     *   ]
     * }
     * @responseField data object[] The user's diet subscriptions.
     * @responseField data[].status string `pending` or `paid`.
     */
    public function subscription()
    {
        return $this->success(DietSubscription::with('package')->where('user_id', auth()->id())->get());
    }
}
