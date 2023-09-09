<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\V1\RiderFilter;
use App\Models\Rider;
use App\Http\Requests\V1\StoreRiderRequest;
use App\Http\Requests\V1\UpdateRiderRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RiderCollection;
use App\Http\Resources\V1\RiderResource;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Traits\Admin\UploadFileTrait;

/**
 * @group Rider Management
 * 
 * Rider API resource
 */
class RiderController extends Controller
{
    use HttpResponses;
    use UploadFileTrait;
    
    public $settings = [
        'model' =>  '\\App\\Models\\Rider',
        'caption' =>  "Rider",
        'storageName' =>  "riders",
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'restaurant') {
                $filter =  new RiderFilter();
                $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
                $riders = Rider::where('status', 2)
                ->where($filterItems)
                ->with(['user'])
                ->paginate();
                return new RiderCollection($riders);
            }
            

        } else {return $this->error('', 'Unauthorized', 401); }
    }

    
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRiderRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     * Use uuid to get the specified resource.
     */
    public function show(Rider $rider)
    {
        // if ($this->isNotAuthorized($rider)) {
        //     return new RiderResource($rider->loadMissing(['user']));
        // } else {
        //     return $this->isNotAuthorized($rider);
        // }
        return new RiderResource($rider->loadMissing(['user']));
    }

    
    /**
     * Update the specified resource in storage.
     * Use uuid as identifier.
     */
    public function update(UpdateRiderRequest $request, Rider $rider)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'rider') {
                return $this->error('', 'Unauthorized', 401);
            }
            try {
                DB::beginTransaction();
                if (!$this->isNotAuthorized($rider)) {
                    return $this->isNotAuthorized($rider);
                }
                if($request->hasFile('profile_picture')){
                    $fileName = $this->generateFileName2($request->file('profile_picture'));
                    $rider->update($request->all(),['profile_picture' => $fileName]);
                    if($request->hasFile('profile_picture')){
                        $fileData = ['file' => $request->file('profile_picture'),'fileName' => $fileName, 'storageName' => $this->settings['storageName'].'\\images','prevFile' => null];
                        if(!$this->uploadFile($fileData)){
                            DB::rollBack();
                        }
                    }
                } else {
                    $rider->update($request->all());
                }
                
                DB::commit();
                return new RiderResource($rider);
            } catch (\Throwable $th) {
                //throw $th;
            }
        } else {
            return $this->error('', 'Unauthorized', 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Use uuid as identifier..
     */
    public function destroy(Rider $rider)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role != 'rider') {
                return $this->error('', 'Unauthorized', 401);
            }
            try {
                DB::beginTransaction();
                if ($this->isNotAuthorized($rider)) {
                    $rider->delete();
                } else {
                    return $this->isNotAuthorized($rider);
                }
                DB::commit();
                return response(null, 204);
            } catch (\Throwable $th) {
                info($th);
                DB::rollBack();
                return $this->error('', $th->getMessage(), 403);
            }
        }
    }

    public function isNotAuthorized($rider)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'rider') {
                if (Auth::user()->id == $rider->user_id) {
                    return true;
                } else {
                    return $this->error('', 'You are not authorized to make this request', 403);
                }
            } else {
                return false;
                if (Auth::user()->id != $rider->user_id) {
                    return $this->error('', 'You are not authorized to make this request', 403);
                }
            }
        }
    }
}
