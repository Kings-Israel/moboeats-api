<?php

namespace App\Helpers;

class Paypal
{
    public static function env()
    {
        return config('paypal.mode');
    }

    public static function currency()
    {
        return config('paypal.currency');
    }

    public static function credentials()
    {
        return [
            'client_id' => config('paypal.'.self::env().'.client_id'),
            'client_secret' => config('paypal.'.self::env().'.client_secret'),
            'app_id' => config('paypal.'.self::env().'.app_id'),
        ];
    }
}
