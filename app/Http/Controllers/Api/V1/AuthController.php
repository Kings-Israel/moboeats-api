<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LoginUserRequest;
use App\Http\Requests\V1\StoreUserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use HttpResponses;

    public function login(LoginUserRequest $request)
    {
        $request->validated($request->all());

        if(!Auth::attempt($request->only(['email', 'password']))){
            return $this->error('', 'Unauthorized', 401);
        }

        $user = User::where('email', $request->email)->first();
        $adminToken = $user->createToken('admin', ['create', 'update', 'delete']);
        $updateToken = $user->createToken('update', ['create', 'update']);
        $basic = $user->createToken('basic',['none']);


        $user = new UserResource($user);
        return $this->success([
            'user' =>$user,
            'admin-token' => $adminToken->plainTextToken,
            'update-token' => $updateToken->plainTextToken,
            'basic-token' => $basic->plainTextToken,
        ]);

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
            ]);
            DB::commit();

            $adminToken = $user->createToken('admin', ['create', 'update', 'delete']);
            $updateToken = $user->createToken('update', ['create', 'update']);
            $basic = $user->createToken('basic', ['none']);

            $user = new UserResource($user);

            return $this->success([
                'user' => $user,
                'token' => $adminToken->plainTextToken,
            ]);

            return new UserResource($user);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
        }
    }

    public function logout()
    {
        // Auth::user()->currentAccessToken()->delete();
        Auth::user()->tokens->each(function($token, $key) {
            $token->delete();
        });
        return $this->success([
            'message' => 'Successfully logged out.'
        ]);
    }

}
