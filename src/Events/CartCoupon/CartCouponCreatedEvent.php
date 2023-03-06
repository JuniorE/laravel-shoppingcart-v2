<?php


namespace juniorE\ShoppingCart\Events\CartCoupon;


use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use juniorE\ShoppingCart\Models\CartCoupon;

class CartCouponCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var CartCoupon $item
     */
    public $item;


    /**
     * Create a new Event instance
     *
     * @param CartCoupon $item
     */
    public function __construct(CartCoupon $item)
    {
        $this->item = $item;
    }
}
