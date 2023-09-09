<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Payment;
use App\Http\Requests\V1\StorePaymentRequest;
use App\Http\Requests\V1\UpdatePaymentRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

/**
 * @group Payment Post Controller
 * 
 * Payment API resource
 */
class PaymentController extends Controller
{
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
                DB::beginTransaction();
                //get order to be paid for
                $order = Order::where('id',$request->orderId)
                ->where('status', 1) //pending
                ->first();
               
                if (!$order) {
                    return $this->error('Order Payment', 'User does not have pending order', 402);
                }

                // process payement order
                //nomarly we will get payment details from payment gateway callback
                $pay = Payment::create([
                    'transaction_id' => $request->transaction_id,
                    'order_id' => $order->id,
                    'payment_method' => $request->payment_method,
                    'amount' => $request->amount,
                    'status' => 2,
                    'created_by' => $user->name,
                ]);
                
                if ($pay->amount == $order->total_amount) {
                    $order->update(['status' =>2]);
                }

                if ($pay->amount > $order->total_amount) {
                    $balance = $order->amount - $order->total_amount;
                    DB::rollBack();
                    return $this->error('Payment Incomplete', 'Amount is given is extra by ' .$balance, 402);
                }

                if ($pay->amount < $order->total_amount) {
                    $balance = $order->total_amount - $order->amount;
                    DB::rollBack();
                    return $this->error('Payment Incomplete', 'Amount is given is less by ' .$balance, 402);
                }

                DB::commit();
                return $this->success('Order', 'Order confirmed successfully');
                
            } catch (\Throwable $th) {
                info($th);
                DB::rollBack();
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
}
