<?php

namespace juniorE\ShoppingCart;

/**
 * Per-request cache of Cart instances by identifier. The cart() helper used
 * to build a fresh Cart on every call — and every construction restores the
 * cart from the database, so hot paths like the model-event total updates
 * re-ran the same restore queries many times per request.
 *
 * Registered as a scoped singleton, so the cache resets between requests,
 * queued jobs and Octane requests.
 */
class CartRegistry
{
    /**
     * @var array<string, Cart>
     */
    private $carts = [];

    public function get(?string $identifier = null): Cart
    {
        $key = $identifier ?? session(BaseCart::SESSION_CART_IDENTIFIER);

        // The identifier recheck drops entries whose cart was renamed after
        // construction (updateIdentifier on login merge, destroy): a caller
        // asking for the old name gets the same fresh-build behaviour as
        // before the registry existed.
        if ($key !== null && isset($this->carts[$key]) && $this->carts[$key]->identifier === $key) {
            // Constructing a cart writes its identifier to the session;
            // a cache hit keeps that side effect.
            session()->put(BaseCart::SESSION_CART_IDENTIFIER, $key);

            return $this->carts[$key];
        }

        $cart = new Cart($identifier);

        return $this->carts[$cart->identifier] = $cart;
    }
}
