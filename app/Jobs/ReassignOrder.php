<?php

namespace App\Jobs;

use App\Helpers\AssignOrder;
use App\Models\AssignedOrder;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReassignOrder implements ShouldQueue
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
        // Get pending assigned orders
        $assigned_orders = AssignedOrder::where(function ($query) {
                                                $query->where('status', 'pending');
                                            })
                                            ->whereBetween('created_at', [now(), now()->addSeconds(10)])
                                            ->get()
                                            ->pluck('order_id');

        // Mark pending assigned orders as rejected
        $assigned_orders->each(function ($order) {
            $order->update([
                'status' => 'rejected'
            ]);
        });

        // Get all pending orders that have been paid for and have no rider assigned
        $orders = Order::whereHas('payment')
                        ->where('rider_id', NULL)
                        ->where(function ($query) {
                            $query->where('status', '1')
                                    ->orWhere('status', '2');
                        })
                        ->get();

        $orders->each(function ($order) {
            AssignOrder::assignOrder($order->id);
        });
    }
}
