<?php

namespace App\Filters\V1;

use Illuminate\Http\Request;
use App\Filters\ApiFilter;

class OrdererFilter extends ApiFilter {

    protected $safeParams = [
        'uuid' => ['eq'],
        'name' => ['eq'],
        'email' =>['eq'],
        'phoneNo' => ['eq'],
        'address' => ['eq'],
        'city' => ['eq'],
        'state' => ['eq'],
        'mapLocation' =>['eq', 'gt', 'lt'],
        'image' => ['eq'],
        'status' => ['eq'],
    ];

    protected $columnMap = [
        'phoneNo' => 'phone_no',
        'mapLocation' => 'map_location',
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