<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\V1\OrdererFilter;
use App\Http\Controllers\Controller;
use App\Models\Orderer;
use App\Http\Requests\V1\StoreOrdererRequest;
use App\Http\Requests\V1\UpdateOrdererRequest;
use App\Http\Resources\V1\OrdererCollection;
use App\Http\Resources\V1\OrdererResource;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\Admin\UploadFileTrait;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * @group Customer Profile
 *
 * Customer (orderer) profile management.
 */

class OrdererController extends Controller
{
    use UploadFileTrait;
    use HttpResponses;

    public $settings = [
        'model' =>  '\\App\\Models\\Orderer',
        'caption' =>  "Orderer",
        'storageName' =>  "orderers",
        'defaultPassword' =>  "12345678",//shoud be from database settings
    ];


    /**
     * List customers (orderers)
     *
     * Returns a paginated list of customer (orderer) profiles. Users whose active role is `orderer` are not allowed to call this.
     *
     * @queryParam uuid[eq] string Filter by orderer UUID. Example: 9b1f2c3d-0000-4a6b-8c7d-1e2f3a4b5c6d
     * @queryParam name[eq] string Filter by exact name. Example: Jane Doe
     * @queryParam email[eq] string Filter by exact email. Example: jane@example.com
     * @queryParam phoneNo[eq] string Filter by exact phone number. Example: 254712345678
     * @queryParam address[eq] string Filter by exact address. Example: Moi Avenue
     * @queryParam city[eq] string Filter by exact city. Example: Nairobi
     * @queryParam state[eq] string Filter by exact state. Example: Nairobi
     * @queryParam mapLocation[eq] string Filter by map location (also supports `mapLocation[gt]` and `mapLocation[lt]`). Example: -1.2847,36.8219
     * @queryParam image[eq] string Filter by image file name. Example: avatar.png
     * @queryParam status[eq] integer Filter by status (1 = deactivated, 2 = active). Example: 2
     * @queryParam page integer The page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "data": [{
     *       "id": 1,
     *       "uuid": "9b1f2c3d-0000-4a6b-8c7d-1e2f3a4b5c6d",
     *       "attributes": {
     *         "name": "Jane Doe",
     *         "email": "jane@example.com",
     *         "phoneNo": "254712345678",
     *         "address": "Moi Avenue",
     *         "city": "Nairobi",
     *         "state": "Nairobi",
     *         "mapLocation": "-1.2847,36.8219",
     *         "image": null,
     *         "status": "2"
     *       },
     *       "relationships": {
     *         "user": {"uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d", "name": "Jane Doe", "email": "jane@example.com"}
     *       }
     *     }
     *   ],
     *   "links": {"first": "http://localhost/api/v1/orderer/orderers?page=1", "last": "http://localhost/api/v1/orderer/orderers?page=1", "prev": null, "next": null},
     *   "meta": {"current_page": 1, "from": 1, "last_page": 1, "links": [{"url": null, "label": "&laquo; Previous", "active": false}, {"url": "http://localhost/api/v1/orderer/orderers?page=1", "label": "1", "active": true}, {"url": null, "label": "Next &raquo;", "active": false}], "path": "http://localhost/api/v1/orderer/orderers", "per_page": 15, "to": 1, "total": 1}
     * }
     * @response 401 scenario="Orderer role not allowed" {"status": "Error has occured...", "message": "Unauthorized", "data": ""}
     *
     * @responseField data object[] The orderer profiles (OrdererResource).
     * @responseField meta object Pagination metadata.
     */
    public function index(Request $request)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->role_id == 'orderer') {return $this->error('', 'Unauthorized', 401);}
        $filter =  new OrdererFilter();
        $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
        $orderers = Orderer::where($filterItems); //
        return new OrdererCollection($orderers->paginate()->appends($request->query()));
    }


    /**
     * Create a customer (orderer)
     *
     * Creates a user with the `orderer` role and default password `12345678`, plus the related orderer profile.
     *
     * @bodyParam name string required The customer name. Example: Jane Doe
     * @bodyParam email string required The customer email. Example: jane@example.com
     * @bodyParam phoneNo string The customer phone number. Example: 254712345678
     * @bodyParam address string The customer address. Example: Moi Avenue
     * @bodyParam city string The customer city. Example: Nairobi
     * @bodyParam state string The customer state. Example: Nairobi
     * @bodyParam mapLocation string The customer map location. Example: -1.2847,36.8219
     * @bodyParam image file An optional profile image.
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "orderer": {
     *       "id": 1,
     *       "uuid": "9b1f2c3d-0000-4a6b-8c7d-1e2f3a4b5c6d",
     *       "attributes": {
     *         "name": "Jane Doe",
     *         "email": "jane@example.com",
     *         "phoneNo": "254712345678",
     *         "address": "Moi Avenue",
     *         "city": "Nairobi",
     *         "state": "Nairobi",
     *         "mapLocation": "-1.2847,36.8219",
     *         "image": null,
     *         "status": "2"
     *       },
     *       "relationships": {
     *         "user": {"uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d", "name": "Jane Doe", "email": "jane@example.com"}
     *       }
     *     },
     *     "message": "Customer created successfully. The default password will be: 12345678"
     *   }
     * }
     * @response 403 scenario="Server error" {"status": "Error has occured...", "message": "Something went wrong", "data": ""}
     * @response 422 scenario="Validation error" {"message": "The email field is required.", "errors": {"email": ["The email field is required."]}}
     *
     * @responseField data.orderer object The created orderer profile (OrdererResource).
     * @responseField data.message string Confirmation message including the default password.
     */
    public function store(StoreOrdererRequest $request)
    {
        try {
            DB::beginTransaction();
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($this->settings['defaultPassword']),//it's supposed to come from user settings table of default password for customers
                'user_type' => 'orderer',
            ]);
            $user->addRole('orderer');
            $fileName= '';
            if($request->hasFile('image')){
                $fileName = $this->generateFileName2($request->file('image'));
            }
            $orderer = Orderer::create($request->all(),[
                'user_id' => $user->id,
                'image' => $fileName
            ]);
            if($request->hasFile('image')){
                $fileData = ['file' => $request->file('image'),'fileName' => $fileName, 'storageName' => $this->settings['storageName'].'\\images','prevFile' => null];
                if(!$this->uploadFile($fileData)){
                    DB::rollBack();
                }
            }
            DB::commit();
            return $this->success([
                'orderer' => new OrdererResource($orderer),
                'message' => 'Customer created successfully. The default password will be: ' . $this->settings['defaultPassword'] ,
            ]);
            return new OrdererResource($orderer);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }

    }

    /**
     * Get user details
     *
     * Returns the user details. Only the user themself may view their details.
     *
     * @urlParam orderer string required The UUID of the user. Example: 9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 5,
     *     "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d",
     *     "name": "Jane Doe",
     *     "email": "jane@example.com",
     *     "phone_number": "254712345678",
     *     "role": "orderer",
     *     "avatar": null,
     *     "relationships": {
     *       "orderer": {
     *         "id": 1,
     *         "uuid": "9b1f2c3d-0000-4a6b-8c7d-1e2f3a4b5c6d",
     *         "attributes": {
     *           "name": "Jane Doe",
     *           "email": "jane@example.com",
     *           "phoneNo": "254712345678",
     *           "address": "Moi Avenue",
     *           "city": "Nairobi",
     *           "state": "Nairobi",
     *           "mapLocation": "-1.2847,36.8219",
     *           "image": null,
     *           "status": "2"
     *         },
     *         "relationships": {
     *           "user": {"uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d", "name": "Jane Doe", "email": "jane@example.com"}
     *         }
     *       }
     *     },
     *     "type": null,
     *     "referral_code": null,
     *     "is_guided": false
     *   }
     * }
     * @response 403 scenario="Not authorized" {"status": "Error has occured...", "message": "You are not authorized to make this request", "data": ""}
     *
     * @responseField data object The user (UserResource).
     */
    public function show(User $user)
    {
        return $this->isNotAuthorized($user) ?  $this->isNotAuthorized($user) : new UserResource($user);
    }


    /**
     * Update user details
     *
     * Updates the details of the user identified by UUID. Any other user attributes sent in the body are also mass-assigned
     * to the user. An optional `image` upload is stored as the user image.
     *
     * @urlParam orderer string required The UUID of the user. Example: 9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d
     *
     * @bodyParam name string required The name of the user. Example: Jane Doe
     * @bodyParam email string The email of the user. Example: jane@example.com
     * @bodyParam phone_number string required The phone number of the user. Example: 254712345678
     * @bodyParam location string The location of the user. Example: Moi Avenue, Nairobi
     * @bodyParam latitude string The latitudinal location of the user. Example: -1.2847473
     * @bodyParam longitude string The longitudinal location of the user. Example: 36.8219
     * @bodyParam image file An optional profile image.
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 5,
     *     "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d",
     *     "name": "Jane Doe",
     *     "email": "jane@example.com",
     *     "phone_number": "254712345678",
     *     "role": "orderer",
     *     "avatar": null,
     *     "relationships": {
     *       "orderer": {
     *         "id": 1,
     *         "uuid": "9b1f2c3d-0000-4a6b-8c7d-1e2f3a4b5c6d",
     *         "attributes": {
     *           "name": "Jane Doe",
     *           "email": "jane@example.com",
     *           "phoneNo": "254712345678",
     *           "address": "Moi Avenue",
     *           "city": "Nairobi",
     *           "state": "Nairobi",
     *           "mapLocation": "-1.2847,36.8219",
     *           "image": null,
     *           "status": "2"
     *         },
     *         "relationships": {
     *           "user": {"uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d", "name": "Jane Doe", "email": "jane@example.com"}
     *         }
     *       }
     *     },
     *     "type": null,
     *     "referral_code": null,
     *     "is_guided": false
     *   }
     * }
     * @response 400 scenario="Validation error" {"name": ["The name field is required."], "phone_number": ["The phone number field is required."]}
     * @response 403 scenario="Error (e.g. user not found)" {"status": "Error has occured...", "message": "Call to a member function update() on null", "data": ""}
     *
     * @responseField data object The updated user (UserResource).
     */
    public function update(Request $request, $ordererId)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'email' => ['sometimes', 'email'],
            'phone_number' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        $user = User::where('uuid', $ordererId)->first();

        try {
            DB::beginTransaction();
            if($request->hasFile('image')){
                $fileName = $this->generateFileName2($request->file('image'));
                $user->update($request->all(),['image' => $fileName]);
                if($request->hasFile('image')){
                    $fileData = ['file' => $request->file('image'),'fileName' => $fileName, 'storageName' => $this->settings['storageName'].'\\images','prevFile' => null];
                    if(!$this->uploadFile($fileData)){
                        DB::rollBack();
                    }
                }
            } else {
                $user->update($request->all());
            }
            DB::commit();
            return new UserResource($user);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * Delete (deactivate) a customer account
     *
     * Deactivates the orderer profile and its user account (status set to 1) instead of deleting them.
     *
     * @urlParam orderer string required The UUID of the orderer profile. Example: 9b1f2c3d-0000-4a6b-8c7d-1e2f3a4b5c6d
     *
     * @response 200 scenario="Account deactivated" {"status": "Request was succesful", "message": "Account deleted successfully", "data": ""}
     * @response 403 scenario="Not authorized" {"status": "Error has occured...", "message": "You are not authorized to make this request", "data": ""}
     * @response 404 scenario="Orderer not found" {"message": "No query results for model [App\\Models\\Orderer] 9b1f2c3d-0000-4a6b-8c7d-1e2f3a4b5c6d"}
     */
    public function destroy(Orderer $orderer)
    {
        try {
            DB::beginTransaction();
            if ($this->isNotAuthorized($orderer)) {
                $user = User::find($orderer->user_id);
                $user->update([
                    'status' => 1,
                ]);
                $orderer->update(['status' => 1]);
                return $this->success('', 'Account deleted successfully');

            } else {
                return $this->error('', 'You are not authorized to make this request', 403);
            }
            // return $this->isNotAuthorized($orderer) ?  $this->isNotAuthorized($orderer) :  $orderer->update(['status' => 1]);
            DB::commit();
            // return response(null, 204);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * Store diet planning data
     *
     * Saves the authenticated user's body measurements used for diet planning.
     *
     * @group Diet Plans
     *
     * @bodyParam height number required The height of the user. Example: 170
     * @bodyParam height_units string The measurement units for height, e.g. `cm`, `inches`, `feet`. Required when `height` is present. Example: cm
     * @bodyParam weight number required The weight of the user. Example: 65
     * @bodyParam weight_units string The measurement units for weight, e.g. `kg`, `pounds`. Required when `weight` is present. Example: kg
     * @bodyParam body_mass_index number required The body mass index of the user. Example: 22.5
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": "Data updated successfully",
     *   "data": {
     *     "id": 5,
     *     "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d",
     *     "name": "Jane Doe",
     *     "email": "jane@example.com",
     *     "phone_number": "254712345678",
     *     "role_id": "orderer",
     *     "height": 170,
     *     "height_units": "cm",
     *     "weight": 65,
     *     "weight_units": "kg",
     *     "body_mass_index": 22.5,
     *     "created_at": "2024-01-01T10:00:00.000000Z",
     *     "updated_at": "2024-03-01T10:00:00.000000Z"
     *   }
     * }
     * @response 400 scenario="Validation error" {"status": "Error has occured...", "message": "Diet plan data", "data": {"height": ["The height field is required."]}}
     *
     * @responseField data object The updated authenticated user.
     */
    public function storeDietPlanUserData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'height' => ['required'],
            'height_units' => ['required_with:height'],
            'weight' => ['required'],
            'weight_units' => ['required_with:weight'],
            'body_mass_index' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Diet plan data', 400);
        }

        auth()->user()->update([
            'height' => $request->height,
            'height_units' => $request->height_units,
            'weight' => $request->weight,
            'weight_units' => $request->weight_units,
            'body_mass_index' => $request->body_mass_index,
        ]);

        return $this->success(auth()->user(), 'Data updated successfully');
    }

    public function isNotAuthorized($orderer)
    {
        if (Auth::user()->id !== $orderer->id) {
            return $this->error('', 'You are not authorized to make this request', 403);
        }
    }
}
