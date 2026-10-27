<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\NewOrder;
use App\Helpers\AssignOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\UpdatePaymentRequest;
use App\Jobs\SendNotification;
use App\Models\AssignedOrder;
use App\Models\DietSubscription;
use App\Models\Order;
use App\Models\Payment;
use App\Models\DietSubscriptionPackage;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\Rider;
use App\Models\RiderTip;
use App\Models\StripePayment;
use App\Models\SupplementOrder;
use App\Models\User;
use App\Models\UserRestaurant;
use App\Notifications\NewOrder as NotificationsNewOrder;
use App\Traits\Admin\UploadFileTrait;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Str;

/**
 * @group Payments
 *
 * Payment endpoints: Stripe, M-Pesa (PochiPay) and PayPal checkouts for orders, rider tips,
 * supplement orders and diet plan subscriptions, plus the provider webhooks/callbacks that confirm payments.
 */
class PaymentController extends Controller
{
    use HttpResponses;
    use UploadFileTrait;

    /**
     * Render PayPal checkout page for an order
     *
     * Returns an HTML page (the `paypal.checkout` view) that loads the PayPal JS SDK so the customer can pay
     * for a pending order. The page then calls the create/capture PayPal order endpoints. Errors are returned as JSON.
     *
     * @urlParam user_id integer required The numeric id of the user paying for the order. Example: 12
     * @urlParam order_id string required The order's numeric id or uuid. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     *
     * @response 200 scenario="Checkout page" <<html>> The PayPal checkout HTML page.
     * @response 403 scenario="User not found" {"status":"Error has occured...","message":"User not found","data":"Order Payment"}
     * @response 403 scenario="Order belongs to another user" {"status":"Error has occured...","message":"Cannot make payment for order.","data":"Order Payment"}
     * @response 404 scenario="Order not found" {"status":"Error has occured...","message":"Order not found","data":"Order Payment"}
     * @response 422 scenario="Order not pending" {"status":"Error has occured...","message":"Order already paid","data":"Order Payment"}
     */
    public function store($user_id, $order_id)
    {
        $user = User::where('id', $user_id)->first();

        if (!$user) {
            return $this->error('Order Payment', 'User not found', 403);
        }

        $order = Order::with('restaurant')
                        ->where(function ($query) use ($order_id) {
                            $query->where('id', $order_id)->orWhere('uuid', $order_id);
                        })
                        ->first();

        if (!$order) {
            return $this->error('Order Payment', 'Order not found', 404);
        }

        if ($order->status !== 'Pending') {
            return $this->error('Order Payment', 'Order already paid', 422);
        }

        if ($order->user_id != $user->id) {
            return $this->error('Order Payment', 'Cannot make payment for order.', 403);
        }

        return view('paypal.checkout', [
            'client_id' => config('paypal.sandbox.client_id'),
            'currency' => config('paypal.currency'),
            'order' => $order,
            'total_amount' => $order->total_amount,
            'checkout_id' => $order->uuid,
        ]);
    }

    /**
     * Get payment details
     *
     * Admin endpoint returning a payment together with the order it pays for (with user, restaurant, rider,
     * order items and menus, reservation and assigned tables) and the order's total preparation time.
     * Returns an empty body unless the request accepts JSON.
     *
     * @group Admin - Orders & Payments
     *
     * @urlParam payment string required The uuid of the payment. Example: 3f2504e0-4f89-11d3-9a0c-0305e82c3301
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":"","data":{"order":{"id":10,"uuid":"9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d","user_id":12,"restaurant_id":3,"rider_id":7,"total_amount":"25.50","delivery":true,"delivery_fee":"3.20","delivery_address":"221B Baker Street, London","delivery_location_lat":51.5237,"delivery_location_lng":-0.1585,"delivery_status":"Delivered","status":"5","service_charge":"1.20","discount":"0","booking_time":null,"preparation_time":25,"country":"United Kingdom","user":{"id":12,"name":"Jane Doe"},"restaurant":{"id":3,"name":"Mama's Kitchen"},"rider":{"id":7,"name":"John Rider"},"order_items":[{"id":1,"order_id":10,"menu_id":4,"quantity":2,"subtotal":"20.00","menu":{"id":4,"title":"Chicken Burger"}}],"reservation":null,"order_tables":[]},"payment":{"id":5,"uuid":"3f2504e0-4f89-11d3-9a0c-0305e82c3301","transaction_id":"ch_3OabcXYZ","orderable_id":10,"orderable_type":"App\\Models\\Order","payment_method":"Stripe","amount":"25.50","status":2,"created_by":"Jane Doe","created_at":"2024-01-15T10:00:00.000000Z","updated_at":"2024-01-15T10:00:00.000000Z"}}}
     * @response 404 scenario="Payment not found" {"message":"No query results for model [App\\Models\\Payment]."}
     *
     * @responseField data.order object The paid order with loaded relations.
     * @responseField data.order.preparation_time integer Total preparation time (minutes) of all menus in the order.
     * @responseField data.payment object The payment record.
     */
    public function show(Payment $payment)
    {
        $order = $payment->orderable->load('user', 'restaurant', 'rider', 'orderItems.menu', 'reservation', 'orderTables.restaurantTable');

        $order->preparation_time = $order->getTotalPreparationTime();

        return request()->wantsJson() ?
                $this->success([
                    'order' => $order,
                    'payment' => $payment,
                ], '', 200) : '';
    }

    /**
     * Create PayPal order for an order payment
     *
     * Called by the PayPal checkout page. Creates a PayPal order with intent `CAPTURE` for the given amount
     * in the configured PayPal currency and returns the PayPal order id.
     *
     * @bodyParam total_price number The amount to charge. Example: 25.5
     *
     * @response 200 scenario="PayPal order created" {"order_id":"5O190127TN364715T"}
     *
     * @responseField order_id string The PayPal order id to approve and capture.
     */
    public function createPaypalOrder(Request $request)
    {
        $provider = new PayPalClient;

        $provider->setApiCredentials(config('paypal'));

        // Get Paypal Token
        $provider->getAccessToken();

        // Create Order for Paypal Payment
        $paypal_order = $provider->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => config('paypal.currency'),
                        'value' => $request->total_price
                    ],
                ]
            ]
        ]);

        return response()->json([
            'order_id' => $paypal_order['id']
        ], 200);
    }

    /**
     * Capture PayPal order payment
     *
     * Called by the PayPal checkout page after the customer approves the payment. Marks the order as paid
     * (status 2), records a `Paypal` payment, assigns the order to a rider, notifies the restaurant and logs the activity.
     *
     * @bodyParam checkout_id string required The uuid of the order being paid for. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @bodyParam transaction_id string required The PayPal transaction/capture id. Example: 3C679366HH908993F
     *
     * @response 200 scenario="Payment captured" {"message":"Successful Payment"}
     *
     * @responseField message string Result message.
     */
    public function capturePaypalPayment(Request $request)
    {
        $order = Order::where('uuid', $request->checkout_id)->first();

        $order->update([
            'status' => 2
        ]);

        Payment::create([
            'transaction_id' => $request->transaction_id,
            'orderable_id' => $order->id,
            'orderable_type' => Order::class,
            'payment_method' => 'Paypal',
            'amount' => $order->total_amount,
            'status' => 2,
            'created_by' => $order->user->name,
        ]);

        // Assign Order to Rider
        AssignOrder::assignOrder($order->id);

        $order->restaurant->notify(new NotificationsNewOrder($order->load('user')));

        event(new NewOrder($order->restaurant, $order->load('user')));

        activity()->causedBy($order->user)->performedOn($order)->log('paid for the order');

        return response()->json([
            'message' => 'Successful Payment',
         ], 200);
    }

    public function storeTip($order_id, $amount)
    {
        $order = Order::with('rider')
                    ->where(function ($query) use ($order_id) {
                        $query->where('id', $order_id)->orWhere('uuid', $order_id);
                    })
                    ->first();

        if (!$order) {
            return $this->error('Order Tip Payment', 'Order not found', 404);
        }

        $rider = Rider::where('user_id', $order->rider->id)->first();

        if ($rider) {
            return view('paypal.tip-checkout', [
                'client_id' => config('paypal.sandbox.client_id'),
                'currency' => config('paypal.currency'),
                'order' => $order,
                'rider' => $order->rider,
                'total_amount' => $amount,
                'checkout_id' => $order->uuid,
            ]);
        } else {
            return view('paypal.error', ['message' => 'Rider hasn\'t completed their profile']);
        }

    }

    /**
     * Create PayPal order for a rider tip
     *
     * Called by the PayPal tip checkout page. Creates a PayPal order with intent `CAPTURE` for the tip amount
     * in the configured PayPal currency and returns the PayPal order id.
     *
     * @bodyParam total_price number The tip amount to charge. Example: 5
     *
     * @response 200 scenario="PayPal order created" {"order_id":"5O190127TN364715T"}
     *
     * @responseField order_id string The PayPal order id to approve and capture.
     */
    public function createTipPaypalOrder(Request $request)
    {
        $provider = new PayPalClient;

        $provider->setApiCredentials(config('paypal'));

        // Get Paypal Token
        $provider->getAccessToken();

        // Create Order for Paypal Payment
        $paypal_order = $provider->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => config('paypal.currency'),
                        'value' => $request->total_price
                    ],
                ]
            ]
        ]);

        return response()->json([
            'order_id' => $paypal_order['id']
        ], 200);
    }

    /**
     * Capture PayPal rider tip payment
     *
     * Called by the PayPal tip checkout page after approval. Sets the order status to 2, records a paid
     * `RiderTip` for the order's rider and logs the activity.
     *
     * @bodyParam checkout_id string required The uuid of the order whose rider is being tipped. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @bodyParam transaction_id string required The PayPal transaction/capture id. Example: 3C679366HH908993F
     * @bodyParam amount number required The tip amount paid. Example: 5
     *
     * @response 200 scenario="Tip captured" {"message":"Successful Payment"}
     *
     * @responseField message string Result message.
     */
    public function captureTipPaypalPayment(Request $request)
    {
        $order = Order::where('uuid', $request->checkout_id)->first();

        $order->update([
            'status' => 2
        ]);

        $rider = Rider::where('user_id', $order->rider->id)->first();

        RiderTip::create([
            'transaction_id' => $request->transaction_id,
            'order_id' => $order->id,
            'rider_id' => $rider->id,
            'payment_method' => 'Paypal',
            'amount' => $request->amount,
            'status' => 'paid',
        ]);

        activity()->causedBy($order->user)->performedOn($order)->log('tipped '.$order.' the rider');

        return response()->json([
            'message' => 'Successful Payment',
         ], 200);
    }

    /**
     * Checkout an order (Stripe or M-Pesa)
     *
     * Starts payment for the authenticated customer's order. For restaurants outside Kenya a Stripe customer,
     * ephemeral key and PaymentIntent are created (amount rounded up to `.30`/next whole unit) and a pending
     * `StripePayment` is stored; the client completes payment with the Stripe SDK and the Stripe webhook confirms it.
     * For restaurants in Kenya an M-Pesa STK push is sent via PochiPay to the customer's phone and the PochiPay
     * callback confirms it. The `mode` placeholder is accepted but currently ignored.
     *
     * @urlParam order_id string required The order's numeric id or uuid. Example: 10
     * @urlParam mode string Payment mode (currently unused; defaults to `pochipay`). Example: pochipay
     *
     * @response 200 scenario="Stripe payment sheet (non-Kenya)" {"paymentIntent":"pi_3OabcXYZ_secret_abc123","ephemeralKey":"ek_test_YWNjdF8xTz","customer":"cus_PQ12abcXYZ","publishableKey":"pk_test_51Oabc"}
     * @response 200 scenario="M-Pesa request sent (Kenya)" {"status":"Request was succesful","message":"Payment Request sent successful","data":""}
     * @response 400 scenario="PochiPay request failed" {"status":"Error has occured...","message":"Invalid phone number","data":""}
     * @response 403 scenario="User not found" {"status":"Error has occured...","message":"User not found","data":"Order Payment"}
     * @response 403 scenario="Order belongs to another user" {"status":"Error has occured...","message":"Cannot make payment for order.","data":"Order Payment"}
     * @response 404 scenario="Order not found" {"status":"Error has occured...","message":"Order not found","data":"Order Payment"}
     * @response 422 scenario="Order already paid" {"status":"Error has occured...","message":"Order already paid","data":"Order Payment"}
     *
     * @responseField paymentIntent string Stripe PaymentIntent client secret (Stripe flow).
     * @responseField ephemeralKey string Stripe ephemeral key secret for the customer (Stripe flow).
     * @responseField customer string Stripe customer id (Stripe flow).
     * @responseField publishableKey string Stripe publishable key (Stripe flow).
     */
    public function stripeCheckout($order_id, $mode = 'pochipay')
    {
        $user = auth()->user();

        if (!$user) {
            return $this->error('Order Payment', 'User not found', 403);
        }

        $order = Order::with('restaurant')
                        ->where(function ($query) use ($order_id) {
                            $query->where('id', $order_id)->orWhere('uuid', $order_id);
                        })
                        ->first();

        if (!$order) {
            return $this->error('Order Payment', 'Order not found', 404);
        }

        // Check if order is paid for
        $payment = Payment::where('orderable_type', Order::class)->where('orderable_id', $order_id)->first();
        if ($payment) {
            return $this->error('Order Payment', 'Order already paid', 422);
        }

        if ($order->user_id != $user->id) {
            return $this->error('Order Payment', 'Cannot make payment for order.', 403);
        }

        if ($order->restaurant->country != 'Kenya') {
            $amount = explode('.', $order->total_amount);
            if (count($amount) > 1) {
                if ((int)(end($amount)) > 30) {
                    $amount = ceil((double)($order->total_amount));
                } else {
                    $amount = $amount[0].'.30';
                }
            } else {
                $amount = $order->total_amount;
            }

            // Make request to stripe to store menu item
            $stripe = new \Stripe\StripeClient(config('services.stripe.SECRET_KEY'));

            // Use an existing Customer ID if this is a returning customer.
            $customer = $stripe->customers->create();
            $ephemeralKey = $stripe->ephemeralKeys->create([
                                'customer' => $customer->id,
                            ], [
                                'stripe_version' => '2023-10-16',
                            ]);

            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $amount * 100,
                'currency' => $order->country == 'Kenya' ? 'kes' : 'gbp',
                'customer' => $customer->id,
            ]);

            StripePayment::create([
                'user_id' => $user->id,
                'payment_intent' => $paymentIntent->id,
                'payable_type' => Order::class,
                'payable_id' => $order->id,
                'amount' => $amount,
            ]);

            return response()->json(
                [
                  'paymentIntent' => $paymentIntent->client_secret,
                  'ephemeralKey' => $ephemeralKey->secret,
                  'customer' => $customer->id,
                  'publishableKey' => config('services.stripe.KEY')
                ]
            );
        } else {
            // Get token
            $token = Cache::get('pochi_token');

            if (!$token) {
                $res = Http::post(config('services.pochipay.BASE_URL') . '/account/token', [
                    'email' => config('services.pochipay.EMAIL'),
                    'password' => config('services.pochipay.PASSWORD')
                ]);

                if ($res->successful()) {
                    // Store token to cache
                    Cache::add('pochi_token', $res['result']['accessToken'], now()->addSeconds($res['result']['expiresIn']));
                    $token = $res['result']['accessToken'];
                }
            }

            // Initiate transaction
            $res = Http::withToken($token)
                ->post(config('services.pochipay.BASE_URL') . '/collections/mpesa', [
                    "orderId" => explode('-', $order->uuid)[0],
                    "billRefNumber" => explode('-', $order->uuid)[0],
                    "phoneNumber" => Str::startsWith($order->user->phone_number, '+254') ? $order->user->phone_number : '+' . $order->user->phone_number,
                    "amount" => round($order->total_amount),
                    "narration" => "test",
                    "callbackUrl" => route('pochipay.callback'),
                ]);

            if ($res->successful()) {
                return $this->success('', 'Payment Request sent successful');
            } else {
                return $this->error('', $res['message'], 400);
            }
        }

        // Send to mobile
        // {
        //     "callbackUrl": "",//your callback url
        //     "requestId": "",//unique reference to identify the disbursement batch
        //     "disbursementTitle": "", // a title for the disbursement
        //     "recipients": [
        //       {
        //         "amount": 0,
        //         "remarks": "", // the narration
        //         "trackingReference": "", //unique reference for the recipient
        //         "phoneNumber": "" //valid phone number
        //       }
        //     ]
        //   }
    }

    /**
     * Stripe checkout for a rider tip
     *
     * Creates an unpaid `RiderTip` for the order's rider, a Stripe customer, ephemeral key and a GBP PaymentIntent
     * (automatic payment methods enabled), and stores a pending `StripePayment`. The Stripe webhook marks the tip as paid.
     * Note: this route shares its URI pattern with the order checkout route (`/stripe/checkout/{order_id}/{mode?}`),
     * which is registered first and therefore matches these requests.
     *
     * @urlParam order_id string required The order's numeric id or uuid. Example: 10
     * @urlParam amount integer required The tip amount in the smallest currency unit (pence). Example: 500
     *
     * @response 200 scenario="Stripe payment sheet" {"paymentIntent":"pi_3OabcXYZ_secret_abc123","ephemeralKey":"ek_test_YWNjdF8xTz","customer":"cus_PQ12abcXYZ","publishableKey":"pk_test_51Oabc"}
     * @response 404 scenario="Order not found" {"status":"Error has occured...","message":"Order not found","data":"Order Tip Payment"}
     *
     * @responseField paymentIntent string Stripe PaymentIntent client secret.
     * @responseField ephemeralKey string Stripe ephemeral key secret for the customer.
     * @responseField customer string Stripe customer id.
     * @responseField publishableKey string Stripe publishable key.
     */
    public function stripeTipCheckout($order_id, $amount)
    {
        $order = Order::with('rider')
                    ->where(function ($query) use ($order_id) {
                        $query->where('id', $order_id)->orWhere('uuid', $order_id);
                    })
                    ->first();

        if (!$order) {
            return $this->error('Order Tip Payment', 'Order not found', 404);
        }

        $rider = Rider::where('user_id', $order->rider->id)->first();

        $rider_tip = RiderTip::create([
            'rider_id' => $rider->id,
            'order_id' => $order->id,
            'amount' => $amount,
        ]);

        // Make request to stripe to store menu item
        $stripe = new \Stripe\StripeClient(config('services.stripe.SECRET_KEY'));

        // Use an existing Customer ID if this is a returning customer.
        $customer = $stripe->customers->create();
        $ephemeralKey = $stripe->ephemeralKeys->create([
                            'customer' => $customer->id,
                        ], [
                            'stripe_version' => '2023-10-16',
                        ]);

        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => $amount,
            'currency' => 'gbp',
            'customer' => $customer->id,
            // In the latest version of the API, specifying the `automatic_payment_methods` parameter
            // is optional because Stripe enables its functionality by default.
            'automatic_payment_methods' => [
                'enabled' => 'true',
            ],
        ]);

        StripePayment::create([
            'user_id' => auth()->id(),
            'payment_intent' => $paymentIntent->client_secret,
            'payable_type' => RiderTip::class,
            'payable_id' => $rider_tip->id,
            'amount' => $amount,
        ]);

        return response()->json(
            [
              'paymentIntent' => $paymentIntent->client_secret,
              'ephemeralKey' => $ephemeralKey->secret,
              'customer' => $customer->id,
              'publishableKey' => config('services.stripe.KEY')
            ]
        );
    }

    /**
     * Stripe checkout for a supplement order
     *
     * Creates a Stripe customer, ephemeral key and a KES PaymentIntent for the authenticated user's pending
     * supplement order, and stores a pending `StripePayment`. The Stripe webhook marks the order as paid.
     *
     * @group Supplements
     *
     * @urlParam id integer required The numeric id of the supplement order. Example: 4
     *
     * @response 200 scenario="Stripe payment sheet" {"paymentIntent":"pi_3OabcXYZ_secret_abc123","ephemeralKey":"ek_test_YWNjdF8xTz","customer":"cus_PQ12abcXYZ","publishableKey":"pk_test_51Oabc"}
     * @response 403 scenario="User not found" {"status":"Error has occured...","message":"User not found","data":"Order Payment"}
     * @response 403 scenario="Order belongs to another user" {"status":"Error has occured...","message":"Cannot make payment for order.","data":"Order Payment"}
     * @response 404 scenario="Order not found" {"status":"Error has occured...","message":"Order not found","data":"Order Payment"}
     * @response 422 scenario="Order not pending" {"status":"Error has occured...","message":"Order already paid","data":"Order Payment"}
     *
     * @responseField paymentIntent string Stripe PaymentIntent client secret.
     * @responseField ephemeralKey string Stripe ephemeral key secret for the customer.
     * @responseField customer string Stripe customer id.
     * @responseField publishableKey string Stripe publishable key.
     */
    public function stripeSupplementCheckout($order_id)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->error('Order Payment', 'User not found', 403);
        }

        $order = SupplementOrder::with('supplement.supplier')
                        ->where(function ($query) use ($order_id) {
                            $query->where('id', $order_id);
                        })
                        ->first();

        if (!$order) {
            return $this->error('Order Payment', 'Order not found', 404);
        }

        if ($order->status !== 'pending') {
            return $this->error('Order Payment', 'Order already paid', 422);
        }

        if ($order->user_id != $user->id) {
            return $this->error('Order Payment', 'Cannot make payment for order.', 403);
        }

        $amount = $order->amount;

        // Make request to stripe to store menu item
        $stripe = new \Stripe\StripeClient(config('services.stripe.SECRET_KEY'));

        // Use an existing Customer ID if this is a returning customer.
        $customer = $stripe->customers->create();
        $ephemeralKey = $stripe->ephemeralKeys->create([
                            'customer' => $customer->id,
                        ], [
                            'stripe_version' => '2023-10-16',
                        ]);

        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => $amount * 1000,
            'currency' => 'kes',
            'customer' => $customer->id,
        ]);

        StripePayment::create([
            'user_id' => $user->id,
            'payment_intent' => $paymentIntent->id,
            'payable_type' => SupplementOrder::class,
            'payable_id' => $order->id,
            'amount' => $amount,
        ]);

        return response()->json(
            [
              'paymentIntent' => $paymentIntent->client_secret,
              'ephemeralKey' => $ephemeralKey->secret,
              'customer' => $customer->id,
              'publishableKey' => config('services.stripe.KEY')
            ]
        );
    }

    /**
     * Stripe checkout for a diet plan subscription
     *
     * Subscribes the authenticated user to a diet subscription package. Reuses the user's current pending
     * subscription or creates a new pending one (end date derived from the package duration), then creates a
     * Stripe customer, ephemeral key and PaymentIntent in the package currency and stores a pending `StripePayment`.
     * The Stripe webhook marks the subscription as paid. Body measurements are only required when the user's
     * profile has neither height nor weight.
     *
     * @group Diet Plans
     *
     * @urlParam diet_subscription_package integer required The numeric id of the diet subscription package. Example: 2
     *
     * @bodyParam height number User height. Required when the user's profile has no height and no weight. Example: 175
     * @bodyParam height_units string Height units; required with `height` in the same case. Example: cm
     * @bodyParam weight number User weight. Required when the user's profile has no height and no weight. Example: 70
     * @bodyParam weight_units string Weight units; required with `weight` in the same case. Example: kg
     * @bodyParam body_mass_index number User BMI. Required when the user's profile has no height and no weight. Example: 22.9
     *
     * @response 200 scenario="Stripe payment sheet" {"paymentIntent":"pi_3OabcXYZ_secret_abc123","ephemeralKey":"ek_test_YWNjdF8xTz","customer":"cus_PQ12abcXYZ","publishableKey":"pk_test_51Oabc"}
     * @response 400 scenario="Validation error" {"status":"Error has occured...","message":"Diet plan data","data":{"height":["The height field is required."]}}
     * @response 400 scenario="Already subscribed" {"status":"Error has occured...","message":"Diet Plans Checkout","data":"You already have a subscription"}
     * @response 400 scenario="Invalid package duration" {"status":"Error has occured...","message":"Invalid subscription package","data":""}
     * @response 404 scenario="Package not found" {"message":"No query results for model [App\\Models\\DietSubscriptionPackage] 99"}
     *
     * @responseField paymentIntent string Stripe PaymentIntent client secret.
     * @responseField ephemeralKey string Stripe ephemeral key secret for the customer.
     * @responseField customer string Stripe customer id.
     * @responseField publishableKey string Stripe publishable key.
     */
    public function stripeDietPlanCheckout(Request $request, DietSubscriptionPackage $diet_subscription_package)
    {
        if (!auth()->user()->height && !auth()->user()->weight) {
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
        }

        // Check if user has an active subscription
        $subscription = DietSubscription::where(['user_id' => auth()->id(), 'status' => 'paid'])->whereDate('end_date', '>=', now()->format('Y-m-d'))->first();

        if ($subscription) {
            return $this->error('You already have a subscription', 'Diet Plans Checkout', 400);
        }

        $subscription = DietSubscription::where(['user_id' => auth()->id(), 'status' => 'pending'])->whereDate('end_date', '>=', now()->format('Y-m-d'))->first();

        if (!$subscription) {
            $start_date = now()->format('Y-m-d');
            switch ($diet_subscription_package->duration) {
                case 'daily':
                    $end_date = now()->addDay()->format('Y-m-d');
                    break;
                case 'weekly':
                    $end_date = now()->addWeek()->format('Y-m-d');
                    break;
                case 'monthly':
                    $end_date = now()->addMonth()->format('Y-m-d');
                    break;
                case 'quarterly':
                    $end_date = now()->addMonths(3)->format('Y-m-d');
                    break;
                case 'half annually':
                    $end_date = now()->addMonths(6)->format('Y-m-d');
                    break;
                case 'annually':
                    $end_date = now()->addYear()->format('Y-m-d');
                    break;
                default:
                    return $this->error('', 'Invalid subscription package', 400);
                    break;
            }

            $subscription = DietSubscription::create([
                'user_id' => auth()->id(),
                'diet_subscription_package_id' => $diet_subscription_package->id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'status' => 'pending'
            ]);

            $amount = $diet_subscription_package->currency == 'kes' ? (double) $diet_subscription_package->price * 1000 : (double) $diet_subscription_package->price + 0.30;

            // Make request to stripe to store menu item
            $stripe = new \Stripe\StripeClient(config('services.stripe.SECRET_KEY'));

            // Use an existing Customer ID if this is a returning customer.
            $customer = $stripe->customers->create();
            $ephemeralKey = $stripe->ephemeralKeys->create([
                                'customer' => $customer->id,
                            ], [
                                'stripe_version' => '2023-10-16',
                            ]);

            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $amount * 100,
                'currency' => $diet_subscription_package->currency,
                'customer' => $customer->id,
            ]);

            StripePayment::create([
                'user_id' => auth()->id(),
                'payment_intent' => $paymentIntent->id,
                'payable_type' => DietSubscription::class,
                'payable_id' => $subscription->id,
                'amount' => $amount,
            ]);

            return response()->json([
                'paymentIntent' => $paymentIntent->client_secret,
                'ephemeralKey' => $ephemeralKey->secret,
                'customer' => $customer->id,
                'publishableKey' => config('services.stripe.KEY')
            ]);
        } else {
            $amount = $diet_subscription_package->currency == 'kes' ? (double) $diet_subscription_package->price * 1000 : (double) $diet_subscription_package->price + 0.30;
            // Make request to stripe to store menu item
            $stripe = new \Stripe\StripeClient(config('services.stripe.SECRET_KEY'));

            // Use an existing Customer ID if this is a returning customer.
            $customer = $stripe->customers->create();
            $ephemeralKey = $stripe->ephemeralKeys->create([
                                'customer' => $customer->id,
                            ], [
                                'stripe_version' => '2023-10-16',
                            ]);

            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $amount,
                'currency' => $diet_subscription_package->currency,
                'customer' => $customer->id,
            ]);

            StripePayment::create([
                'user_id' => auth()->id(),
                'payment_intent' => $paymentIntent->id,
                'payable_type' => DietSubscription::class,
                'payable_id' => $subscription->id,
                'amount' => $amount,
            ]);

            return response()->json([
                'paymentIntent' => $paymentIntent->client_secret,
                'ephemeralKey' => $ephemeralKey->secret,
                'customer' => $customer->id,
                'publishableKey' => config('services.stripe.KEY')
            ]);
        }
    }

    /**
     * Stripe webhook
     *
     * Receives Stripe event notifications. Only events whose `data.object.object` is `charge` are handled; the
     * charge's `payment_intent` is matched to a stored `StripePayment`. On `charge.succeeded` the payment is marked
     * successful and, depending on what was paid for, the order is marked paid (status 2), assigned to a rider and
     * the restaurant notified; or the rider tip is marked paid; or the supplement order is marked paid; or the diet
     * subscription is marked paid. A `Payment` record is created (except for diet subscriptions) and the user is notified.
     * On `charge.payment_failed` the user is notified. Unmatched or unhandled events return an empty 200 response.
     * Called by Stripe, not by API clients.
     *
     * @bodyParam id string The Stripe event id. Example: evt_3OabcXYZ
     * @bodyParam type string required The Stripe event type. Handled values: `charge.succeeded`, `charge.payment_failed`. Example: charge.succeeded
     * @bodyParam data object required The event data wrapper.
     * @bodyParam data.object object required The Stripe object the event refers to.
     * @bodyParam data.object.object string required The Stripe object type; only `charge` is processed. Example: charge
     * @bodyParam data.object.id string required The charge id, stored as the payment transaction id. Example: ch_3OabcXYZ
     * @bodyParam data.object.payment_intent string required The PaymentIntent id the charge belongs to. Example: pi_3OabcXYZ
     *
     * @response 200 scenario="Payment processed" {"message":"Successful Payment"}
     * @response 200 scenario="Event ignored" ""
     *
     * @responseField message string Result message.
     */
    public function stripeWebhookCallback(Request $request)
    {
        // Get the payment details
        if ($request->all()['data']['object']['object'] == 'charge') {
            // Check if payment is successful
            $payment_intent = $request->all()['data']['object']['payment_intent'];

            $stripe_payment = StripePayment::where('payment_intent', $payment_intent)->first();

            if ($stripe_payment) {
                if ($request->all()['type'] == 'charge.succeeded') {
                    $stripe_payment->update([
                        'status' => 'success'
                    ]);

                    switch ($stripe_payment->payable_type) {
                        case 'App\\Models\\Order':
                            // Get and update order details and send notification to user
                            $order = $stripe_payment->payable_type::find($stripe_payment->payable_id);

                            if ($order) {
                                $order->update([
                                    'status' => 2
                                ]);

                                // Assign Order to Rider
                                AssignOrder::assignOrder($order->id);

                                $order->restaurant->notify(new NotificationsNewOrder($order->load('user')));

                                event(new NewOrder($order->restaurant, $order->load('user')));

                                Payment::create([
                                    'transaction_id' => $request->all()['data']['object']['id'],
                                    'orderable_id' => $order->id,
                                    'orderable_type' => Order::class,
                                    'payment_method' => 'Stripe',
                                    'amount' => $order->total_amount,
                                    'status' => 2,
                                    'created_by' => $order->user->name,
                                ]);

                                SendNotification::dispatchAfterResponse($stripe_payment->user, 'Payment was successful. Order has started being prepared', ['order' => $order]);

                                activity()->causedBy($order->user)->performedOn($order)->log('paid for the order');

                                return response()->json([
                                    'message' => 'Successful Payment',
                                ], 200);
                            }
                            break;
                        case 'App\\Models\\RiderTip':
                            // Update Tip
                            $rider_tip = $stripe_payment->payable_type::find($stripe_payment->payable_id);
                            if ($rider_tip) {
                                $rider_tip->update([
                                    'status' => 'paid',
                                ]);

                                Payment::create([
                                    'transaction_id' => $request->all()['data']['object']['id'],
                                    'orderable_id' => $rider_tip->id,
                                    'orderable_type' => RiderTip::class,
                                    'payment_method' => 'Stripe',
                                    'amount' => $stripe_payment->amount,
                                    'status' => 2,
                                    'created_by' => $stripe_payment->user->name,
                                ]);

                                SendNotification::dispatchAfterResponse($stripe_payment->user, 'Payment was successful. Rider hhas been tipped');

                                activity()->causedBy($rider_tip->order->user)->performedOn($rider_tip->order)->log('tipped '.$rider_tip->order.' the rider');

                                return response()->json([
                                    'message' => 'Successful Payment',
                                ], 200);
                            }
                            break;
                        case 'App\\Models\\SupplementOrder':
                            // Get and update order details and send notification to user
                            $order = $stripe_payment->payable_type::find($stripe_payment->payable_id);

                            if ($order) {
                                $order->update([
                                    'status' => 2
                                ]);

                                Payment::create([
                                    'transaction_id' => $request->all()['data']['object']['id'],
                                    'orderable_id' => $order->id,
                                    'orderable_type' => SupplementOrder::class,
                                    'payment_method' => 'Stripe',
                                    'amount' => $stripe_payment->amount,
                                    'status' => 2,
                                    'created_by' => $stripe_payment->user->name,
                                ]);

                                SendNotification::dispatchAfterResponse($stripe_payment->user, 'Payment was successful. Order has started being prepared for delivery', ['order' => $order]);

                                activity()->causedBy($order->user)->performedOn($order)->log('paid for the supplement order');

                                return response()->json([
                                    'message' => 'Successful Payment',
                                ], 200);
                            }
                            break;
                        case 'App\\Models\\DietSubscription':
                            // Get and update order details and send notification to user
                            $order = $stripe_payment->payable_type::find($stripe_payment->payable_id);

                            if ($order) {
                                $order->update([
                                    'status' => 'paid'
                                ]);

                                // Payment::create([
                                //     'transaction_id' => $request->all()['data']['object']['id'],
                                //     'order_id' => $order->id,
                                //     'payment_method' => 'Stripe',
                                //     'amount' => 50,
                                //     'status' => 2,
                                //     'created_by' => $order->user->name,
                                // ]);

                                SendNotification::dispatchAfterResponse($stripe_payment->user, 'Subscription to package completed sucessfully', ['order' => $order]);

                                activity()->causedBy($order->user)->performedOn($order)->log('paid for diet plan subscription');

                                return response()->json([
                                    'message' => 'Successful Payment',
                                ], 200);
                            }
                            break;
                        default:
                            # code...
                            break;
                    }
                }

                if ($request->all()['type'] == 'charge.payment_failed') {
                    if ($stripe_payment->user->device_token) {
                        SendNotification::dispatchSync($stripe_payment->user, 'Payment was not successful');
                    }
                }
            }
        }
    }

    /**
     * M-Pesa checkout for an order
     *
     * Sends an M-Pesa STK push via PochiPay to the authenticated customer's phone number for the rounded order total.
     * Only available for orders from restaurants in Kenya. Payment is confirmed through the PochiPay callback.
     *
     * @urlParam order_id string required The order's numeric id or uuid. Example: 10
     *
     * @response 200 scenario="M-Pesa request sent" {"status":"Request was succesful","message":"Payment Request sent successful","data":""}
     * @response 400 scenario="Restaurant outside Kenya" {"status":"Error has occured...","message":"Cannot use mpesa for payments outside Kenya","data":"Order Payment"}
     * @response 400 scenario="PochiPay request failed" {"status":"Error has occured...","message":"Invalid phone number","data":""}
     * @response 403 scenario="User not found" {"status":"Error has occured...","message":"User not found","data":"Order Payment"}
     * @response 403 scenario="Order belongs to another user" {"status":"Error has occured...","message":"Cannot make payment for order.","data":"Order Payment"}
     * @response 404 scenario="Order not found" {"status":"Error has occured...","message":"Order not found","data":"Order Payment"}
     * @response 422 scenario="Order already paid" {"status":"Error has occured...","message":"Order already paid","data":"Order Payment"}
     *
     * @responseField message string Result message.
     */
    public function mpesaCheckout($order_id)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->error('Order Payment', 'User not found', 403);
        }

        $order = Order::with('restaurant')
                        ->where(function ($query) use ($order_id) {
                            $query->where('id', $order_id)->orWhere('uuid', $order_id);
                        })
                        ->first();

        if (!$order) {
            return $this->error('Order Payment', 'Order not found', 404);
        }

        // Check if order is paid for
        $payment = Payment::where('orderable_type', Order::class)->where('orderable_id', $order_id)->first();
        if ($payment) {
            return $this->error('Order Payment', 'Order already paid', 422);
        }

        if ($order->user_id != $user->id) {
            return $this->error('Order Payment', 'Cannot make payment for order.', 403);
        }

        if ($order->restaurant->country != 'Kenya') {
            return $this->error('Order Payment', 'Cannot use mpesa for payments outside Kenya', 400);
        }

        // Get token
        $token = Cache::get('pochi_token');

        if (!$token) {
            $res = Http::post(config('services.pochipay.BASE_URL') . '/account/token', [
                'email' => config('services.pochipay.EMAIL'),
                'password' => config('services.pochipay.PASSWORD')
            ]);

            if ($res->successful()) {
                // Store token to cache
                Cache::add('pochi_token', $res['result']['accessToken'], now()->addSeconds($res['result']['expiresIn']));
                $token = $res['result']['accessToken'];
            }
        }

        // Initiate transaction
        $res = Http::withToken($token)
            ->post(config('services.pochipay.BASE_URL') . '/collections/mpesa', [
                "orderId" => explode('-', $order->uuid)[0],
                "billRefNumber" => explode('-', $order->uuid)[0],
                "phoneNumber" => Str::startsWith($order->user->phone_number, '+254') ? $order->user->phone_number : '+' . $order->user->phone_number,
                "amount" => round($order->total_amount),
                "narration" => "test",
                "callbackUrl" => route('pochipay.callback'),
            ]);

        if ($res->successful()) {
            return $this->success('', 'Payment Request sent successful');
        } else {
            return $this->error('', $res['message'], 400);
        }
    }

    /**
     * PochiPay (M-Pesa) callback
     *
     * Receives the result of an M-Pesa collection initiated through PochiPay. The order is matched by the uuid prefix
     * in `billRefNumber`. On success the order is marked paid (status 2), assigned to a rider, the restaurant and
     * customer are notified and a `Mobile Money` payment is recorded. Called by PochiPay, not by API clients.
     *
     * @bodyParam billRefNumber string The bill reference sent when initiating the collection (first segment of the order uuid). Example: 9b1deb4d
     * @bodyParam thirdPartyReference string The M-Pesa transaction reference; must not already exist as a payment transaction id. Example: SAB1CD2EFG
     * @bodyParam isSuccessful boolean Whether the collection succeeded. Example: true
     * @bodyParam failReason string Reason for failure, when unsuccessful. Example: Request cancelled by user
     *
     * @response 200 scenario="Payment recorded" {"status":"Request was succesful","message":"Successful Payment","data":""}
     * @response 400 scenario="Payment failed or order not found" {"status":"Error has occured...","message":"Payment was not successful","data":"Request cancelled by user"}
     * @response 422 scenario="Duplicate transaction" {"message":{"thirdPartyReference":["The third party reference has already been taken."]}}
     */
    public function pochipayCallback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'billRefNumber' => ['sometimes', 'nullable'],
            'thirdPartyReference' => ['sometimes', 'nullable', 'unique:payments,transaction_id']
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->messages()], 422);
        }

        $order = Order::where('uuid', 'LIKE', $request->billRefNumber . '%')->first();

        if ($order && $request->isSuccessful) {
            $order->update([
                'status' => 2
            ]);

            // Assign Order to Rider
            AssignOrder::assignOrder($order->id);

            $order->restaurant->notify(new NotificationsNewOrder($order->load('user')));

            event(new NewOrder($order->restaurant, $order->load('user')));

            Payment::create([
                'transaction_id' => $request->thirdPartyReference,
                'orderable_id' => $order->id,
                'orderable_type' => Order::class,
                'payment_method' => 'Mobile Money',
                'amount' => $order->total_amount,
                'status' => 2,
                'created_by' => $order->user->name,
            ]);

            SendNotification::dispatchAfterResponse($order->user, 'Payment was successful. Order has started being prepared', ['order' => $order]);

            activity()->causedBy($order->user)->performedOn($order)->log('paid for the order');

            return $this->success('', 'Successful Payment');
        }

        return $this->error($request->failReason, 'Payment was not successful', 400);
    }
}
