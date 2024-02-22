<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LoginUserRequest;
use App\Http\Requests\V1\StoreUserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\Orderer;
use App\Models\Restaurant;
use App\Models\Rider;
use App\Models\Role;
use App\Models\User;
use App\Models\Otp;
use App\Models\UserRestaurant;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Jobs\SendCommunication;
use App\Helpers\NumberGenerator;

class AuthController extends Controller
{
    use HttpResponses;

    /**
     * @group Authentication Management
     *
     * User API resource
     */

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
                'userType' => ['required', 'string', Rule::in(['orderer', 'restaurant', 'rider', 'restaurant employee'])],
            ], [
                'userType.in' => 'Please select a orderer, restaurant or rider for the user type'
            ]);

            if(!Auth::attempt($request->only(['email', 'password']))){
                return $this->error('', 'Unauthorized', 401);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user->hasRole($request->userType)) {
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

            $token = $user->createToken($request->userType, ['create', 'read', 'update', 'delete']);

            $user->update(['role_id' => $request->userType]);

            $user = new UserResource($user);

            if ($user->hasRole('restaurant employee')) {
                $user_restaurant = UserRestaurant::where('user_id', $user->id)->first();
                $restaurant = Restaurant::where('id', $user_restaurant->restaurant_id)->first();
            }

            return $this->success([
                'user' => $user,
                'token' => $token->plainTextToken,
                'role' => $request->userType,
                'restaurants' => $request->userType == 'restaurant' ? $user->restaurants : NULL,
                'restaurant' => $request->userType == 'restaurant employee' ? $restaurant : NULL,
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
                'image' => $request->hasFile('image') ? pathinfo($request->image->store('avatar', 'user'), PATHINFO_BASENAME) : NULL,
                'phone_number' => $request->has('phone_no') && !empty($request->phone_no) ? $request->phone_no : NULL
            ]);

            if ($request->user_type === 'orderer') {
                $orderer = Orderer::create([
                    'user_id' => $user->id,
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone_no' => $request->phone_no ?? NULL,
                    'address' => '',
                    'status' => 2,
                ]);
                $role = Role::where('name', $request->user_type)->first();
                if (!$role) {
                    return $this->error('', 'Unknown user type: ' .$request->user_type, 401);
                }
                $user->addRole($request->user_type);
                $token = $user->createToken($request->user_type, ['create', 'update', 'delete']);

                $code = NumberGenerator::generateVerificationCode(Otp::class, 'code');

                Otp::create([
                    'phone_number' => $user->phone_number,
                    'code' => $code,
                ]);

                SendCommunication::dispatchAfterResponse('sms', 'SendSMS', $user->phone_number, ['code' => $code]);
            }
            if ($request->user_type === 'restaurant') {
                $role = Role::where('name', $request->user_type)->first();
                if (!$role) {
                    return $this->error('', 'Unknown user type: '.$request->user_type, 401);
                }
                $user->addRole($request->user_type);
                $token = $user->createToken($request->user_type, ['create', 'update', 'delete']);

                $user->update([
                    'type' => $request->type
                ]);
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

            activity()->causedBy($user)->log('registered a new account');

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

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required_without:phone_number'],
            'phone_number' => ['required_without:email']
        ]);

        if ($validator->fails()) {
            return $this->error('Auth Error', $validator->messages(), 403);
        }

        $user = User::when($request->has('email') && !empty($request->email), fn ($query) => $query->where('email', $request->email))->when($request->has('phone_number') && !empty($request->phone_number), fn ($query) => $query->where('phone_number', $request->phone_number))->first();

        if (!$user) {
            if ($request->has('email') && !empty($request->email)) {
                return $this->error('Auth Error', 'User with email'.$request->email.' was not found', 404);
            } else {
                return $this->error('Auth Error', 'User with phone number'.$request->phone_number.' was not found', 404);
            }
        }

        // Generate token
        $token = rand(000000, 999999);

        $email = $request->has('email') && !empty($request->email) ? $request->email : $request->phone_number;

        $user = DB::table('password_reset_tokens')->where('email', $email)->first();

        if ($user) {
            $token = $user->token;
        } else {
            DB::table('password_reset_tokens')
                ->insert([
                    'email' => $request->has('email') && !empty($request->email) ? $request->email : $request->phone_number,
                    'token' => $token
                ]);
        }

        SendCommunication::dispatchAfterResponse('mail', $request->email, 'ResetPassword', ['code' => $token]);

        return $this->success('', 'Password reset code sent successfully');
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required'],
            'password' => ['required', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->error('Reset Password Error', $validator->messages(), 403);
        }

        $token = DB::table('password_reset_tokens')->where('token', $request->code)->first();

        if (!$token) {
            return $this->error('Password Reset Error', 'Invalid Credentials', 403);
        }

        $user = User::where('email', $token->email)->orWhere('phone_number', $token->email)->first();

        $user->update([
            'password' => bcrypt($request->password)
        ]);

        DB::table('password_reset_tokens')->where('token', $request->code)->delete();

        return $this->success('', 'Password was reset successfully.');
    }

    public function authUser()
    {
        return $this->success([
            'user' =>  Auth::user()
        ]);
    }

}
