<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Paypal;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StorePaymentRequest;
use App\Http\Requests\V1\UpdatePaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Traits\Admin\UploadFileTrait;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
    public function store(StorePaymentRequest $request)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            try {
                // DB::beginTransaction();
                //get order to be paid for
                $order = Order::where('id',$request->orderId)
                ->where('status', 1) //pending
                ->first();

                if (!$order) {
                    return $this->error('Order Payment', 'User does not have pending order', 402);
                }

                if ($order->user_id != auth()->id()) {
                    return $this->error('Order Payment', 'User not authorized to make payment', 403);
                }

                return view('paypal.checkout', [
                    'client_id' => config('paypal.sandbox.client_id'),
                    'currency' => config('paypal.currency'),
                    'total_amount' => $order->total_amount,
                    'checkout_id' => $order->uuid,
                 ]);

                // process payement order
                //nomarly we will get payment details from payment gateway callback
                // $pay = Payment::create([
                //     'transaction_id' => $request->transaction_id,
                //     'order_id' => $order->id,
                //     'payment_method' => $request->payment_method,
                //     'amount' => $request->amount,
                //     'status' => 2,
                //     'created_by' => $user->name,
                // ]);

                // if ($pay->amount == $order->total_amount) {
                //     $order->update(['status' =>2]);
                // }

                // if ($pay->amount > $order->total_amount) {
                //     $balance = $order->amount - $order->total_amount;
                //     // DB::rollBack();
                //     return $this->error('Payment Incomplete', 'Amount is given is extra by ' .$balance, 402);
                // }

                // if ($pay->amount < $order->total_amount) {
                //     $balance = $order->total_amount - $order->amount;
                //     // DB::rollBack();
                //     return $this->error('Payment Incomplete', 'Amount is given is less by ' .$balance, 402);
                // }

                // DB::commit();
                // return $this->success('Order', 'Order confirmed successfully');

            } catch (\Throwable $th) {
                info($th);
                // DB::rollBack();
                return $this->error('', $th->getMessage(), 403);
            }
        } else {
            return $this->error('', 'Unauthorized', 401);
        }
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
        $order = Order::where('uuid', $request->order_id)->first();

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

        return response()->json([
            'message' => 'Successful Payment',
         ], 200);
    }
}
