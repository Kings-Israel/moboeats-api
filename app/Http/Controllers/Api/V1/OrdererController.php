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
 * @group Orderer/Customer Management
 *
 * Orderer/Customer API resource
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
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
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
     * Get User Details.
     */
    public function show(User $user)
    {
        return $this->isNotAuthorized($user) ?  $this->isNotAuthorized($user) : new UserResource($user);
    }


    /**
     * @authenticated
     * Update User Details.
     *
     * @urlParam uuid string The uuid of the user
     *
     * @bodyParam name string required The name of the user
     * @bodyParam email string The email of the user
     * @bodyParam phone_number string required The phone number of the user
     * @bodyParam location string The location of the user
     * @bodyParam latitude string The latitudinal location of the user
     * @bodyParam longitude string The longitudinal location of the user
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
     * Remove the specified resource from storage.
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
     * Store user data for diet planning
     * @bodyParam height integer required The height of the user
     * @bodyParam height_units integer required The measurement units for height. E.g inches, feet
     * @bodyParam weight integer required The weight of the user
     * @bodyParam weight_units integer required The measurement units for weight. E.g kilograms, pounds
     * @bodyParam body_mass_index integer required The mass index of the user
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
