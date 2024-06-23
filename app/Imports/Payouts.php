<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Order;
use App\Models\Payout;
use App\Models\Payment;
use App\Models\RiderTip;
use App\Models\Restaurant;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class Payouts implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            $partner = User::where('email', $row['email'])->first();

            if (!$partner) {
                $partner = Restaurant::where('email', $row['email'])->first();
            }

            // Check if amount is more that partner balance
            if ($partner) {
                $earnings = 0;
                if ($partner instanceof Restaurant) {
                    $orders = Order::where('restaurant_id', $partner->id)
                                    ->where('delivery_status', 'Delivered')
                                    ->get();

                    $total_service_charges = $orders->sum('service_charge');
                    $rider_earnings = $orders->where('delivery', true)->sum('delivery_fee');

                    $paid_amount = Payout::with('payable')
                                        ->where('payable_type', Restaurant::class)
                                        ->where('payable_id', $partner->id)
                                        ->sum('amount');

                    $payment_data = Payment::with('orderable.restaurant')
                                    ->whereHas('orderable', function ($query) use ($orders) {
                                        $query->where('orderable_type', Order::class)
                                                ->whereIn('orderable_id', $orders->pluck('id'));
                                    })
                                    ->where('transaction_id', '!=', NULL)
                                    ->sum('amount');

                    $earnings = $payment_data - $rider_earnings - $total_service_charges - $paid_amount;

                    if ((double) $row['amount'] < (double) $earnings) {
                        Payout::create([
                            'payable_id' => $partner->id,
                            'payable_type' => Restaurant::class,
                            'amount' => $row['amount'],
                        ]);
                    }
                }
                if ($partner instanceof User) {
                    $earnings = Order::where('rider_id', $partner->id)->where('delivery_status', 'delivered')->sum('delivery_fee');

                    $tips = 0;
                    if ($partner->rider) {
                        $tips = RiderTip::where('rider_id', $partner->rider->id)->where('transaction_id', '!=', NULL)->sum('amount');
                    }

                    // Add disbursed amount
                    $paid_amount = Payout::with('payable')
                                        ->where('payable_type', User::class)
                                        ->where('payable_id', $partner->id)
                                        ->sum('amount');

                    $earnings = $earnings + $tips - $paid_amount;

                    if ((double) $row['amount'] < (double) $earnings) {
                        Payout::create([
                            'payable_id' => $partner->id,
                            'payable_type' => User::class,
                            'amount' => $row['amount'],
                        ]);
                    }
                }
            }
        }
    }
}
