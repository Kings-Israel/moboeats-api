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
use App\Models\ReferralCode;
use App\Models\Otp;
use App\Models\UserRestaurant;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Jobs\SendCommunication;
use App\Jobs\SendSMS;
use App\Helpers\NumberGenerator;

/**
 * @group Authentication Management
 *
 * User API resource
 */
class AuthController extends Controller
{
    use HttpResponses;

    /**
     * Login through email and password
     *
     * @bodyParam email string required The email of the user
     * @bodyParam password string required The password of the user
     * @bodyParam userType string required The type of user
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

            if ($request->userType == 'rider') {
                $rider = $user->rider;
                // if (!$rider) {
                //     return $this->error('Rider profile not complete', 'Complete your rider profile.', 400);
                // }

                if ($rider && $rider->status == 1) {
                    return $this->error('Rider Profile', 'Rider profile awaiting approval. Contact Admin for Assistance.', 403);
                }
            }

            $token = $user->createToken($request->userType, ['create', 'read', 'update', 'delete']);

            $user->update(['role_id' => $request->userType]);

            $user = new UserResource($user);

            $country = '';
            $country_code = '';
            if ($user->hasRole('restaurant employee')) {
                $user_restaurant = UserRestaurant::where('user_id', $user->id)->first();
                $restaurant = Restaurant::where('id', $user_restaurant->restaurant_id)->first();
                $country = $restaurant->country;
                $country_code = $restaurant->country_code;
            }

            if ($user->hasRole('restaurant')) {
                $restaurant = Restaurant::where('user_id', $user->id)->first();
                $country = $restaurant->country;
                $country_code = $restaurant->country_code;
            }

            return $this->success([
                'user' => $user,
                'token' => $token->plainTextToken,
                'role' => $request->userType,
                'restaurants' => $request->userType == 'restaurant' ? $user->restaurants : NULL,
                'restaurant' => $request->userType == 'restaurant employee' ? $restaurant : NULL,
                'country' => $country,
                'country_code' => $country_code,
            ]);
        } catch (\Throwable $th) {
            info($th->getMessage());
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * Registration
     * @bodyParam email string required The email of the user
     * @bodyParam password string required The password of the user
     * @bodyParam userType string required The type of user
     * @bodyParam phone string required The type of user
     * @bodyParam latitude string optional The latitude of the user. Example -1.2847473
     * @bodyParam longitude string optional The longitude of the user. Example 36.3878745
     */
    public function register(StoreUserRequest $request)
    {
        $request->validated($request->all());

        try {
            DB::beginTransaction();
            $user = User::firstOrCreate(
                [
                    'email' => $request->email,
                    'phone_number' => $request->phone
                ],
                [
                    'name' => $request->name,
                    'password' => Hash::make($request->password),
                    'user_type' => $request->userType,
                    'status' => 2,
                    'device_token' => $request->has('device_token') && $request->device_token != '' ? $request->device_token : NULL,
                    'image' => $request->hasFile('image') ? pathinfo($request->image->store('avatar', 'user'), PATHINFO_BASENAME) : NULL,
                ]
            );

            if ($request->userType === 'orderer') {
                $orderer = Orderer::firstOrCreate(
                    [
                        'phone_no' => $request->phone,
                    ],
                    [
                        'user_id' => $user->id,
                        'name' => $request->name,
                        'email' => $request->email,
                        'address' => '',
                        'status' => 2,
                        // 'latitude' => $request->latitude ?? NULL,
                        // 'longitude' => $request->longitude ?? NULL,
                    ]);
                $role = Role::where('name', $request->userType)->first();
                if (!$role) {
                    return $this->error('', 'Unknown user type: ' .$request->userType, 401);
                }
                $user->addRole($request->userType);
                // $token = $user->createToken($request->userType, ['create', 'update', 'delete']);

                // $code = NumberGenerator::generateVerificationCode(Otp::class, 'code');

                // Otp::create([
                //     'phone_number' => $user->phone_number,
                //     'code' => $code,
                // ]);

                // SendCommunication::dispatchAfterResponse('sms', 'SendSMS', $user->phone_number, ['code' => $code]);
            }

            if ($request->userType === 'restaurant') {
                $role = Role::where('name', $request->userType)->first();
                if (!$role) {
                    return $this->error('', 'Unknown user type: '.$request->userType, 401);
                }
                $user->addRole($request->userType);
                // $token = $user->createToken($request->userType, ['create', 'update', 'delete']);

                $user->update([
                    'type' => $request->type
                ]);
            }

            if ($request->userType === 'rider') {
                $rider = Rider::firstOrCreate(
                    [
                        'phone_no' => $request->phone,
                    ],
                    [
                        'user_id' => $user->id,
                        'name' => $request->name,
                        'email' => $request->email,
                        'status' => 2,
                        'address' => $request->address ?? NULL,
                        'city' => $request->city ?? NULL,
                        'state' => $request->state ?? NULL,
                        'postal_code' => $request->postal_code ?? NULL,
                        'vehicle_type' => $request->hicle_type ?? NULL,
                        'vehicle_license_plate' => $request->vehicle_license_plate ?? NULL,
                        'paypal_email' => $request->paypal_email ?? NULL,
                    ]);

                $role = Role::where('name', $request->userType)->first();

                if (!$role) {
                    return $this->error('', 'Unknown user type: '.$request->userType, 401);
                }

                $user->addRole($request->userType);
            }

            $token = $user->createToken($request->userType, ['create', 'update', 'delete']);

            $user->update(['role_id' => $request->userType]);

            activity()->causedBy($user)->log('registered a new account as '.$request->userType);

            if ($request->has('referral_code') && !empty($request->referral_code)) {
                $code = ReferralCode::where('referral_code', $request->referral_code)->first();

                if ($code) {
                    $code->update([
                        'uses' => $code->uses + 1,
                    ]);
                }
            }

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

    /**
     * Logout
     */
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

    /**
     * Forgot Password
     *
     * @bodyParam email The email of the user
     * @bodyParam phone_number The phone number of the user
     */
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

        if ($request->has('email') && !empty($request->email)) {
            SendCommunication::dispatchAfterResponse('mail', $request->email, 'ResetPassword', ['code' => $token]);
        } else {
            SendSMS::dispatchAfterResponse($request->phone_number, 'Your password reset token is: '.$token);
        }

        return $this->success('', 'Password reset code sent successfully');
    }

    /**
     * Reset password
     *
     * @bodyParam code The Verification OTP code
     * @bodyParam password The new password to be set
     * @bodyParam password_confirmation The new password confirmation
     */
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

    /**
     * Get the authenticated user
     */
    public function authUser()
    {
        return $this->success([
            'user' =>  Auth::user()
        ]);
    }

    /**
     * Login through OTP
     *
     * @bodyParam phone_number string required The phone number of the user. Example 44374673827
     * @bodyParam device_token string optional The device token
     * @bodyParam latitude string optional The latitude of the user. Example -1.2847473
     * @bodyParam longitude string optional The longitude of the user. Example 36.3878745
     */
    public function otpLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->error('Authentication', 'Phone number is required', 400);
        }

        try {
            DB::beginTransaction();

            $user = User::firstOrCreate(
                [
                    'phone_number' => $request->phone_number,
                ],
                [
                    'password' => Hash::make($request->phone_number),
                    'user_type' => $request->userType,
                    'status' => 2,
                    'device_token' => $request->has('device_token') && $request->device_token != '' ? $request->device_token : NULL,
                    'latitude' => $request->latitude ?? NULL,
                    'longitude' => $request->longitude ?? NULL,
                ]
            );

            if($user->status == 1) {
                return $this->error('', 'Oops! Your account has been deleted or deactivated', 401);
            }

            // $token = $user->createToken($request->userType, ['create', 'update', 'delete']);

            $code = NumberGenerator::generateVerificationCode(Otp::class, 'code');

            Otp::create([
                'phone_number' => $user->phone_number,
                'code' => $code,
            ]);

            SendSMS::dispatchAfterResponse($user->phone_number, 'Your verification code is: '.$code);

            DB::commit();

            $user = new UserResource($user);

            return $this->success([
                'user' => $user,
                // 'token' => $token->plainTextToken,
                'code' => $code
            ]);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * Verify OTP
     *
     * @bodyParam code string required The phone number of the user
     * @bodyParam userType string required The type of user logging in
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required'],
            'userType' => ['required', 'in:orderer,restaurant,rider']
        ]);

        if ($validator->fails()) {
            return $this->error('Authentication', [$validator->messages()->toArray()], 400);
        }

        $code = Otp::where('code', $request->code)->first();

        if (!$code) {
            return $this->error('Authentication', 'Invalid Code', 400);
        }

        $user = User::where('phone_number', $code->phone_number)->first();

        // Update device token
        if ($request->has('device_token') && $request->device_token != '') {
            $user->update([
                'device_token' => $request->device_token
            ]);
        }

        if ($request->userType == 'rider') {
            $rider = $user->rider;
            if (!$rider) {
                return $this->error('Rider profile not complete', 'Complete your rider profile.', 400);
            }

            if ($rider->status == 1) {
                return $this->success(['Rider profile awaiting approval'], 403);
            }
        }
        info($user->hasRole('restaurant') ? 'true' : 'false');
        $token = $user->createToken($request->userType, ['create', 'read', 'update', 'delete']);

        $user->update(['role_id' => $request->userType]);

        $user = new UserResource($user);

        $country = '';
        $country_code = '';
        if ($user->hasRole('restaurant employee')) {
            $user_restaurant = UserRestaurant::where('user_id', $user->id)->first();
            $restaurant = Restaurant::where('id', $user_restaurant->restaurant_id)->first();
            $country = $restaurant->country;
            $country_code = $restaurant->country_code;
        }

        if ($user->hasRole('restaurant')) {
            $restaurant = Restaurant::where('user_id', $user->id)->first();
            $country = $restaurant->country;
            $country_code = $restaurant->country_code;
        }

        return $this->success([
            'user' => $user,
            'token' => $token->plainTextToken,
            'role' => $request->userType,
            'restaurants' => $request->userType == 'restaurant' ? $user->restaurants : NULL,
            'restaurant' => $request->userType == 'restaurant employee' ? $restaurant : NULL,
            'country' => $country,
            'country_code' => $country_code,
        ]);
    }

    /**
     * Delete account
     */
    public function delete()
    {
        return view('delete');
    }

    public function confirmDelete(Request $request)
    {
        $request->validate([
            'name' => ['required'],
            'email' => ['required_without:phone_number', 'email'],
            'phone_number' => ['required_without:email']
        ]);

        $user = User::where('email', $request->email)->orWhere('phone_number', $request->phone_number)->first();

        if ($user) {
            $user->delete();

            return view('deleted');
        }

        return back()->withErrors(['email' => 'Invalid user details']);
    }
}
