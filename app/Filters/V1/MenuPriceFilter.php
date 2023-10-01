<?php

namespace App\Filters\V1;

use Illuminate\Http\Request;
use App\Filters\ApiFilter;

class MenuPriceFilter extends ApiFilter {

    protected $safeParams = [
        'uuid' => ['eq'],
        'menuId' => ['eq'],
        'price' => ['eq'],
        'description' =>['eq'],
        'status' => ['eq'],
        'createdBy' => ['eq'],
        'updatedBy' => ['eq'],
    ];

    protected $columnMap = [
        'createdBy' => 'created_by',
        'createdBy' => 'created_by',
        'menuId' => 'menu_id',
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