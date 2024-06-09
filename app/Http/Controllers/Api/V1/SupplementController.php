<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SupplementOrderResource;
use App\Http\Resources\V1\SupplementResource;
use App\Http\Resources\V1\SupplementSupplierResource;
use App\Jobs\SendNotification;
use App\Models\Supplement;
use App\Models\SupplementOrder;
use App\Models\SupplementSupplier;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group Supplements Management
 *
 * Supplements API resource
 */
class SupplementController extends Controller
{
    use HttpResponses;

    /**
     * Display a listing of supplements.
     */
    public function index(Request $request)
    {
        $per_page = $request->query('per_page');
        $search = $request->query('search');
        $supplier = $request->query('supplier');

        if (auth()->check()) {
            if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('supplements admin')) {
                $supplements = SupplementResource::collection(
                        Supplement::with('supplier', 'images')
                                    ->withCount('orders')
                                    ->when($search && $search != '', function ($query) use ($search) {
                                        $query->where('name', 'LIKE', '%'.$search.'%');
                                    })
                                    ->when($supplier && $supplier != '', function ($query) use ($supplier) {
                                        $query->whereHas('supplier', function ($query) use ($supplier) {
                                            $query->where('id', $supplier);
                                        });
                                    })
                                    ->paginate($per_page)
                    )->response()->getData(true);

                $suppliers = SupplementSupplierResource::collection(SupplementSupplier::all());

                return $this->success(['supplements' => $supplements, 'suppliers' => $suppliers]);
            } else {
                $supplements = SupplementResource::collection(Supplement::available()->with('supplier', 'images')->paginate($per_page))->response()->getData(true);

                return $this->success(['supplements' => $supplements]);
            }
        } else {
            $supplements = SupplementResource::collection(Supplement::available()->with('supplier', 'images')->paginate($per_page))->response()->getData(true);

            return $this->success(['supplements' => $supplements]);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => ['required', 'exists:supplement_suppliers,id'],
            'name' => ['required'],
            'price' => ['required'],
            'measuring_unit' => ['required', 'in:kilograms,pounds,litres'],
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['mimes:jpg,png']
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Invalid data', 400);
        }

        $supplement = Supplement::create([
            'supplement_supplier_id' => $request->supplier_id,
            'name' => $request->name,
            'price_per_quantity' => $request->price,
            'measuring_unit' => $request->measuring_unit,
            'description' => $request->has('description') && !empty($request->description) ? $request->description : NULL,
        ]);

        foreach ($request->images as $image) {
            $supplement->images()->create([
                'image' => pathinfo($image->store('', 'supplements'), PATHINFO_BASENAME)
            ]);
        }

        activity()->causedBy(auth()->user())->performedOn($supplement)->log('registered a new supplement');

        return $this->success(new SupplementResource($supplement));
    }

    /**
     * Display the specified supplement.
     */
    public function show(Supplement $supplement)
    {
        return $this->success(new SupplementResource($supplement));
    }

    public function update(Request $request, Supplement $supplement)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Invalid data', 400);
        }
    }

    public function destroy(Supplement $supplement)
    {
        // Check if supplement has orders

        // Force delete if no orders
        $supplement->forceDelete();

        return $this->success('', 'Supplement deleted successfully');
    }

    public function updateSupplementStatus(Supplement $supplement)
    {
        $supplement->update([
            'is_available' => !$supplement->is_available
        ]);

        return $this->success(new SupplementResource($supplement), 'Supplement updated successfully');
    }

    /**
     * List of all suppliers
     */
    public function suppliers(Request $request)
    {
        $per_page = $request->query('per_page');

        $suppliers = SupplementSupplierResource::collection(SupplementSupplier::with('supplements')->withCount('orders')->paginate($per_page))->response()->getData(true);

        return $this->success($suppliers);
    }

    public function storeSupplier(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'image' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Invalid data', 400);
        }

        $supplier = SupplementSupplier::create([
            'name' => $request->name,
            'location' => $request->has('location') && !empty($request->location) ? $request->location : NULL,
            'image' => pathinfo($request->image->store('suppliers', 'supplements'), PATHINFO_BASENAME)
        ]);

        activity()->causedBy(auth()->user())->performedOn($supplier)->log('registered a new supplement supplier');

        return $this->success(new SupplementSupplierResource($supplier));
    }

    public function updateSupplierStatus(SupplementSupplier $supplier)
    {
        $supplier->update([
            'status' => $supplier->status == 'active' ? 'inactive' : 'active',
        ]);

        return $this->success(new SupplementSupplierResource($supplier->load('supplements')));
    }

    /**
     * Supplement Orders
     */
    public function orders(Request $request)
    {
        $per_page = $request->query('per_page');

        if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('supplements admin')) {
            return $this->success(SupplementOrderResource::collection(SupplementOrder::with('supplement.supplier', 'user')->paginate($per_page))->response()->getData(true));
        } else {
            return $this->success(SupplementOrderResource::collection(SupplementOrder::with('supplement.supplier', 'user')->where('user_id', auth()->id())->paginate($per_page))->response()->getData(true));
        }
    }

    /**
     * Create a new supplement order
     * @bodyParam supplement_id int The ID of the supplement
     * @bodyParam quantity int The quantity of the order
     */
    public function storeOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplement_id' => ['required'],
            'quantity' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Invalid Data', 400);
        }

        $order = SupplementOrder::create([
            'user_id' => auth()->id(),
            'supplement_id' => $request->supplement_id,
            'quantity' => $request->quantity
        ]);

        return $this->success(new SupplementOrderResource($order), 'Order created successfully');
    }

    public function order(SupplementOrder $order)
    {
        return $this->success(new SupplementOrderResource($order->load('supplement.supplier')));
    }

    /**
     * Update Order Status
     * @urlParam id The ID of the order
     * @bodyParam status string required The status of the order(confirmed)
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:delivered,cancelled,on delivery,delivered,awaiting confirmation,confirmed']
        ]);

        if ($validator->failed()) {
            return $this->error($validator->messages(), 'Invalid Order Status', 400);
        }

        $order = SupplementOrder::find($id);

        if ($request->status == 'delivered') {
            // Send notification to user to confirm receipt of order
            SendNotification::dispatchAfterResponse($order->user, 'Order is delivered. Log in to confirm delivery', ['order' => $order]);

            $order->update([
                'status' => 'delivered'
            ]);
        } else if ($request->status == 'on delivery') {
            $order->update([
                'status' => 'on delivery'
            ]);

            // Send notification to user to confirm receipt of order
            SendNotification::dispatchAfterResponse($order->user, 'Order is en route to you.', ['order' => $order]);
        } else {
            if ($request->status == 'confirmed') {
                $order->update([
                    'status' => 'delivered'
                ]);
            } else {
                $order->update(['status' => $request->status]);
            }
        }

        $order->update([
            'expected_delivery_date' => $request->has('expected_delivery_date') && !empty($request->expected_delivery_date) && $request->expected_delivery_date != '' ? $request->expected_delivery_date : NULL,
            'courier_contact_name' => $request->has('courier_name') && !empty($request->courier_name) && $request->courier_name != '' ? $request->courier_name : NULL,
            'courier_contact_email' => $request->has('courier_email') && !empty($request->courier_email) && $request->courier_email != '' ? $request->courier_email : NULL,
            'courier_contact_phone' => $request->has('courier_phone') && !empty($request->courier_phone) && $request->courier_phone != '' ? $request->courier_phone : NULL,
        ]);

        return $this->success(new SupplementOrderResource($order->load('supplement.supplier')), 'Order Status updated successfully');
    }
}
