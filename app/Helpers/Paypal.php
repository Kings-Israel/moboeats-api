<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

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

    public static function url()
    {
        if (self::env() == 'live') {
            return 'https://api-m.paypal.com';
        } else {
            return 'https://api-m.sandbox.paypal.com';
        }
    }

    public static function credentials()
    {
        return [
            'client_id' => config('paypal.'.self::env().'.client_id'),
            'client_secret' => config('paypal.'.self::env().'.client_secret'),
            'app_id' => config('paypal.'.self::env().'.app_id'),
        ];
    }

    public static function token()
    {
        $credentials = base64_encode(config('paypal.'.self::env().'.client_id').':'.config('paypal.'.self::env().'.client_secret'));
        $token = Http::withHeaders([
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Authorization' => 'Basic '.$credentials,
                        'Accept' => 'application/json',
                    ])
                    ->asForm()
                    ->post(self::url().'/v1/oauth2/token', [
                            'grant_type' => 'client_credentials'
                        ]);

        return $token;
    }
}
