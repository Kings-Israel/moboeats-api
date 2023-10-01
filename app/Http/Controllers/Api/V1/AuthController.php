<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LoginUserRequest;
use App\Http\Requests\V1\StoreUserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\Orderer;
use App\Models\Rider;
use App\Models\Role;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use HttpResponses;

    /**
     * @group Authentication Management
     *
     * User API resource
     */

    public function login(LoginUserRequest $request)
    {
        try {
            $request->validated($request->all());

            if(!Auth::attempt($request->only(['email', 'password']))){
                return $this->error('', 'Unauthorized', 401);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user->hasRole($request->user_type)) {
                return $this->error('', 'Unknown user type', 401);
            }
            if($user->status == 1) {
                return $this->error('', 'Oops! Your account has been deleted or deactivated', 401);
            }

            // Update device token
            if ($request->has('device_token') && $request->device_token != '') {
                $user->update([
                    'device_token' => $request->device_token
                ]);
            }

            $token = $user->createToken($request->user_type, ['create', 'read', 'update', 'delete']);

            $user->update(['role_id' => $request->user_type]);

            $user = new UserResource($user);

            return $this->success([
                'user' => $user,
                'token' => $token->plainTextToken,
                // 'tokens' => [
                //     'admin' => $adminToken->plainTextToken,
                //     'update' => $updateToken->plainTextToken,
                //     'basic' => $basic->plainTextToken
                // ],

            ]);
        } catch (\Throwable $th) {
            info($th->getMessage());
            return $this->error('', $th->getMessage(), 403);
        }
    }

    public function register(StoreUserRequest $request)
    {
        $request->validated($request->all());

        try {
            DB::beginTransaction();
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'user_type' => $request->user_type,
                'status' => 2,
                'device_token' => $request->has('device_token') && $request->device_token != '' ? $request->device_token : NULL,
            ]);

            if ($request->user_type === 'orderer') {
                $orderer = Orderer::create([
                    'user_id' => $user->id,
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone_no' => $request->phone_no??'',
                    'address' => '',
                    'status' => 2,
                ]);
                $role = Role::where('name', $request->user_type)->first();
                if (!$role) {
                    return $this->error('', 'Unknown user type: ' .$request->user_type, 401);
                }
                $user->addRole($request->user_type);
                $token = $user->createToken($request->user_type, ['create', 'update', 'delete']);
            }
            if ($request->user_type === 'restaurant') {
                $role = Role::where('name', $request->user_type)->first();
                if (!$role) {
                    return $this->error('', 'Unknown user type: '.$request->user_type, 401);
                }
                $user->addRole($request->user_type);
                $token = $user->createToken($request->user_type, ['create', 'update', 'delete']);
            }
            if ($request->user_type === 'rider') {
                $rider = Rider::create([
                    'user_id' => $user->id,
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone_no' => $request->phone_no??'',
                    'status' => 2,
                ]);
                $role = Role::where('name', $request->user_type)->first();
                if (!$role) {
                    return $this->error('', 'Unknown user type: '.$request->user_type, 401);
                }
                $user->addRole($request->user_type);
                $token = $user->createToken($request->user_type, ['create', 'update', 'delete']);
            }
            $user->update(['role_id' => $request->user_type]);
            DB::commit();

            $user = new UserResource($user);

            return $this->success([
                'user' => $user,
                'token' => $token->plainTextToken,
            ]);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    public function logout()
    {
        // Auth::user()->currentAccessToken()->delete();
        try {
            Auth::user()->tokens->each(function($token, $key) {
                $token->delete();
            });
            $user = User::where('id', Auth::user()->id)->first();
            $user->update(['role_id' => '']);
            return $this->success([
                'message' => 'Successfully logged out.'
            ]);
        } catch (\Throwable $th) {
            return $this->error('', $th->getMessage(), 403);
        }

    }

    public function authUser()
    {
        return $this->success([
            'user' =>  Auth::user()
        ]);
    }

}
