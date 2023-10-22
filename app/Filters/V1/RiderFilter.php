<?php

namespace App\Filters\V1;

use Illuminate\Http\Request;
use App\Filters\ApiFilter;

class RiderFilter extends ApiFilter {

    protected $safeParams = [
        'uuid' => ['eq'],
        'name' => ['eq'],
        'email' =>['eq'],
        'phoneNo' => ['eq'],
        'address' => ['eq'],
        'city' => ['eq'],
        'state' => ['eq'],
        'profilePicture' => ['eq'],
        'status' => ['eq'],
        'postalCode' => ['eq', 'gt', 'lt'],
        'vehicleType' => ['eq'],
        'vehicleLicensePlate' => ['eq'],
    ];

    protected $columnMap = [
        'profilePicture' => 'profile_picture',
        'vehicleType' => 'vehicle_type',
        'vehicleLicensePlate' => 'vehicle_license_plate',
        'phoneNo' => 'phone_no',
        'postalCode' => 'postal_code',
    ];

    protected $operatorMap = [
        'eq' => '=',
        'gt' => '>',
        'lt' => '<',
        'gte' => '>=',
        'lte' => '=<',
        'ne' => '!=',
    ];

}