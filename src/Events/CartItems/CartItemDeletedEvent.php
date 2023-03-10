<?php

namespace juniorE\ShoppingCart\Events\CartItems;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use juniorE\ShoppingCart\Models\CartItem;

class CartItemDeletedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var CartItem
     */
    public $item;

    /**
     * Create a new Event instance
     */
    public function __construct(CartItem $item)
    {
        $this->item = $item;
    }
}
