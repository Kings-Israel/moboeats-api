<?php

namespace App\Filters\V1;

use Illuminate\Http\Request;
use App\Filters\ApiFilter;

class CartFilter extends ApiFilter {

    protected $safeParams = [
        'uuid' => ['eq'],
        'status' => ['eq'],
        'userId' => ['eq'],
    ];

    protected $columnMap = [
        'userId' => 'user_id',
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