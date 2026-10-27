<?php

namespace App\Http\Controllers;

use App\Http\Resources\V1\OrphanageResource;
use App\Models\Orphanage;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group Orphanages
 *
 * Orphanages that customers can donate orders to. Public endpoints under `v1/orphanages`; admin endpoints under
 * `v1/admin/orphanages` (also duplicated under `v1/admin/v1/orphanages`).
 */
class OrphanageController extends Controller
{
    use HttpResponses;

    /**
     * List orphanages
     *
     * Returns orphanages (paginated). Guests and users with role `orderer`, `restaurant` or `restaurant employee` see only
     * approved orphanages; other authenticated users (e.g. admins) see all.
     *
     * @queryParam per_page integer Number of items per page. Example: 15
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "data": [{"id": 1, "name": "Hope Children Home", "email": "info@hopehome.org", "contact_user_name": "Mary Wanjiku", "contact_user_email": "mary@hopehome.org", "contact_user_phone_number": "254711000111", "logo": "https://moboeats.com/storage/orphanages/logo/hope.png", "status": "Approved", "created_by": null, "location": "Kibera, Nairobi", "latitude": -1.3133, "longitude": 36.7876}],
     *     "links": {"first": "https://moboeats.com/api/v1/orphanages?page=1", "last": "https://moboeats.com/api/v1/orphanages?page=1", "prev": null, "next": null},
     *     "meta": {"current_page": 1, "from": 1, "last_page": 1, "links": [], "path": "https://moboeats.com/api/v1/orphanages", "per_page": 15, "to": 1, "total": 1}
     *   }
     * }
     * @responseField data.data object[] The orphanages.
     * @responseField data.meta object Pagination metadata.
     */
    public function index(Request $request)
    {
        $per_page = $request->query('per_page');

        if (!auth()->check()) {
            $data = Orphanage::approved()->paginate($per_page);
        } else {
            if (auth()->user()->hasRole('orderer') || auth()->user()->hasRole('restaurant') || auth()->user()->hasRole('restaurant employee')) {
                $data = Orphanage::approved()->paginate($per_page);
            } else {
                $data = Orphanage::paginate($per_page);
            }
        }

        $data = OrphanageResource::collection($data)->response()->getData();

        return $this->success($data);
    }

    /**
     * Register an orphanage
     *
     * Registers an orphanage. Requires authentication on `v1/orphanages/store` and the admin routes. The orphanage is
     * `approved` immediately when the user has the `create orphanages` permission, otherwise `pending`. Requests that do
     * not expect JSON receive an HTML confirmation page instead.
     *
     * @bodyParam name string required Orphanage name. Example: Hope Children Home
     * @bodyParam email string Orphanage email. Required if `phone_number` is not given. Example: info@hopehome.org
     * @bodyParam phone_number string Orphanage phone. Required if `email` is not given. Example: 254711000000
     * @bodyParam contact_name string required Contact person's name. Example: Mary Wanjiku
     * @bodyParam contact_email string Contact person's email. Required if `contact_phone_number` is not given. Example: mary@hopehome.org
     * @bodyParam contact_phone_number string Contact person's phone. Required if `contact_email` is not given. Example: 254711000111
     * @bodyParam location string required Location description. Example: Kibera, Nairobi
     * @bodyParam location_lat string required Latitude. Example: -1.3133
     * @bodyParam location_long string required Longitude. Example: 36.7876
     * @bodyParam logo file required Logo image (jpg or png, max 5 MB).
     *
     * @response 200 scenario="Created" {"status": "Request was succesful", "message": null, "data": {"id": 1, "name": "Hope Children Home", "email": "info@hopehome.org", "phone_number": "254711000000", "contact_name": "Mary Wanjiku", "contact_email": "mary@hopehome.org", "contact_phone_number": "254711000111", "location": "Kibera, Nairobi", "location_lat": "-1.3133", "location_long": "36.7876", "logo": "hope.png", "status": "approved", "rejection_reason": null, "created_by": 1, "created_at": "2024-12-01T10:00:00.000000Z", "updated_at": "2024-12-01T10:00:00.000000Z"}}
     * @response 200 scenario="Non-JSON request (HTML page)" <html><body>Orphanage added</body></html>
     * @response 422 scenario="Validation error" {"status": "Error has occured...", "message": "Invalid Data", "data": {"name": ["The name field is required."]}}
     * @responseField data object The created orphanage (raw model).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'email' => ['required_without:phone_number', 'email'],
            'phone_number' => ['required_without:email'],
            'contact_name' => ['required'],
            'contact_email' => ['required_without:contact_phone_number'],
            'contact_phone_number' => ['required_without:contact_email'],
            'location' => ['required'],
            'location_lat' => ['required'],
            'location_long' => ['required'],
            'logo' => ['required', 'mimes:jpg,png', 'max:5000']
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Invalid Data', 422);
        }

        $orphanage = Orphanage::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'contact_name' => $request->contact_name,
            'contact_email' => $request->contact_email,
            'contact_phone_number' => $request->contact_phone_number,
            'location' => $request->location,
            'location_lat' => $request->location_lat,
            'location_long' => $request->location_long,
            'logo' => $request->hasFile('logo') ? pathinfo($request->logo->store('logo', 'orphanages'), PATHINFO_BASENAME) : NULL,
            'status' => auth()->check() && auth()->user()->hasPermission('create orphanages') ? 'approved' : 'pending',
            'created_by' => auth()->check() ? auth()->id() : NULL
        ]);

        if ($request->wantsJson()) {
            return $this->success($orphanage);
        }

        return view('orphanage-added', ['orpanage' => $orphanage]);
    }

    /**
     * Get an orphanage
     *
     * Returns an orphanage with its donated orders and their users.
     *
     * @urlParam orphanage integer required The numeric ID of the orphanage. Example: 1
     *
     * @response 200 {"status": "Request was succesful", "message": null, "data": {"id": 1, "name": "Hope Children Home", "email": "info@hopehome.org", "contact_user_name": "Mary Wanjiku", "contact_user_email": "mary@hopehome.org", "contact_user_phone_number": "254711000111", "logo": "https://moboeats.com/storage/orphanages/logo/hope.png", "status": "Approved", "created_by": null, "location": "Kibera, Nairobi", "latitude": -1.3133, "longitude": 36.7876, "orders": []}}
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\Orphanage] 99"}
     * @responseField data.orders object[] Orders donated to this orphanage.
     */
    public function show(Orphanage $orphanage)
    {
        $orphanage->load('orders.user');

        return $this->success(new OrphanageResource($orphanage));
    }

    /**
     * Update an orphanage
     *
     * Updates an orphanage. Requires the `edit orphanages` permission. Status becomes `approved` when the user has
     * role `admin`, otherwise it is reset to `pending`.
     *
     * @urlParam orphanage integer required The numeric ID of the orphanage. Example: 1
     * @bodyParam name string required Orphanage name. Example: Hope Children Home
     * @bodyParam email string Orphanage email. Required if `phone_number` is not given. Example: info@hopehome.org
     * @bodyParam phone_number string Orphanage phone. Required if `email` is not given. Example: 254711000000
     * @bodyParam contact_name string Contact person's name. Example: Mary Wanjiku
     * @bodyParam contact_email string Contact person's email. Required if `contact_phone_number` is not given. Example: mary@hopehome.org
     * @bodyParam contact_phone_number string Contact person's phone. Required if `contact_email` is not given. Example: 254711000111
     * @bodyParam location string required Location description. Example: Kibera, Nairobi
     * @bodyParam location_lat string required Latitude. Example: -1.3133
     * @bodyParam location_long string required Longitude. Example: 36.7876
     * @bodyParam logo file Logo image (jpg or png, max 5 MB).
     *
     * @response 200 scenario="Updated" {"status": "Request was succesful", "message": "Orphanage updated successfully", "data": {"id": 1, "name": "Hope Children Home", "email": "info@hopehome.org", "phone_number": "254711000000", "contact_name": "Mary Wanjiku", "contact_email": "mary@hopehome.org", "contact_phone_number": "254711000111", "location": "Kibera, Nairobi", "location_lat": "-1.3133", "location_long": "36.7876", "logo": "hope.png", "status": "approved", "rejection_reason": null, "created_by": 1, "created_at": "2024-12-01T10:00:00.000000Z", "updated_at": "2024-12-01T10:00:00.000000Z"}}
     * @response 401 scenario="Missing permission" {"status": "Error has occured...", "message": "You are not authorized to perform this action", "data": ""}
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\Orphanage] 99"}
     * @response 422 scenario="Validation error" {"status": "Error has occured...", "message": "Invalid Data", "data": {"name": ["The name field is required."]}}
     * @responseField data object The updated orphanage (raw model).
     */
    public function update(Request $request, Orphanage $orphanage)
    {
        if (!auth()->user()->hasPermission('edit orphanages')) {
            return $this->error('', 'You are not authorized to perform this action', 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'email' => ['required_without:phone_number', 'email'],
            'phone_number' => ['required_without:email'],
            'contact_email' => ['required_without:contact_phone_number'],
            'contact_phone_number' => ['required_without:contact_email'],
            'location' => ['required'],
            'location_lat' => ['required'],
            'location_long' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Invalid Data', 422);
        }

        $orphanage->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'contact_name' => $request->contact_name,
            'contact_email' => $request->contact_email,
            'contact_phone_number' => $request->contact_phone_number,
            'location' => $request->has('location') && !empty($request->location) ? $request->location : $orphanage->location,
            'location_lat' => $request->has('location_lat') && !empty($request->location_lat) ? $request->location_lat : $orphanage->location_lat,
            'location_long' => $request->has('location_long') && !empty($request->location_long) ? $request->location_long : $orphanage->location_long,
            'status' => auth()->check() && auth()->user()->hasRole('admin') ? 'approved' : 'pending',
            'logo' => $request->hasFile('logo') ? pathinfo($request->logo->store('logo', 'orphanages'), PATHINFO_BASENAME) : $orphanage->logo,
        ]);

        return $this->success($orphanage, 'Orphanage updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Orphanage $orphanage)
    {
        if ($orphanage->orders->count() > 0) {
            return $this->error('', 'Orphanage has orders', 401);
        }

        $orphanage->delete();

        return $this->success('', 'Orphanage Deleted Successfully');
    }

    /**
     * Update orphanage status
     *
     * Admin endpoint. Approves or rejects an orphanage. Requires the `edit orphanages` permission.
     *
     * @urlParam orphanage integer required The numeric ID of the orphanage. Example: 1
     * @bodyParam status string New status. One of: `pending`, `approved`, `rejected`. Example: rejected
     * @bodyParam rejection_reason string Reason for rejection. Example: Incomplete registration details.
     *
     * @response 200 scenario="Updated" {"status": "Request was succesful", "message": "Updated Orhpanage successfully", "data": {"id": 1, "name": "Hope Children Home", "email": "info@hopehome.org", "phone_number": "254711000000", "contact_name": "Mary Wanjiku", "contact_email": "mary@hopehome.org", "contact_phone_number": "254711000111", "location": "Kibera, Nairobi", "location_lat": "-1.3133", "location_long": "36.7876", "logo": "hope.png", "status": "rejected", "rejection_reason": "Incomplete registration details.", "created_by": 1, "created_at": "2024-12-01T10:00:00.000000Z", "updated_at": "2024-12-01T10:00:00.000000Z"}}
     * @response 401 scenario="Missing permission" {"status": "Error has occured...", "message": "You cannot perform this action", "data": ""}
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\Orphanage] 99"}
     * @responseField data.status string The new status.
     */
    public function updateStatus(Orphanage $orphanage, Request $request)
    {
        if (!auth()->user()->hasPermission('edit orphanages')) {
            return $this->error('', 'You cannot perform this action', 401);
        }

        $orphanage->update([
            'status' => $request->status,
            'rejection_reason' => $request->rejection_reason
        ]);

        return $this->success($orphanage, 'Updated Orhpanage successfully');
    }
}
