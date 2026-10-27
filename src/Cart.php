<?php

namespace juniorE\ShoppingCart;

use Illuminate\Support\Collection;
use juniorE\ShoppingCart\Data\Interfaces\CartCouponDatabase;
use juniorE\ShoppingCart\Data\Interfaces\CartDatabase;
use juniorE\ShoppingCart\Data\Interfaces\CartItemDatabase;
use juniorE\ShoppingCart\Data\Interfaces\VisitsHistoryDatabase;
use juniorE\ShoppingCart\Models\CartCoupon;
use juniorE\ShoppingCart\Models\CartItem;

class Cart extends BaseCart
{
    public function addProduct(array $product, ?bool $forceNewLine = null): CartItem
    {
        if ($forceNewLine === null) {
            $forceNewLine = ! config('shoppingcart.merge_lines');
        }

        $product = collect($product)
            ->merge(['cart_id' => $this->liveCartId()])
            ->toArray();

        if ($forceNewLine) {
            return $this->createCartItem($product);
        }

        return $this->updateOrCreateCartItem($product);
    }

    public function addProducts(...$products): Collection
    {
        $items = collect();
        foreach ($products as $product) {
            $items->push($this->addProduct($product, ! config('shoppingcart.merge_lines')));
        }

        return $items;
    }

    public function removeItem(CartItem $item): void
    {
        app(CartDatabase::class)->removeCartItem($item);
    }

    /**
     * Always read from the database: cart items are mutated through
     * freshly fetched models (price checks, quantity updates, model
     * events), so an instance-lifetime snapshot goes stale — and with
     * the per-request registry, instances live for the whole request.
     */
    public function items(): Collection
    {
        return $this->getCart()->items;
    }

    public function empty()
    {
        app(CartItemDatabase::class)->emptyCart($this->id);

        return $this;
    }

    public function addCoupon(CartCoupon $coupon): void
    {
        if ($coupon->ends_other_coupons && $this->getAllCouponsOnCart()->count() > 0) {
            return;
        }

        app(CartDatabase::class)->addCoupon($coupon, $this->liveCartId());
    }

    public function removeCoupon(): void
    {
        app(CartDatabase::class)->removeCoupon($this->liveCartId());
    }

    /**
     * @return Collection|CartCoupon[]|null
     */
    public function getAllCouponsOnCart()
    {
        return app(CartCouponDatabase::class)->getCoupons(self::getCart()->id);
    }

    public function setCheckoutMethod(string $checkoutMethod): void
    {
        $database = app(CartDatabase::class);
        $cart = $this->getCart();
        $database->setCheckoutMethod($checkoutMethod, $cart->id);
        // Diffed from created_at forward: Carbon 3 diffs are signed, so the
        // old now()->diffInMinutes(created_at) stored negative minutes.
        $database->setConversionTime((int) $cart->created_at->diffInMinutes(now()), $cart->id);
    }

    public function setShippingMethod(string $method): void
    {
        $cartDatabase = app(CartDatabase::class);
        $cartId = $this->liveCartId();
        $cartDatabase->setShippingMethod($method, $cartId);
        $cartDatabase->updateTotal($cartId);
    }

    public function setAdditionalData(array $data)
    {
        app(CartDatabase::class)->setAdditionalData($data, $this->liveCartId());
    }

    public function pricesIncludeTax(): bool
    {
        return Models\Cart::pricesIncludeTaxFor($this->getCart()->id);
    }

    public function setPricesIncludeTax(bool $include): void
    {
        $cart = $this->getCart();

        if (Models\Cart::pricesIncludeTaxFor($cart->id) === $include) {
            return;
        }

        if ($cart->items()->exists()) {
            throw new \LogicException(
                'A cart with lines keeps the VAT mode its lines were priced in; empty it before switching.'
            );
        }

        // A basket emptied line by line still carries the coupon and the
        // delivery method it was priced with, and totals that include them;
        // neither belongs to the other mode.
        $cart->update(['prices_include_tax' => $include, 'coupon_code' => null, 'shipping_method' => null]);
        app(CartDatabase::class)->updateTotal($cart->id);
    }

    public function getCart(): Models\Cart
    {
        $cart = app(CartDatabase::class)->getCart($this->identifier);

        if ($cart) {
            return $cart;
        }

        // The cart row vanished underneath this instance (nightly
        // clean): recreate it under its own identifier, like a freshly
        // constructed cart() would.
        $this->create($this->identifier);

        return app(CartDatabase::class)->getCart($this->identifier);
    }

    public function history()
    {
        return $this->getCart()->history;
    }

    public function markVisited(string $plu): void
    {
        app(VisitsHistoryDatabase::class)->markVisited($plu);
    }

    public function updateIdentifier(string $identifier): void
    {
        $this->getCart()->update([
            'identifier' => $identifier,
        ]);

        $this->identifier = $identifier;
        session()->put(self::SESSION_CART_IDENTIFIER, $identifier);
    }

    /**
     * What the cart's shipping method costs it: nothing without a method,
     * and nothing when the cart reaches none of the method's rates (an
     * application with its own tier rule decides what that means).
     */
    public function getDeliveryCost()
    {
        $cart = $this->getCart();

        return $cart->shipping_method
            ? ($this->shippingRateFor($cart)?->price ?? 0)
            : 0;
    }

    public function getShippingRate()
    {
        return $this->shippingRateFor($this->getCart());
    }

    /**
     * The highest rate of the cart's method that its total reaches, read
     * off the row the caller already holds: every updateTotal() asks.
     */
    private function shippingRateFor(Models\Cart $cart)
    {
        return $this->shippingRateRepository->shippingRatesForMethod($cart->shipping_method)
            ->where('minimum_cart_price', '<=', $cart->sub_total + $cart->tax_total)
            ->sortBy('minimum_cart_price')
            ->last();
    }

    public function contains(array $plus): bool
    {
        // THIS cart's items, from the database — the session-bound
        // cart() helper pointed webhook/queue callers at the wrong cart,
        // and an in-memory snapshot misses the item whose create-event
        // is being handled right now.
        $items = $this->getItems($this->id)->map->only('plu')->flatten();

        foreach ($plus as $plu) {
            if (! $items->contains($plu)) {
                return false;
            }
        }

        return true;
    }

    private function updateOrCreateCartItem(array $product): CartItem
    {
        $database = app(CartDatabase::class);

        $hash = CartItem::getHash($product);

        $existingCartItem = $database->getCartItemByHash($hash);

        if ($existingCartItem) {
            return $this->updateQuantity($existingCartItem, $existingCartItem->quantity + ($product['quantity'] ?? 0));
        } else {
            return $this->createCartItem($product);
        }
    }

    private function updateQuantity(CartItem $item, $quantity): CartItem
    {
        $this->itemsRepository->setQuantity($item, $quantity);

        return $item;
    }

    /**
     * The cart row can vanish underneath a live instance (nightly
     * clean); recreate before using the id so new items land in a
     * cart that exists.
     */
    private function liveCartId(): int
    {
        if (! app(CartDatabase::class)->getCart($this->identifier)) {
            $this->create($this->identifier);
        }

        return $this->id;
    }

    private function createCartItem(array $product): CartItem
    {
        return app(CartDatabase::class)->createCartItem($product);
    }

    /**
     * @return Collection|CartItem[]
     */
    public function itemsTree(): Collection
    {
        return app(CartDatabase::class)->getCartItemsTree($this->id);
    }

    public function merge(Contracts\Cart $other): Contracts\Cart
    {
        // Lines are copied with their prices; a gross line in a net cart
        // (or the reverse) would be taxed wrongly from then on.
        if ($this->pricesIncludeTax() !== $other->pricesIncludeTax()) {
            throw new \LogicException(
                'Cannot merge a cart whose prices include VAT with one whose prices exclude it.'
            );
        }

        $other->itemsTree()->each(function ($item) {
            $this->addItem($item);
        });

        return $this;
    }

    private function addItem(CartItem $product, ?int $parent = null)
    {
        // add this product (insert parent id if exists)
        $newParent = $this->addProduct(
            collect([
                'parent_id' => $parent ?? $product->parent_id,
                'additional' => $product->additional ?? [],
            ])
                ->merge(
                    collect($product->getAttributes())
                        ->except(['id', 'additional', 'parent_id'])
                )
                ->toArray());

        $product->subproducts->each(function ($item) use ($newParent) {
            // add subproducts
            $this->addItem($item, $newParent->id);
        });
    }
}
