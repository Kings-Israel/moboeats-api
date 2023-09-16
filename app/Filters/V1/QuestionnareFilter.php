<?php

namespace App\Filters\V1;

use Illuminate\Http\Request;
use App\Filters\ApiFilter;

class QuestionnareFilter extends ApiFilter {

    protected $safeParams = [
        'uuid' => ['eq'],
        'restaurantId' => ['eq'],
        'delivery' =>['eq'],
        'booking' =>['eq'],
        'status' => ['eq'],
        'createdBy' => ['eq'],
        'updatedBy' => ['eq'],
    ];

    protected $columnMap = [
        'restaurantId' => 'restaurant_id',
        'createdBy' => 'created_by',
        'updatedBy' => 'updated_by',
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