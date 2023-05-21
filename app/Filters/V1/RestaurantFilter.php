<?php

namespace App\Filters\V1;

use Illuminate\Http\Request;
use App\Filters\ApiFilter;

class RestaurantFilter extends ApiFilter {

    protected $safeParams = [
        'uuid' => ['eq'],
        'name' => ['eq'],
        'nameShort' =>['eq'],
        'email' =>['eq'],
        'about' =>['eq'],
        'aboutShort' => ['eq'],
        'phoneNo' => ['eq'],
        'address' => ['eq'],
        'city' => ['eq'],
        'state' => ['eq'],
        'mapLocation' =>['eq', 'gt', 'lt'],
        'url' => ['eq'],
        'logo' => ['eq'],
        'status' => ['eq'],
        'postalCode' => ['eq', 'gt', 'lt']
    ];

    protected $columnMap = [
        'nameShort' => 'name_short',
        'aboutShort' => 'about_short',
        'phoneNo' => 'phone_no',
        'mapLocation' => 'map_location',
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