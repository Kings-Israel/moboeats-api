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
use App\Models\Scopes\UserCountryScope;

/**
 * @group Authentication
 *
 * Endpoints for registering, logging in (email/password or phone OTP), logging out,
 * resetting passwords and fetching the authenticated user.
 */
class AuthController extends Controller
{
    use HttpResponses;

    /**
     * Login with email and password
     *
     * Authenticates a user with email and password and issues a Sanctum API token for the given user type.
     * Logging in as `orderer` automatically adds the orderer role to the user if missing. The user's
     * `role_id` is set to the selected user type and the device token is updated when supplied.
     * Riders whose profile is awaiting approval are rejected.
     *
     * @bodyParam email string required The email of the user. Example: jane@example.com
     * @bodyParam password string required The password of the user. Example: secret123
     * @bodyParam userType string required The type of user logging in. One of `orderer`, `restaurant`, `rider`, `restaurant employee`. Example: orderer
     * @bodyParam device_token string The push notification (FCM) device token. Example: fcm-device-token-123
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d",
     *       "name": "Jane Doe",
     *       "email": "jane@example.com",
     *       "phone_number": "254712345678",
     *       "role": "orderer",
     *       "avatar": null,
     *       "relationships": {
     *         "orderer": {
     *           "id": 1,
     *           "uuid": "9b1f2c3d-0000-4a6b-8c7d-1e2f3a4b5c6d",
     *           "attributes": {
     *             "name": "Jane Doe",
     *             "email": "jane@example.com",
     *             "phoneNo": "254712345678",
     *             "address": "",
     *             "city": null,
     *             "state": null,
     *             "mapLocation": null,
     *             "image": null,
     *             "status": "2"
     *           },
     *           "relationships": {
     *             "user": {
     *               "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d",
     *               "name": "Jane Doe",
     *               "email": "jane@example.com"
     *             }
     *           }
     *         }
     *       },
     *       "type": null,
     *       "referral_code": null,
     *       "is_guided": false
     *     },
     *     "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ0123456789",
     *     "role": "orderer",
     *     "restaurants": null,
     *     "restaurant": null,
     *     "country": "",
     *     "country_code": ""
     *   }
     * }
     * @response 401 scenario="Invalid credentials" {"status": "Error has occured...", "message": "Unauthorized", "data": ""}
     * @response 401 scenario="Account deactivated" {"status": "Error has occured...", "message": "Oops! Your account has been deleted or deactivated", "data": ""}
     * @response 403 scenario="Rider awaiting approval" {"status": "Error has occured...", "message": "Rider profile awaiting approval. Contact Admin for Assistance.", "data": "Rider Profile"}
     * @response 403 scenario="Validation or other error" {"status": "Error has occured...", "message": "The email field is required.", "data": ""}
     *
     * @responseField data.user object The authenticated user (UserResource).
     * @responseField data.token string The Sanctum bearer token to use in the `Authorization` header.
     * @responseField data.role string The user type the token was issued for.
     * @responseField data.restaurants object[] The user's restaurants (only when `userType` is `restaurant`, otherwise null).
     * @responseField data.restaurant object The employer restaurant (only when `userType` is `restaurant employee`, otherwise null).
     * @responseField data.country string Country of the user's restaurant (restaurant owners/employees only).
     * @responseField data.country_code string Country code of the user's restaurant (restaurant owners/employees only).
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

            if($user->status == 1) {
                return $this->error('', 'Oops! Your account has been deleted or deactivated', 401);
            }

            if ($request->userType == 'orderer' && !$user->hasRole('orderer')) {
                $user->addRole($request->userType);
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
     * Register a new user
     *
     * Creates a user account (or reuses an existing one with the same email and phone), assigns the role
     * matching `userType`, creates the related orderer/rider profile, and returns a Sanctum token.
     * If a valid `referral_code` is supplied, its usage counter is incremented.
     *
     * @bodyParam name string required The full name of the user. Must not exceed 255 characters. Example: Jane Doe
     * @bodyParam email string required A unique email address. Example: jane@example.com
     * @bodyParam password string required The password (Laravel default password rules). Example: secret123
     * @bodyParam password_confirmation string required Must match `password`. Example: secret123
     * @bodyParam userType string required The type of user. One of `orderer`, `restaurant`, `rider`. Example: orderer
     * @bodyParam phone string required A unique phone number. Example: 254712345678
     * @bodyParam image file An optional avatar image.
     * @bodyParam otp string Optional OTP (currently unused). Example: 123456
     * @bodyParam device_token string The push notification (FCM) device token. Example: fcm-device-token-123
     * @bodyParam referral_code string A referral code used to sign up. Example: REF12345
     * @bodyParam type string Restaurant account type (only used when `userType` is `restaurant`). Example: restaurant
     * @bodyParam address string Rider address (only used when `userType` is `rider`). Example: 12 Moi Avenue
     * @bodyParam city string Rider city (only used when `userType` is `rider`). Example: Nairobi
     * @bodyParam state string Rider state/county (only used when `userType` is `rider`). Example: Nairobi
     * @bodyParam postal_code string Rider postal code (only used when `userType` is `rider`). Example: 00100
     * @bodyParam vehicle_license_plate string Rider vehicle license plate (only used when `userType` is `rider`). Example: KDA 123A
     * @bodyParam paypal_email string Rider PayPal email (only used when `userType` is `rider`). Example: rider@example.com
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d",
     *       "name": "Jane Doe",
     *       "email": "jane@example.com",
     *       "phone_number": "254712345678",
     *       "role": "orderer",
     *       "avatar": null,
     *       "relationships": {
     *         "orderer": {
     *           "id": 1,
     *           "uuid": "9b1f2c3d-0000-4a6b-8c7d-1e2f3a4b5c6d",
     *           "attributes": {
     *             "name": "Jane Doe",
     *             "email": "jane@example.com",
     *             "phoneNo": "254712345678",
     *             "address": "",
     *             "city": null,
     *             "state": null,
     *             "mapLocation": null,
     *             "image": null,
     *             "status": "2"
     *           },
     *           "relationships": {
     *             "user": {
     *               "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d",
     *               "name": "Jane Doe",
     *               "email": "jane@example.com"
     *             }
     *           }
     *         }
     *       },
     *       "type": null,
     *       "referral_code": null,
     *       "is_guided": false
     *     },
     *     "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ0123456789"
     *   }
     * }
     * @response 401 scenario="Unknown user type" {"status": "Error has occured...", "message": "Unknown user type: orderer", "data": ""}
     * @response 403 scenario="Server error" {"status": "Error has occured...", "message": "SQLSTATE[23000]: Integrity constraint violation", "data": ""}
     * @response 422 scenario="Validation error" {"message": "An account with the same email already exists", "errors": {"email": ["An account with the same email already exists"]}}
     *
     * @responseField data.user object The newly registered user (UserResource).
     * @responseField data.token string The Sanctum bearer token.
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
     *
     * Revokes all API tokens of the authenticated user and clears their active role.
     *
     * @response 200 scenario="Success" {"status": "Request was succesful", "message": null, "data": {"message": "Successfully logged out."}}
     * @response 403 scenario="Error" {"status": "Error has occured...", "message": "Something went wrong", "data": ""}
     *
     * @responseField data.message string Confirmation message.
     */
    public function logout()
    {
        try {
            Auth::user()->tokens->each(function($token, $key) {
                $token->delete();
            });
            $user = User::withoutGlobalScope(UserCountryScope::class)->where('id', Auth::user()->id)->first();
            $user->update(['role_id' => '']);
            return $this->success([
                'message' => 'Successfully logged out.'
            ]);
        } catch (\Throwable $th) {
            return $this->error('', $th->getMessage(), 403);
        }

    }

    /**
     * Request a password reset code
     *
     * Generates (or reuses) a 6-digit password reset code and sends it by email when `email` is given,
     * otherwise by SMS to `phone_number`. One of `email` or `phone_number` is required.
     *
     * @bodyParam email string The email of the user. Required if `phone_number` is not provided. Example: jane@example.com
     * @bodyParam phone_number string The phone number of the user. Required if `email` is not provided. Example: 254712345678
     *
     * @response 200 scenario="Code sent" {"status": "Request was succesful", "message": "Password reset code sent successfully", "data": ""}
     * @response 403 scenario="Validation error" {"status": "Error has occured...", "message": {"email": ["The email field is required when phone number is not present."], "phone_number": ["The phone number field is required when email is not present."]}, "data": "Auth Error"}
     * @response 404 scenario="User not found" {"status": "Error has occured...", "message": "User with emailjane@example.com was not found", "data": "Auth Error"}
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
     * Resets the user's password using the code received from the forgot-password endpoint. The code is consumed on success.
     *
     * @bodyParam code string required The password reset code. Example: 482913
     * @bodyParam password string required The new password. Example: newSecret123
     * @bodyParam password_confirmation string required The new password confirmation; must match `password`. Example: newSecret123
     *
     * @response 200 scenario="Password reset" {"status": "Request was succesful", "message": "Password was reset successfully.", "data": ""}
     * @response 403 scenario="Validation error" {"status": "Error has occured...", "message": {"code": ["The code field is required."]}, "data": "Reset Password Error"}
     * @response 403 scenario="Invalid code" {"status": "Error has occured...", "message": "Invalid Credentials", "data": "Password Reset Error"}
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
     *
     * Returns the raw authenticated user model (including appended rider/order statistics attributes).
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d",
     *       "name": "Jane Doe",
     *       "email": "jane@example.com",
     *       "phone_number": "254712345678",
     *       "user_type": "orderer",
     *       "role_id": "orderer",
     *       "status": 2,
     *       "latitude": null,
     *       "longitude": null,
     *       "device_token": "fcm-device-token-123",
     *       "image": null,
     *       "country": "Kenya",
     *       "country_code": "KE",
     *       "is_guided": false,
     *       "email_verified_at": null,
     *       "created_at": "2024-01-01T10:00:00.000000Z",
     *       "updated_at": "2024-01-01T10:00:00.000000Z",
     *       "total_rider_tips": 0,
     *       "rider_last_delivery": null,
     *       "total_rider_deliveries": 0,
     *       "amount_spent": 0,
     *       "latest_order": null,
     *       "county": null
     *     }
     *   }
     * }
     *
     * @responseField data.user object The authenticated user model.
     */
    public function authUser()
    {
        return $this->success([
            'user' =>  Auth::user()
        ]);
    }

    /**
     * Request an OTP login code
     *
     * Finds or creates a user with the given phone number, generates a one-time verification code and sends it by SMS.
     * When latitude/longitude are provided the user's country is resolved via Google geocoding. Complete the login
     * with the verify OTP endpoint.
     *
     * @bodyParam phone_number string required The phone number of the user. Example: 254712345678
     * @bodyParam userType string The user type stored on newly created users. One of `orderer`, `restaurant`, `rider`. Example: orderer
     * @bodyParam device_token string The push notification (FCM) device token. Example: fcm-device-token-123
     * @bodyParam latitude string The latitude of the user. Example: -1.2847473
     * @bodyParam longitude string The longitude of the user. Example: 36.3878745
     *
     * @response 200 scenario="Code sent" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d",
     *       "name": null,
     *       "email": null,
     *       "phone_number": "254712345678",
     *       "role": null,
     *       "avatar": null,
     *       "relationships": {
     *         "orderer": null
     *       },
     *       "type": null,
     *       "referral_code": null,
     *       "is_guided": false
     *     },
     *     "code": "482913"
     *   }
     * }
     * @response 400 scenario="Missing phone number" {"status": "Error has occured...", "message": "Phone number is required", "data": "Authentication"}
     * @response 401 scenario="Account deactivated" {"status": "Error has occured...", "message": "Oops! Your account has been deleted or deactivated", "data": ""}
     * @response 403 scenario="Server error" {"status": "Error has occured...", "message": "Something went wrong", "data": ""}
     *
     * @responseField data.user object The user (UserResource).
     * @responseField data.code string The generated OTP code (also sent by SMS).
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

            if ($request->latitude && $request->longitude) {
                try {
                    $user_location = Http::withOptions(['verify' => false])
                                            ->get('https://maps.googleapis.com/maps/api/geocode/json?latlng='.$request->latitude.','.$request->longitude.'&key='.config('services.map.key'));
                } catch (ConnectionException $e) {
                    $user_country = 'Kenya';
                    $user_short_country_name = 'KE';
                }

                if($user_location->failed() || $user_location->clientError() || $user_location->serverError()) {
                    $user_country = 'Kenya';
                    $user_short_country_name = 'KE';
                }

                foreach ($user_location['results'][0]['address_components'] as $place) {
                    if (collect($place['types'])->contains('country')) {
                        $user_country = $place['long_name'];
                    }

                    if (collect($place['types'])->contains('country')) {
                        $user_short_country_name = $place['short_name'];
                    }
                }

                $user->update([
                    'country' => $user_country,
                    'country_code' => $user_short_country_name
                ]);
            }

            if($user->status == 1) {
                return $this->error('', 'Oops! Your account has been deleted or deactivated', 401);
            }

            // $token = $user->createToken($request->userType, ['create', 'update', 'delete']);

            $code = NumberGenerator::generateVerificationCode(Otp::class, 'code');

            Otp::create([
                'phone_number' => $user->phone_number,
                'code' => $code,
            ]);

            if (str_starts_with($user->phone_number, '+254') || str_starts_with($user->phone_number, '254')) {
                $country = 'KE';
            } else {
                $country = 'GB';
            }

            SendSMS::dispatchAfterResponse($user->phone_number, 'Your verification code is: '.$code, $country);

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
     * Verify OTP and log in
     *
     * Verifies the OTP code sent by the OTP login endpoint and issues a Sanctum token for the given user type.
     * Riders must have a rider profile; riders awaiting approval receive a 200 response whose `message` is `403`.
     *
     * @bodyParam code string required The OTP code received by SMS. Example: 482913
     * @bodyParam userType string required The type of user logging in. One of `orderer`, `restaurant`, `rider`. Example: orderer
     * @bodyParam device_token string The push notification (FCM) device token. Example: fcm-device-token-123
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d",
     *       "name": "Jane Doe",
     *       "email": "jane@example.com",
     *       "phone_number": "254712345678",
     *       "role": "orderer",
     *       "avatar": null,
     *       "relationships": {
     *         "orderer": {
     *           "id": 1,
     *           "uuid": "9b1f2c3d-0000-4a6b-8c7d-1e2f3a4b5c6d",
     *           "attributes": {
     *             "name": "Jane Doe",
     *             "email": "jane@example.com",
     *             "phoneNo": "254712345678",
     *             "address": "",
     *             "city": null,
     *             "state": null,
     *             "mapLocation": null,
     *             "image": null,
     *             "status": "2"
     *           },
     *           "relationships": {
     *             "user": {
     *               "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d",
     *               "name": "Jane Doe",
     *               "email": "jane@example.com"
     *             }
     *           }
     *         }
     *       },
     *       "type": null,
     *       "referral_code": null,
     *       "is_guided": false
     *     },
     *     "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ0123456789",
     *     "role": "orderer",
     *     "restaurants": null,
     *     "restaurant": null,
     *     "country": "",
     *     "country_code": ""
     *   }
     * }
     * @response 200 scenario="Rider awaiting approval" {"status": "Request was succesful", "message": 403, "data": ["Rider profile awaiting approval"]}
     * @response 400 scenario="Validation error" {"status": "Error has occured...", "message": [{"code": ["The code field is required."]}], "data": "Authentication"}
     * @response 400 scenario="Invalid code" {"status": "Error has occured...", "message": "Invalid Code", "data": "Authentication"}
     * @response 400 scenario="Rider profile missing" {"status": "Error has occured...", "message": "Complete your rider profile.", "data": "Rider profile not complete"}
     *
     * @responseField data.user object The authenticated user (UserResource).
     * @responseField data.token string The Sanctum bearer token.
     * @responseField data.role string The user type the token was issued for.
     * @responseField data.restaurants object[] The user's restaurants (only when `userType` is `restaurant`, otherwise null).
     * @responseField data.country string Country of the user's restaurant (restaurant owners/employees only).
     * @responseField data.country_code string Country code of the user's restaurant (restaurant owners/employees only).
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
     * Account deletion page
     *
     * Returns the HTML account deletion request page (not JSON). The actual deletion is confirmed via the web form.
     *
     * @response 200 scenario="HTML page" <<html>> The account deletion HTML page.
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
