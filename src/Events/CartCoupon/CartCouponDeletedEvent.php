<?php

namespace juniorE\ShoppingCart\Events\CartCoupon;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use juniorE\ShoppingCart\Models\CartCoupon;

class CartCouponDeletedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var CartCoupon
     */
    public $item;

    /**
     * Create a new Event instance
     */
    public function __construct(CartCoupon $item)
    {
        $this->item = $item;
    }
}
