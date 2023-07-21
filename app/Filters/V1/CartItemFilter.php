<?php

namespace App\Filters\V1;

use Illuminate\Http\Request;
use App\Filters\ApiFilter;

class CartItemFilter extends ApiFilter {

    protected $safeParams = [
        'uuid' => ['eq'],
        'status' => ['eq'],
        'cartId' => ['eq'],
        'menuId' => ['eq'],
        'quantity' => ['eq', 'gt', 'lt'],
    ];

    protected $columnMap = [
        'menuId' => 'menu_id',
        'cartId' => 'cart_id',
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