<?php


namespace juniorE\ShoppingCart\Events\CartShippingRate;


use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use juniorE\ShoppingCart\Models\CartShippingRate;

class CartShippingRateDeletedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var CartShippingRate $item
     */
    public $item;


    /**
     * Create a new Event instance
     *
     * @param CartShippingRate $item
     */
    public function __construct(CartShippingRate $item)
    {
        $this->item = $item;
    }
}
