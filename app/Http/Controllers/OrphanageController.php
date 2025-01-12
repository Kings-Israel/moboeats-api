<?php

namespace App\Http\Controllers;

use App\Http\Resources\V1\OrphanageResource;
use App\Models\Orphanage;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrphanageController extends Controller
{
    use HttpResponses;

    /**
     * Listing of the orphanages.
     */
    public function index(Request $request)
    {
        $per_page = $request->query('per_page');

        if (!auth()->check()) {
            $data = Orphanage::approved()->paginate($per_page);
        } else {
            if (auth()->user()->hasRole('orderer') || auth()->user()->hasRole('restaurant') || auth()->user()->hasRole('restaurant employee')) {
                $data = Orphanage::approved()->paginate($per_page);
            } else {
                $data = Orphanage::paginate($per_page);
            }
        }

        $data = OrphanageResource::collection($data)->response()->getData();

        return $this->success($data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'email' => ['required_without:phone_number', 'email'],
            'phone_number' => ['required_without:email'],
            'contact_name' => ['required'],
            'contact_email' => ['required_without:contact_phone_number'],
            'contact_phone_number' => ['required_without:contact_email'],
            'location' => ['required'],
            'location_lat' => ['required'],
            'location_long' => ['required'],
            'logo' => ['required', 'mimes:jpg,png', 'max:5000']
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Invalid Data', 422);
        }

        $orphanage = Orphanage::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'contact_name' => $request->contact_name,
            'contact_email' => $request->contact_email,
            'contact_phone_number' => $request->contact_phone_number,
            'location' => $request->location,
            'location_lat' => $request->location_lat,
            'location_long' => $request->location_long,
            'logo' => $request->hasFile('logo') ? pathinfo($request->logo->store('logo', 'orphanages'), PATHINFO_BASENAME) : NULL,
            'status' => auth()->check() && auth()->user()->hasPermission('create orphanages') ? 'approved' : 'pending',
            'created_by' => auth()->check() ? auth()->id() : NULL
        ]);

        if ($request->wantsJson()) {
            return $this->success($orphanage);
        }

        return view('orphanage-added', ['orpanage' => $orphanage]);
    }

    /**
     * Show an Orphanage.
     */
    public function show(Orphanage $orphanage)
    {
        $orphanage->load('orders.user');

        return $this->success(new OrphanageResource($orphanage));
    }

    public function update(Request $request, Orphanage $orphanage)
    {
        if (!auth()->user()->hasPermission('edit orphanages')) {
            return $this->error('', 'You are not authorized to perform this action', 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'email' => ['required_without:phone_number', 'email'],
            'phone_number' => ['required_without:email'],
            'contact_email' => ['required_without:contact_phone_number'],
            'contact_phone_number' => ['required_without:contact_email'],
            'location' => ['required'],
            'location_lat' => ['required'],
            'location_long' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Invalid Data', 422);
        }

        $orphanage->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'contact_name' => $request->contact_name,
            'contact_email' => $request->contact_email,
            'contact_phone_number' => $request->contact_phone_number,
            'location' => $request->has('location') && !empty($request->location) ? $request->location : $orphanage->location,
            'location_lat' => $request->has('location_lat') && !empty($request->location_lat) ? $request->location_lat : $orphanage->location_lat,
            'location_long' => $request->has('location_long') && !empty($request->location_long) ? $request->location_long : $orphanage->location_long,
            'status' => auth()->check() && auth()->user()->hasRole('admin') ? 'approved' : 'pending',
            'logo' => $request->hasFile('logo') ? pathinfo($request->logo->store('logo', 'orphanages'), PATHINFO_BASENAME) : $orphanage->logo,
        ]);

        return $this->success($orphanage, 'Orphanage updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Orphanage $orphanage)
    {
        if ($orphanage->orders->count() > 0) {
            return $this->error('', 'Orphanage has orders', 401);
        }

        $orphanage->delete();

        return $this->success('', 'Orphanage Deleted Successfully');
    }

    public function updateStatus(Orphanage $orphanage, Request $request)
    {
        if (!auth()->user()->hasPermission('edit orphanages')) {
            return $this->error('', 'You cannot perform this action', 401);
        }

        $orphanage->update([
            'status' => $request->status,
            'rejection_reason' => $request->rejection_reason
        ]);

        return $this->success($orphanage, 'Updated Orhpanage successfully');
    }
}
