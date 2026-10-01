<?php

namespace juniorE\ShoppingCart\Data\Repositories;

use Illuminate\Support\Collection;
use juniorE\ShoppingCart\Data\Interfaces\CartDatabase;
use juniorE\ShoppingCart\Models\Cart;
use juniorE\ShoppingCart\Models\CartCoupon;
use juniorE\ShoppingCart\Models\CartItem;

class EloquentCartDatabase implements CartDatabase
{
    public function createCart(string $identifier): Cart
    {
        return Cart::create([
            'identifier' => $identifier,
        ]);
    }

    /**
     * @return Cart|null
     */
    public function getCart(string $identifier)
    {
        return Cart::where('identifier', '=', $identifier)->first();
    }

    public function createCartItem(array $product): CartItem
    {
        if (isset($product['type']) && CartItem::isTaxable($product['type'])) {
            $taxAmount = ($product['price'] ?? 0) * ($product['tax_percent'] ?? 0);
        } else {
            $taxAmount = 0;
        }

        $cartItem = CartItem::create(
            collect($product)
                ->merge([
                    'tax_amount' => $taxAmount,
                ])
                ->toArray()
        );
        $this->updateTotal();

        return $cartItem;
    }

    /**
     * @return CartItem|null
     */
    public function getCartItem(int $id)
    {
        return CartItem::find($id);
    }

    /**
     * @return CartItem|null
     */
    public function getCartItemByHash(string $hash)
    {
        return CartItem::firstWhere('row_hash', $hash);
    }

    /**
     * @return Collection|CartItem[]
     */
    public function getCartItems(?int $cartIdentifier = null)
    {
        return CartItem::where('cart_id', $cartIdentifier ?: cart()->id)->get();
    }

    public function getCartItemsTree(?int $cartIdentifier = null): Collection
    {
        return CartItem::where('cart_id', $cartIdentifier ?: cart()->id)
            ->whereNull('parent_id')
            ->with('subproducts')
            ->get();
    }

    public function removeCartItem(CartItem $item): void
    {
        $item->delete();
    }

    public function setCheckoutMethod(string $method): void
    {
        cart()->getCart()->update([
            'checkout_method' => $method,
        ]);
    }

    public function setShippingMethod(string $method): void
    {
        cart()->getCart()->update([
            'shipping_method' => $method,
        ]);
    }

    public function setConversionTime(int $minutes): void
    {
        cart()->getCart()->update([
            'conversion_time' => $minutes,
        ]);
    }

    public function addCoupon(CartCoupon $coupon): void
    {
        cart()->getCart()->update([
            'coupon_code' => $coupon->name,
        ]);

        $this->updateTotal();
    }

    public function removeCoupon(): void
    {
        cart()->getCart()->update([
            'coupon_code' => null,
        ]);

        $this->updateTotal();
    }

    public function clear(bool $hard = false): void
    {
        if ($hard) {
            cart()->getCart()->forceDelete();
        } else {
            cart()->getCart()->delete();
        }
    }

    public function setAdditionalData(array $data): void
    {
        $cart = cart()->getCart();
        $cart->update([
            'additional' => collect($cart->additional)
                ->merge($data),
        ]);
    }

    public function removeShippingMethod(): void
    {
        $cart = cart()->getCart();
        $cart->update([
            'shipping_method' => null,
        ]);
        $this->updateTotal($cart->id);
    }

    /**
     * Totals are aggregated straight from the cart_items table: this runs on
     * every cart item create/update/delete (model events), and any in-memory
     * item snapshot may be stale by the time it fires.
     */
    public function updateTotal(?int $cartId = null): void
    {
        $cart = $cartId
            ? Cart::whereId($cartId)->first()
            : cart()->getCart();

        if (! $cart) {
            return;
        }

        $totals = $cart->prices_include_tax === false
            ? $this->netCartTotals($cart->id)
            : $this->cartTotals($cart->id);

        $discount = $this->totalDiscount($cart, $totals->item_discounts, $totals->discountable_total);

        $cart->update([
            'grand_total' => $totals->total - round($discount, 2) + cart($cart->identifier)->getDeliveryCost(),
            'tax_total' => $totals->total - $totals->sub_total,
            'sub_total' => $totals->sub_total,
            'discount' => round($discount, 2),
        ]);
    }

    /**
     * @return object{total: float, sub_total: float, item_discounts: float, discountable_total: float}
     */
    private function cartTotals(int $cartId)
    {
        [$taxableSql, $taxableBindings] = $this->typeNotExemptSql(config('shoppingcart.tax_exempt_types'));
        [$discountableSql, $discountableBindings] = $this->typeNotExemptSql(config('shoppingcart.discount_exempt_types'));

        $totals = CartItem::query()
            ->where('cart_id', $cartId)
            ->selectRaw('COALESCE(SUM(price * quantity), 0) as total')
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$taxableSql} THEN (price * quantity) / (1 + COALESCE(tax_percent, 0)) ELSE price * quantity END), 0) as sub_total",
                $taxableBindings
            )
            ->selectRaw('COALESCE(SUM(discount), 0) as item_discounts')
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$discountableSql} THEN total ELSE 0 END), 0) as discountable_total",
                $discountableBindings
            )
            ->first();

        $totals->total = (float) $totals->total;
        $totals->sub_total = (float) $totals->sub_total;
        $totals->item_discounts = (float) $totals->item_discounts;
        $totals->discountable_total = (float) $totals->discountable_total;

        return $totals;
    }

    /**
     * The totals of a cart whose prices exclude VAT: the lines are the
     * taxable base and each line's tax comes on top. Same shape as
     * cartTotals(), so updateTotal() derives tax_total as total − sub_total
     * in both modes.
     *
     * @return object{total: float, sub_total: float, item_discounts: float, discountable_total: float}
     */
    private function netCartTotals(int $cartId)
    {
        [$taxableSql, $taxableBindings] = $this->typeNotExemptSql(config('shoppingcart.tax_exempt_types'));
        [$discountableSql, $discountableBindings] = $this->typeNotExemptSql(config('shoppingcart.discount_exempt_types'));

        $totals = CartItem::query()
            ->where('cart_id', $cartId)
            ->selectRaw('COALESCE(SUM(price * quantity), 0) as sub_total')
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$taxableSql} THEN price * quantity * COALESCE(tax_percent, 0) ELSE 0 END), 0) as tax",
                $taxableBindings
            )
            ->selectRaw('COALESCE(SUM(discount), 0) as item_discounts')
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN {$discountableSql} THEN total ELSE 0 END), 0) as discountable_total",
                $discountableBindings
            )
            ->first();

        $totals->sub_total = (float) $totals->sub_total;
        $totals->total = $totals->sub_total + (float) $totals->tax;
        $totals->item_discounts = (float) $totals->item_discounts;
        $totals->discountable_total = (float) $totals->discountable_total;

        return $totals;
    }

    /**
     * SQL condition matching CartItem::isTaxable()/discountable(): the type
     * is not in the configured exempt list.
     *
     * @return array{0: string, 1: array}
     */
    private function typeNotExemptSql($exemptTypes): array
    {
        $types = collect($exemptTypes)->values();

        if ($types->isEmpty()) {
            return ['1 = 1', []];
        }

        $placeholders = $types->map(function () {
            return '?';
        })->implode(', ');

        return ["type not in ({$placeholders})", $types->all()];
    }

    private function totalDiscount(Cart $cart, float $itemDiscounts, float $discountableTotal)
    {
        $coupon = $cart->coupon;

        if (! $coupon) {
            return $itemDiscounts;
        }

        return $coupon->discount($discountableTotal, 0, 0.0, cart($cart->identifier)) + $itemDiscounts;
    }
}
