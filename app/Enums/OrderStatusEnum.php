<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self denied()
 * @method static self pending()
 * @method static self in_progress()
 * @method static self awaiting_pick_up()
 * @method static self on_delivery()
 * @method static self delivered()
 */
final class OrderStatusEnum extends Enum
{
    protected static function values(): array
    {
        return [
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'awaiting_pick_up' => 'Awating Pick Up',
            'on_delivery' => 'On Delivery',
            'delivered' => 'Delivered',
            'denied' => 'Denied',
        ];
    }
}
