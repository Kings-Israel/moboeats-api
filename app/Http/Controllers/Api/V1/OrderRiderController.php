<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\OrderRider;
use App\Http\Requests\StoreOrderRiderRequest;
use App\Http\Requests\UpdateOrderRiderRequest;
use App\Http\Controllers\Controller;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;

/**
 * @group Order Delivery Management
 * 
 * Order Delivery API resource
 */
class OrderRiderController extends Controller
{
    use HttpResponses;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // this person wants to see available riders
    }

    
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRiderRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(OrderRider $orderRider)
    {
        //
    }

    
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRiderRequest $request, OrderRider $orderRider)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderRider $orderRider)
    {
        //
    }
}
