<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SupplementOrderResource;
use App\Http\Resources\V1\SupplementResource;
use App\Http\Resources\V1\SupplementSupplierResource;
use App\Jobs\SendNotification;
use App\Models\Supplement;
use App\Models\SupplementCategory;
use App\Models\SupplementOrder;
use App\Models\SupplementSupplier;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group Supplements
 *
 * Supplements, supplement categories, suppliers and supplement orders. Customer-facing
 * endpoints live under `v1/orderer/supplements`, admin endpoints under `v1/admin/supplements`.
 */
class SupplementController extends Controller
{
    use HttpResponses;

    /**
     * List supplements
     *
     * Returns a paginated list of supplements. Guests and regular users only see available supplements.
     * Authenticated users with role `admin` or `supplements admin` see all supplements (with `orders_count`),
     * can filter by `search`/`supplier`, and additionally receive the list of suppliers and categories.
     *
     * @queryParam per_page integer Number of items per page. Example: 15
     * @queryParam page integer Page number. Example: 1
     * @queryParam search string Admin only. Filter supplements whose name contains this text. Example: protein
     * @queryParam supplier integer Admin only. Filter by supplier ID. Example: 1
     *
     * @response 200 scenario="Customer / guest" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "supplements": {
     *       "data": [
     *         {
     *           "id": 1,
     *           "supplier": {"id": 1, "name": "HealthPlus Ltd", "location": "Nairobi", "description": null, "image": "https://moboeats.com/storage/supplements/suppliers/supplier.jpg"},
     *           "name": "Whey Protein",
     *           "supplement_category": {"id": 1, "name": "Proteins", "created_at": "2024-11-01T10:00:00.000000Z", "updated_at": "2024-11-01T10:00:00.000000Z"},
     *           "currency": "KSH",
     *           "price": 2500,
     *           "measuring_unit": "kilograms",
     *           "description": "Premium whey protein powder.",
     *           "images": [{"id": 1, "supplement_id": 1, "image": "https://moboeats.com/storage/supplements/whey.jpg", "created_at": "2024-11-01T10:00:00.000000Z", "updated_at": "2024-11-01T10:00:00.000000Z"}]
     *         }
     *       ],
     *       "links": {"first": "https://moboeats.com/api/v1/orderer/supplements?page=1", "last": "https://moboeats.com/api/v1/orderer/supplements?page=1", "prev": null, "next": null},
     *       "meta": {"current_page": 1, "from": 1, "last_page": 1, "links": [], "path": "https://moboeats.com/api/v1/orderer/supplements", "per_page": 15, "to": 1, "total": 1}
     *     }
     *   }
     * }
     * @response 200 scenario="Admin / supplements admin" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "supplements": {
     *       "data": [
     *         {"id": 1, "supplier": {"id": 1, "name": "HealthPlus Ltd", "location": "Nairobi", "description": null, "status": "active", "image": "https://moboeats.com/storage/supplements/suppliers/supplier.jpg"}, "name": "Whey Protein", "supplement_category": {"id": 1, "name": "Proteins", "created_at": "2024-11-01T10:00:00.000000Z", "updated_at": "2024-11-01T10:00:00.000000Z"}, "currency": "KSH", "price": 2500, "measuring_unit": "kilograms", "description": "Premium whey protein powder.", "is_available": true, "orders_count": 4, "images": []}
     *       ],
     *       "links": {"first": "https://moboeats.com/api/v1/admin/supplements?page=1", "last": "https://moboeats.com/api/v1/admin/supplements?page=1", "prev": null, "next": null},
     *       "meta": {"current_page": 1, "from": 1, "last_page": 1, "links": [], "path": "https://moboeats.com/api/v1/admin/supplements", "per_page": 15, "to": 1, "total": 1}
     *     },
     *     "suppliers": [{"id": 1, "name": "HealthPlus Ltd", "location": "Nairobi", "description": null, "status": "active", "image": "https://moboeats.com/storage/supplements/suppliers/supplier.jpg"}],
     *     "categories": [{"id": 1, "name": "Proteins", "created_at": "2024-11-01T10:00:00.000000Z", "updated_at": "2024-11-01T10:00:00.000000Z"}]
     *   }
     * }
     * @responseField data.supplements object Paginated supplements (data, links, meta).
     * @responseField data.suppliers object[] Admin only. All suppliers.
     * @responseField data.categories object[] Admin only. All supplement categories.
     */
    public function index(Request $request)
    {
        $per_page = $request->query('per_page');
        $search = $request->query('search');
        $supplier = $request->query('supplier');

        if (auth()->check()) {
            if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('supplements admin')) {
                $supplements = SupplementResource::collection(
                        Supplement::with('supplier', 'images', 'supplementCategory')
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

                $categories = SupplementCategory::all();

                return $this->success(['supplements' => $supplements, 'suppliers' => $suppliers, 'categories' => $categories]);
            } else {
                $supplements = SupplementResource::collection(Supplement::available()->with('supplier', 'images', 'supplementCategory')->paginate($per_page))->response()->getData(true);

                return $this->success(['supplements' => $supplements]);
            }
        } else {
            $supplements = SupplementResource::collection(Supplement::available()->with('supplier', 'images', 'supplementCategory')->paginate($per_page))->response()->getData(true);

            return $this->success(['supplements' => $supplements]);
        }
    }

    /**
     * Create a supplement
     *
     * Admin endpoint. Registers a new supplement with its images and logs the activity.
     *
     * @bodyParam supplier_id integer required ID of an existing supplement supplier. Example: 1
     * @bodyParam category_id integer required ID of the supplement category. Example: 1
     * @bodyParam name string required Supplement name. Example: Whey Protein
     * @bodyParam price number required Price per measuring unit. Example: 2500
     * @bodyParam measuring_unit string required One of: `kilograms`, `pounds`, `litres`. Example: kilograms
     * @bodyParam images file[] required At least one image (jpg or png).
     * @bodyParam description string Supplement description. Example: Premium whey protein powder.
     * @bodyParam prescription string Usage / prescription notes. Example: One scoop daily.
     * @bodyParam category string Free-text category label. Example: Fitness
     *
     * @response 200 scenario="Created" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {"id": 1, "name": "Whey Protein", "currency": "KSH", "price": 2500, "measuring_unit": "kilograms", "description": "Premium whey protein powder.", "is_available": null, "orders_count": null}
     * }
     * @response 400 scenario="Validation error" {
     *   "status": "Error has occured...",
     *   "message": "Invalid data",
     *   "data": {"supplier_id": ["The supplier id field is required."]}
     * }
     * @responseField data object The created supplement.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => ['required', 'exists:supplement_suppliers,id'],
            'category_id' => ['required'],
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
            'supplement_category_id' => $request->category_id,
            'name' => $request->name,
            'price_per_quantity' => $request->price,
            'measuring_unit' => $request->measuring_unit,
            'description' => $request->has('description') && !empty($request->description) ? $request->description : NULL,
            'prescription' => $request->has('prescription') && !empty($request->prescription) ? $request->prescription : NULL,
            'category' => $request->has('category') && !empty($request->category) ? $request->category : NULL,
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

    /**
     * Toggle supplement availability
     *
     * Admin endpoint. Flips the supplement's `is_available` flag.
     *
     * @urlParam supplement integer required The numeric ID of the supplement. Example: 1
     *
     * @response 200 scenario="Updated" {
     *   "status": "Request was succesful",
     *   "message": "Supplement updated successfully",
     *   "data": {"id": 1, "name": "Whey Protein", "currency": "KSH", "price": 2500, "measuring_unit": "kilograms", "description": "Premium whey protein powder.", "is_available": false, "orders_count": null}
     * }
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\Supplement] 99"}
     * @responseField data.is_available boolean The new availability state (shown to admins).
     */
    public function updateSupplementStatus(Supplement $supplement)
    {
        $supplement->update([
            'is_available' => !$supplement->is_available
        ]);

        return $this->success(new SupplementResource($supplement), 'Supplement updated successfully');
    }

    /**
     * List supplement suppliers
     *
     * Returns a paginated list of supplement suppliers with their supplements. Public on the orderer routes;
     * admins also see `status` and `orders_count`.
     *
     * @queryParam per_page integer Number of items per page. Example: 15
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "data": [
     *       {"id": 1, "name": "HealthPlus Ltd", "location": "Nairobi", "description": null, "supplements": [{"id": 1, "name": "Whey Protein", "currency": "KSH", "price": 2500, "measuring_unit": "kilograms", "description": "Premium whey protein powder."}], "image": "https://moboeats.com/storage/supplements/suppliers/supplier.jpg"}
     *     ],
     *     "links": {"first": "https://moboeats.com/api/v1/orderer/supplements/suppliers?page=1", "last": "https://moboeats.com/api/v1/orderer/supplements/suppliers?page=1", "prev": null, "next": null},
     *     "meta": {"current_page": 1, "from": 1, "last_page": 1, "links": [], "path": "https://moboeats.com/api/v1/orderer/supplements/suppliers", "per_page": 15, "to": 1, "total": 1}
     *   }
     * }
     * @responseField data.data object[] The suppliers on this page.
     * @responseField data.meta object Pagination metadata.
     */
    public function suppliers(Request $request)
    {
        $per_page = $request->query('per_page');

        $suppliers = SupplementSupplierResource::collection(SupplementSupplier::with('supplements')->withCount('orders')->paginate($per_page))->response()->getData(true);

        return $this->success($suppliers);
    }

    /**
     * Create a supplement supplier
     *
     * Admin endpoint. Registers a new supplier with an image and logs the activity.
     *
     * @bodyParam name string required Supplier name. Example: HealthPlus Ltd
     * @bodyParam image file required Supplier logo/image.
     * @bodyParam location string Supplier location. Example: Nairobi
     *
     * @response 200 scenario="Created" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {"id": 1, "name": "HealthPlus Ltd", "location": "Nairobi", "description": null, "status": null, "orders_count": null, "image": "https://moboeats.com/storage/supplements/suppliers/supplier.jpg"}
     * }
     * @response 400 scenario="Validation error" {
     *   "status": "Error has occured...",
     *   "message": "Invalid data",
     *   "data": {"name": ["The name field is required."]}
     * }
     * @responseField data object The created supplier.
     */
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

    /**
     * Toggle supplier status
     *
     * Admin endpoint. Switches the supplier's `status` between `active` and `inactive`.
     *
     * @urlParam supplier integer required The numeric ID of the supplier. Example: 1
     *
     * @response 200 scenario="Updated" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {"id": 1, "name": "HealthPlus Ltd", "location": "Nairobi", "description": null, "supplements": [], "status": "inactive", "orders_count": null, "image": "https://moboeats.com/storage/supplements/suppliers/supplier.jpg"}
     * }
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\SupplementSupplier] 99"}
     * @responseField data.status string The new status (`active` or `inactive`).
     */
    public function updateSupplierStatus(SupplementSupplier $supplier)
    {
        $supplier->update([
            'status' => $supplier->status == 'active' ? 'inactive' : 'active',
        ]);

        return $this->success(new SupplementSupplierResource($supplier->load('supplements')));
    }

    /**
     * List supplement orders
     *
     * Returns paginated supplement orders. Users with role `admin` or `supplements admin` see all orders;
     * other users see only their own orders.
     *
     * @queryParam per_page integer Number of items per page. Example: 15
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "data": [
     *       {
     *         "id": 1,
     *         "user": {"id": 5, "uuid": "9b1d3c2e-1a2b-4c5d-8e9f-0a1b2c3d4e5f", "name": "Jane Doe", "email": "jane@example.com", "phone_number": "254712345678", "role": null, "avatar": null, "relationships": {"orderer": null}, "type": "orderer", "referral_code": null, "is_guided": true},
     *         "supplement": {"id": 1, "supplier": {"id": 1, "name": "HealthPlus Ltd", "location": "Nairobi", "description": null, "image": "https://moboeats.com/storage/supplements/suppliers/supplier.jpg"}, "name": "Whey Protein", "currency": "KSH", "price": 2500, "measuring_unit": "kilograms", "description": "Premium whey protein powder."},
     *         "quantity": 2,
     *         "currency": "KSH",
     *         "amount": 5000,
     *         "status": "Awaiting Confirmation",
     *         "created_at": "2024-11-01T10:00:00.000000Z"
     *       }
     *     ],
     *     "links": {"first": "https://moboeats.com/api/v1/orderer/supplements/orders?page=1", "last": "https://moboeats.com/api/v1/orderer/supplements/orders?page=1", "prev": null, "next": null},
     *     "meta": {"current_page": 1, "from": 1, "last_page": 1, "links": [], "path": "https://moboeats.com/api/v1/orderer/supplements/orders", "per_page": 15, "to": 1, "total": 1}
     *   }
     * }
     * @responseField data.data object[] The supplement orders on this page.
     * @responseField data.meta object Pagination metadata.
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
     * Create a supplement order
     *
     * Places a supplement order for the authenticated user. Payment is done separately via
     * `GET v1/orderer/supplements/orders/{id}/pay`.
     *
     * @bodyParam supplement_id integer required The ID of the supplement. Example: 1
     * @bodyParam quantity integer required The quantity ordered. Example: 2
     *
     * @response 200 scenario="Created" {
     *   "status": "Request was succesful",
     *   "message": "Order created successfully",
     *   "data": {"id": 1, "quantity": 2, "currency": "KSH", "amount": 5000, "status": "Awaiting Confirmation", "created_at": "2024-11-01T10:00:00.000000Z"}
     * }
     * @response 400 scenario="Validation error" {
     *   "status": "Error has occured...",
     *   "message": "Invalid Data",
     *   "data": {"supplement_id": ["The supplement id field is required."]}
     * }
     * @responseField data.id integer The order ID.
     * @responseField data.amount number Total order amount.
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
     * Update supplement order status
     *
     * Admin endpoint. Updates the order status and courier details. Setting `delivered` or `on delivery`
     * sends a notification to the customer; `confirmed` is stored as `delivered`.
     *
     * @urlParam id integer required The numeric ID of the supplement order. Example: 1
     * @bodyParam status string required New status. One of: `delivered`, `cancelled`, `on delivery`, `awaiting confirmation`, `confirmed`. Example: on delivery
     * @bodyParam expected_delivery_date string Expected delivery date. Example: 2024-11-05
     * @bodyParam courier_name string Courier contact name. Example: John Rider
     * @bodyParam courier_email string Courier contact email. Example: courier@example.com
     * @bodyParam courier_phone string Courier contact phone. Example: 254700000000
     *
     * @response 200 scenario="Updated" {
     *   "status": "Request was succesful",
     *   "message": "Order Status updated successfully",
     *   "data": {"id": 1, "supplement": {"id": 1, "supplier": {"id": 1, "name": "HealthPlus Ltd", "location": "Nairobi", "description": null, "status": "active", "image": "https://moboeats.com/storage/supplements/suppliers/supplier.jpg"}, "name": "Whey Protein", "currency": "KSH", "price": 2500, "measuring_unit": "kilograms", "description": "Premium whey protein powder.", "is_available": true}, "quantity": 2, "currency": "KSH", "amount": 5000, "status": "On Delivery", "created_at": "2024-11-01T10:00:00.000000Z"}
     * }
     * @response 400 scenario="Invalid status" {
     *   "status": "Error has occured...",
     *   "message": "Invalid Order Status",
     *   "data": {"status": ["The selected status is invalid."]}
     * }
     * @responseField data.status string The updated order status (title case).
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

    /**
     * List supplement categories
     *
     * Returns a paginated list of supplement categories, each with its supplements.
     *
     * @queryParam search string Filter categories whose name contains this text. Example: protein
     * @queryParam perPage integer Number of items per page. Example: 15
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {"id": 1, "name": "Proteins", "created_at": "2024-11-01T10:00:00.000000Z", "updated_at": "2024-11-01T10:00:00.000000Z", "supplements": [{"id": 1, "supplement_supplier_id": 1, "supplement_category_id": 1, "name": "Whey Protein", "description": "Premium whey protein powder.", "is_available": true, "price_per_quantity": 2500, "measuring_unit": "kilograms"}]}
     *     ],
     *     "first_page_url": "https://moboeats.com/api/v1/orderer/supplements/categories?page=1",
     *     "from": 1,
     *     "last_page": 1,
     *     "last_page_url": "https://moboeats.com/api/v1/orderer/supplements/categories?page=1",
     *     "links": [],
     *     "next_page_url": null,
     *     "path": "https://moboeats.com/api/v1/orderer/supplements/categories",
     *     "per_page": 15,
     *     "prev_page_url": null,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     * @responseField data.data object[] Categories on this page with their supplements.
     */
    public function categories(Request $request)
    {
        $search = $request->query('search');
        $per_page = $request->query('perPage');

        $categories = SupplementCategory::with('supplements')
        ->when($search && $search != '', function ($query) use ($search) {
            $query->where('name', 'LIKE', '%'.$search.'%');
        })
        ->paginate($per_page);

        return $this->success($categories);
    }

    /**
     * Get a supplement category
     *
     * Returns a supplement category with its supplements.
     *
     * @urlParam supplement_category integer required The numeric ID of the category. Example: 1
     *
     * @response 200 {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {"id": 1, "name": "Proteins", "created_at": "2024-11-01T10:00:00.000000Z", "updated_at": "2024-11-01T10:00:00.000000Z", "supplements": [{"id": 1, "supplement_supplier_id": 1, "supplement_category_id": 1, "name": "Whey Protein", "description": "Premium whey protein powder.", "is_available": true, "price_per_quantity": 2500, "measuring_unit": "kilograms"}]}
     * }
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\SupplementCategory] 99"}
     * @responseField data.supplements object[] Supplements in this category.
     */
    public function category(SupplementCategory $supplement_category)
    {
        $supplement_category->load('supplements');

        return $this->success($supplement_category);
    }
}
