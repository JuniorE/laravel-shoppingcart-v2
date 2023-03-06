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
            "identifier" => $identifier
        ]);
    }

    /**
     * @param string $identifier
     * @return Cart|null
     */
    public function getCart(string $identifier)
    {
        return Cart::where('identifier', "=", $identifier)->first();
    }

    public function createCartItem(array $product): CartItem
    {
        if (isset($product["type"]) && CartItem::isTaxable($product["type"])) {
            $taxAmount = ($product["price"] ?? 0) * ($product["tax_percent"] ?? 0);
        } else {
            $taxAmount = 0;
        }

        $cartItem = CartItem::create(
            collect($product)
                ->merge([
                    "tax_amount" => $taxAmount
                ])
                ->toArray()
        );
        $this->updateTotal();
        return $cartItem;
    }

    /**
     * @param int $id
     * @return CartItem|null
     */
    public function getCartItem(int $id)
    {
        return CartItem::find($id);
    }

    /**
     * @param string $hash
     * @return CartItem|null
     */
    public function getCartItemByHash(string $hash)
    {
        return CartItem::firstWhere('row_hash', $hash);
    }

    /**
     * @param int|null $cartIdentifier
     * @return Collection|CartItem[]
     */
    public function getCartItems(int $cartIdentifier=null)
    {
        return CartItem::where('cart_id', $cartIdentifier ?: cart()->id)->get();
    }

    public function getCartItemsTree(int $cartIdentifier=null): Collection
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
            "checkout_method" => $method
        ]);
    }

    public function setShippingMethod(string $method): void
    {
        cart()->getCart()->update([
            "shipping_method" => $method
        ]);
    }

    public function setConversionTime(int $minutes): void
    {
        cart()->getCart()->update([
            "conversion_time" => $minutes
        ]);
    }

    public function addCoupon(CartCoupon $coupon): void {
        cart()->getCart()->update([
            "coupon_code" => $coupon->name
        ]);

        $this->updateTotal();
    }

    public function removeCoupon(): void
    {
        cart()->getCart()->update([
            "coupon_code" => null,
        ]);

        $this->updateTotal();
    }

    public function clear(bool $hard=false): void
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
                ->merge($data)
        ]);
    }

    public function removeShippingMethod(): void
    {
        $cart = cart()->getCart();
        $cart->update([
            "shipping_method" => null,
        ]);
        $this->updateTotal($cart->id);
    }

    public function updateTotal(int $cartId=null): void
    {
        if (!$cartId) {
            $cart = cart();
        } else {
            $cart = cart(Cart::whereId($cartId)->first()->identifier);
        }

        $total = $cart->items()->reduce(function($carry, CartItem $item) {
            return $carry + $item->price * $item->quantity;
        }, 0);

        $subtotal = $cart->items()->reduce(function($carry, CartItem $item) {
            if ($item->taxable()) {
                return $carry + ($item->price * $item->quantity) / (1 + $item->tax_percent);
            } else {
                return $carry + ($item->price * $item->quantity);
            }
        }, 0);

        $taxes = $total - $subtotal;

        $discount = $this->totalDiscount();

        $cart->getCart()->update([
            "grand_total" => $total - round($discount, 2) + $cart->getDeliveryCost(),
            "tax_total" => $taxes,
            "sub_total" => $subtotal,
            "discount" => round($discount, 2)
        ]);
    }

    private function totalDiscount()
    {
        $cart = cart();
        $coupon = $cart->getCart()->coupon;

        $itemDiscounts = $cart->items()->reduce(function($carry, CartItem $item) {
            return $carry + $item->discount;
        }, 0);

        if ($coupon) {
            $total = $cart->items()->reduce(function($carry, CartItem $item) {
                if (!$item->discountable()) {
                    return $carry;
                }
                return $carry + $item->total;
            });

            return $coupon->discount($total) + $itemDiscounts;
        }
        return 0 + $itemDiscounts;
    }
}
