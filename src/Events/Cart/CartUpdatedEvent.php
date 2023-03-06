<?php


namespace juniorE\ShoppingCart\Events\Cart;


use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use juniorE\ShoppingCart\Models\Cart;

class CartUpdatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Cart $item
     */
    public $item;


    /**
     * Create a new Event instance
     *
     * @param Cart $item
     */
    public function __construct(Cart $item)
    {
        $this->item = $item;
    }
}
