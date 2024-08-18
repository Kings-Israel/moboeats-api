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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

/**
 * @group Payment Post Controller
 *
 * Payment API resource
 */
class PaymentController extends Controller
{
    use HttpResponses;
    use UploadFileTrait;

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

    public function show(Payment $payment)
    {
        $order = $payment->order->load('user', 'restaurant', 'rider', 'orderItems.menu', 'reservation', 'orderTables.restaurantTable');

        $order->preparation_time = $order->getTotalPreparationTime();

        return request()->wantsJson() ?
                $this->success([
                    'order' => $order,
                    'payment' => $payment,
                ], '', 200) : '';
    }

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
     * Stripe Order checkout
     * @urlParam order_id int The id of the order
     */
    public function stripeCheckout($order_id)
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

        if ($order->status !== 'Pending') {
            return $this->error('Order Payment', 'Order already paid', 422);
        }

        if ($order->user_id != $user->id) {
            return $this->error('Order Payment', 'Cannot make payment for order.', 403);
        }

        // info((double)($order->total_amount));
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
            'currency' => 'gbp',
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
    }

    /**
     * Stripe Tip checkout
     * @urlParam order_id int The id of the order
     * @urlParam amount int The amount to tip
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
     * Stripe Supplement payment checkout request
     * @urlParam order_id int The id of the order
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
     * Stripe Diet Subscription payment checkout request
     * @urlParam diet_subscription_package int The id of the diet subscription package
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
                                // $order->update([
                                //     'status' => 2
                                // ]);

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
}
