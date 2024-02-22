<?php

namespace App\Helpers;

class NumberGenerator
{
    public static function generateVerificationCode($model, $column) {
        $number = mt_rand(10000, 99999);

        if (self::verificationCodeExists($model, $number, $column)) {
            return generateVerificationCode($model, $column);
        }

        return $number;
    }

    public static function verificationCodeExists($model, $code, $column) {
        return $model::where($column, $code)->exists();
    }
}
