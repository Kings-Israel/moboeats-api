<?php

namespace App\Filters\V1;

use Illuminate\Http\Request;
use App\Filters\ApiFilter;

class FoodCommonCategoryFilter extends ApiFilter {

    protected $safeParams = [
        'uuid' => ['eq'],
        'title' => ['eq'],
        'description' =>['eq'],
        'status' => ['eq'],
        'createdBy' => ['eq'],
        'updatedBy' => ['eq'],
    ];

    protected $columnMap = [
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