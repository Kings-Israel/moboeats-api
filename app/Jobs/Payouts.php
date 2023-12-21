<?php

namespace App\Jobs;

use App\Helpers\Paypal;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Restaurant;
use App\Models\Rider;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Payouts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $orders = Order::whereHas('payment')->get()->groupBy('restaurant_id');

        $payouts_items = [
            'items' => [],
            'sender_batch_sender' => [
                'sender_batch_id' => "Payouts_".now()->format('Y').'_'.now()->format('dmhi'),
                'email_subject' => 'You have a payout',
                'email_message' => 'You have received a payout! Thanks for using our service!',
            ],
        ];

        foreach ($orders as $key => $order) {
            // Get Restaurants Payouts
            $restaurant = Restaurant::where('id', $key)->where('paypal_email', '!=', NULL)->first();

            if ($restaurant) {
                $amount = 0;
                foreach ($order as $order_details) {
                    if ($order_details->delivery) {
                        // IF Delivery, subtract service charge and delivery fee
                        $amount += $order_details->total_amount - ($order_details->service_charge + $order_details->delivery_fee);
                    } else {
                        // If Dine in, subtract service charge
                        $amount += $order_details->total_amount - $order_details->service_charge;
                    }
                }

                $restaurant_payout_details = [
                    'receiver' => $restaurant->paypal_email,
                    'amount' => [
                        'value' => (string) $amount,
                        'currency' => config('paypal.currency')
                    ],
                    'recipient_type' => 'EMAIL',
                    'note' => 'Thanks for choosing Moboeats',
                    'senter_item_id' => mt_rand(100000000, 999999999),
                    'recipient_wallet' => 'RECIPIENT_SELECTED'
                ];

                array_push($payouts_items['items'], $restaurant_payout_details);

                Payout::create([
                    'payable_id' => $restaurant->id,
                    'payable_type' => Restaurant::class,
                    'amount' => $amount
                ]);
            }
        }

        $rider_orders = Order::whereHas('payment')->where('delivery', 1)->get()->groupBy('rider_id');

        foreach ($rider_orders as $key => $order) {
            // Get Reiders Payouts
            $rider_details = Rider::where('user_id', $key)->where('paypal_email', '!=', NULL)->first();

            if ($rider_details) {
                $amount = 0;

                foreach ($order as $order_details) {
                    $amount += $order_details->delivery_fee;
                }

                $rider_payout_details = [
                    'receiver' => $rider_details->paypal_email,
                    'amount' => [
                        'value' => (string) $amount,
                        'currency' => config('paypal.currency')
                    ],
                    'recipient_type' => 'EMAIL',
                    'note' => 'Thanks for choosing Moboeats',
                    'senter_item_id' => mt_rand(100000000, 999999999),
                    'recipient_wallet' => 'RECIPIENT_SELECTED'
                ];

                array_push($payouts_items['items'], $rider_payout_details);

                Payout::create([
                    'payable_id' => $rider_details->id,
                    'payable_type' => User::class,
                    'amount' => $amount
                ]);
            }
        }

        if (count($payouts_items['items']) > 0) {
            $token = Paypal::token();
            $url = Paypal::url();

            Http::withHeaders([
                    'Authorization' => $token['token_type'].' '.$token['access_token'],
                    'Content-Type' => 'application/json',
                ])
                ->post($url.'/v1/payments/payouts', $payouts_items);
        }

    }
}
