<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self pending()
 * @method static self approved()
 * @method static self denied()
 */
final class RestaurantStatus extends Enum
{
    protected static function values(): array
    {
        return [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'denied' => 'Denied',
        ];
    }
}
