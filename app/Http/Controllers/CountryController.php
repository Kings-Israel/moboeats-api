<?php

namespace App\Http\Controllers;

use App\Http\Resources\V1\CountryResource;
use App\Models\Country;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CountryController extends Controller
{
    use HttpResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $per_page = $request->query('per_page');
        $name = $request->query('name');
        $region = $request->query('region');
        $status = $request->query('status');

        $countries = Country::when($name && $name != '', function ($query) use ($name) {
            $query->where('name', 'LIKE', '%' . $name . '%');
        })
        ->when($region && $region != '', function ($query) use ($region) {
            $query->where('region', $region);
        })
        ->when($status && $status != '', function ($query) use ($status) {
            $query->where('status', $status);
        });

        if ($per_page) {
            $countries = CountryResource::collection($countries->paginate($per_page))->response()->getData();
        } else {
            $countries = CountryResource::collection($countries->get());
        }

        return $this->success($countries);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'unique:countries,name'],
            'iso2' => ['required', 'unique:countries,iso2'],
            'iso3' => ['required', 'unique:countries,iso3']
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Invalid Data', 400);
        }

        $country = Country::create($request->all());

        return $this->success($country, 'Country created successfully', 201);
    }

    public function update(Request $request, Country $country)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', Rule::unique('countries')->ignore($country->id)],
            'iso2' => ['required', Rule::unique('countries')->ignore($country->id)],
            'iso3' => ['required', Rule::unique('countries')->ignore($country->id)]
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Invalid Data', 400);
        }

        $country->update($request->all());

        return $this->success($country, 'Country Updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Country $country)
    {
        //
    }
}
