<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\NewOrder;
use App\Helpers\AssignOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\UpdatePaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\NewOrder as NotificationsNewOrder;
use App\Traits\Admin\UploadFileTrait;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
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

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(StorePaymentRequest $request)
    // {
    //     $user = User::where('id', Auth::user()->id)->first();
    //     if ($user->hasRole(Auth::user()->role_id)) {
    //         try {
    //             // DB::beginTransaction();
    //             //get order to be paid for
    //             $order = Order::where('id',$request->orderId)
    //             ->where('status', 1) //pending
    //             ->first();

    //             if (!$order) {
    //                 return $this->error('Order Payment', 'User does not have pending order', 402);
    //             }

    //             if ($order->user_id != auth()->id()) {
    //                 return $this->error('Order Payment', 'User not authorized to make payment', 403);
    //             }

    //             return view('paypal.checkout', [
    //                 'client_id' => config('paypal.sandbox.client_id'),
    //                 'currency' => config('paypal.currency'),
    //                 'total_amount' => $order->total_amount,
    //                 'checkout_id' => $order->uuid,
    //              ]);

    //             // process payement order
    //             //nomarly we will get payment details from payment gateway callback
    //             // $pay = Payment::create([
    //             //     'transaction_id' => $request->transaction_id,
    //             //     'order_id' => $order->id,
    //             //     'payment_method' => $request->payment_method,
    //             //     'amount' => $request->amount,
    //             //     'status' => 2,
    //             //     'created_by' => $user->name,
    //             // ]);

    //             // if ($pay->amount == $order->total_amount) {
    //             //     $order->update(['status' =>2]);
    //             // }

    //             // if ($pay->amount > $order->total_amount) {
    //             //     $balance = $order->amount - $order->total_amount;
    //             //     // DB::rollBack();
    //             //     return $this->error('Payment Incomplete', 'Amount is given is extra by ' .$balance, 402);
    //             // }

    //             // if ($pay->amount < $order->total_amount) {
    //             //     $balance = $order->total_amount - $order->amount;
    //             //     // DB::rollBack();
    //             //     return $this->error('Payment Incomplete', 'Amount is given is less by ' .$balance, 402);
    //             // }

    //             // DB::commit();
    //             // return $this->success('Order', 'Order confirmed successfully');

    //         } catch (\Throwable $th) {
    //             info($th);
    //             // DB::rollBack();
    //             return $this->error('', $th->getMessage(), 403);
    //         }
    //     } else {
    //         return $this->error('', 'Unauthorized', 401);
    //     }
    // }

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
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        //
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        //
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
            'order_id' => $order->id,
            'payment_method' => 'Paypal',
            'amount' => $order->total_amount,
            'status' => 2,
            'created_by' => $order->user->name,
        ]);

        // Assign Order to Rider
        AssignOrder::assignOrder($order->id);

        $order->restaurant->notify(new NotificationsNewOrder($order->load('user')));
        event(new NewOrder($order->restaurant, $order->load('user')));

        return response()->json([
            'message' => 'Successful Payment',
         ], 200);
    }
}
