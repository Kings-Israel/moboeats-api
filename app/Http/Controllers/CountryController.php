<?php

namespace App\Http\Controllers;

use App\Http\Resources\V1\CountryResource;
use App\Models\Country;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * @group Countries
 *
 * Admin management of supported countries (`v1/admin/countries`).
 */
class CountryController extends Controller
{
    use HttpResponses;

    /**
     * List countries
     *
     * Returns countries, optionally filtered. Paginated only when `per_page` is given; otherwise all matching countries are returned.
     *
     * @queryParam per_page integer Number of items per page. When omitted, results are not paginated. Example: 15
     * @queryParam page integer Page number (with `per_page`). Example: 1
     * @queryParam name string Filter by name (partial match). Example: Ken
     * @queryParam region string Filter by exact region. Example: Africa
     * @queryParam status string Filter by status (`active` or `inactive`). Example: active
     *
     * @response 200 scenario="Not paginated" {"status": "Request was succesful", "message": null, "data": [{"id": 1, "name": "Kenya", "iso2": "KE", "iso3": "KEN", "numeric_code": "404", "phone_code": "254", "region": "Africa", "subregion": "Eastern Africa", "latitude": "-1.0000000", "longitude": "38.0000000", "population": 53771300, "area_km2": 580367, "status": "active"}]}
     * @response 200 scenario="Paginated (per_page given)" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "data": [{"id": 1, "name": "Kenya", "iso2": "KE", "iso3": "KEN", "numeric_code": "404", "phone_code": "254", "region": "Africa", "subregion": "Eastern Africa", "latitude": "-1.0000000", "longitude": "38.0000000", "population": 53771300, "area_km2": 580367, "status": "active"}],
     *     "links": {"first": "https://moboeats.com/api/v1/admin/countries?page=1", "last": "https://moboeats.com/api/v1/admin/countries?page=1", "prev": null, "next": null},
     *     "meta": {"current_page": 1, "from": 1, "last_page": 1, "links": [], "path": "https://moboeats.com/api/v1/admin/countries", "per_page": 15, "to": 1, "total": 1}
     *   }
     * }
     * @responseField data object[] Countries (or a paginated object when `per_page` is given).
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

    /**
     * Create a country
     *
     * Admin endpoint. Creates a country.
     *
     * @bodyParam name string required Country name (unique). Example: Kenya
     * @bodyParam iso2 string required ISO 3166-1 alpha-2 code (unique). Example: KE
     * @bodyParam iso3 string required ISO 3166-1 alpha-3 code (unique). Example: KEN
     * @bodyParam numeric_code string ISO numeric code. Example: 404
     * @bodyParam phone_code string International dialling code. Example: 254
     * @bodyParam region string Region. Example: Africa
     * @bodyParam subregion string Sub-region. Example: Eastern Africa
     * @bodyParam latitude number Latitude. Example: -1
     * @bodyParam longitude number Longitude. Example: 38
     * @bodyParam population integer Population. Example: 53771300
     * @bodyParam area_km2 integer Area in km². Example: 580367
     * @bodyParam status string One of: `active`, `inactive`. Example: active
     *
     * @response 201 scenario="Created" {"status": "Request was succesful", "message": "Country created successfully", "data": {"id": 1, "name": "Kenya", "iso2": "KE", "iso3": "KEN", "numeric_code": "404", "phone_code": "254", "region": "Africa", "subregion": "Eastern Africa", "status": "active", "created_at": "2025-05-17T10:00:00.000000Z", "updated_at": "2025-05-17T10:00:00.000000Z"}}
     * @response 400 scenario="Validation error" {"status": "Error has occured...", "message": "Invalid Data", "data": {"iso2": ["The iso2 has already been taken."]}}
     * @responseField data object The created country.
     */
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

    /**
     * Update a country
     *
     * Admin endpoint. Updates a country.
     *
     * @urlParam country integer required The numeric ID of the country. Example: 1
     * @bodyParam name string required Country name (unique). Example: Kenya
     * @bodyParam iso2 string required ISO 3166-1 alpha-2 code (unique). Example: KE
     * @bodyParam iso3 string required ISO 3166-1 alpha-3 code (unique). Example: KEN
     * @bodyParam numeric_code string ISO numeric code. Example: 404
     * @bodyParam phone_code string International dialling code. Example: 254
     * @bodyParam region string Region. Example: Africa
     * @bodyParam subregion string Sub-region. Example: Eastern Africa
     * @bodyParam latitude number Latitude. Example: -1
     * @bodyParam longitude number Longitude. Example: 38
     * @bodyParam population integer Population. Example: 53771300
     * @bodyParam area_km2 integer Area in km². Example: 580367
     * @bodyParam status string One of: `active`, `inactive`. Example: active
     *
     * @response 200 scenario="Updated" {"status": "Request was succesful", "message": "Country Updated Successfully", "data": {"id": 1, "name": "Kenya", "iso2": "KE", "iso3": "KEN", "numeric_code": "404", "phone_code": "254", "region": "Africa", "subregion": "Eastern Africa", "status": "active", "created_at": "2025-05-17T10:00:00.000000Z", "updated_at": "2025-05-17T10:00:00.000000Z"}}
     * @response 400 scenario="Validation error" {"status": "Error has occured...", "message": "Invalid Data", "data": {"name": ["The name field is required."]}}
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\Country] 99"}
     * @responseField data object The updated country.
     */
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
     * Delete a country
     *
     * Admin endpoint. Not implemented: the method does nothing and returns an empty response.
     *
     * @urlParam country integer required The numeric ID of the country. Example: 1
     *
     * @response 200 scenario="Empty body" ""
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\Country] 99"}
     */
    public function destroy(Country $country)
    {
        //
    }
}
