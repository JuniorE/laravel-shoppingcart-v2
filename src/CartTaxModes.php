<?php

namespace juniorE\ShoppingCart;

use Closure;

/**
 * Each cart's VAT mode (carts.prices_include_tax), read once per request.
 *
 * Every line a cart saves works its own VAT out and needs the mode; reading
 * it from the database per save cost a query per line, twice per save. The
 * mode only changes through the Cart model (setPricesIncludeTax(), a new
 * cart), whose saved and deleted events forget the cart here.
 *
 * Scoped, like CartRegistry: it starts empty for every request, queued job
 * and Octane request.
 */
class CartTaxModes
{
    /**
     * @var array<int, bool>
     */
    private $modes = [];

    /**
     * @param  Closure(): bool  $read
     */
    public function remember(int $cartId, Closure $read): bool
    {
        return $this->modes[$cartId] ??= $read();
    }

    public function forget(int $cartId): void
    {
        unset($this->modes[$cartId]);
    }
}
