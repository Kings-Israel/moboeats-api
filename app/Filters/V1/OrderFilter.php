<?php

namespace App\Filters\V1;

use Illuminate\Http\Request;
use App\Filters\ApiFilter;

class OrderFilter extends ApiFilter {

    protected $safeParams = [
        'uuid' => ['eq'],
        'status' => ['eq'],
        'userId' => ['eq'],
        'restaurantId' => ['eq'],
    ];

    protected $columnMap = [
        'userId' => 'user_id',
        'restaurantId' => 'restaurant_id',
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