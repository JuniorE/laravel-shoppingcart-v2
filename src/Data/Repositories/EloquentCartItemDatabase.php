<?php


namespace juniorE\ShoppingCart\Data\Repositories;


use Closure;
use juniorE\ShoppingCart\Data\Interfaces\CartDatabase;
use juniorE\ShoppingCart\Data\Interfaces\CartItemDatabase;
use juniorE\ShoppingCart\Models\CartItem;

class EloquentCartItemDatabase implements CartItemDatabase
{
    public function emptyCart(int $id): void
    {
        CartItem::where('cart_id', $id)->delete();
        app(CartDatabase::class)->removeCoupon();
        app(CartDatabase::class)->removeShippingMethod();
        app(CartDatabase::class)->updateTotal($id);
    }

    public function setQuantity(CartItem $item, float $quantity, $updateSubproducts=false): void
    {
        $old = $item->quantity;
        $item->update([
            'quantity' => $quantity
        ]);

        if ($updateSubproducts instanceof Closure) {
            $this->updateSubproducts($item, $quantity / $old, $updateSubproducts);
        } elseif ($updateSubproducts) {
            $this->updateSubproducts($item, $quantity / $old);
        }
    }

    public function setParentCartItem(CartItem $item, int $parentId): void
    {
        $item->update([
            "parent_id" => $parentId
        ]);
    }

    public function setTaxPercent(CartItem $item, float $percent): void
    {
        $item->update([
            "tax_percent" => $percent,
            "tax_amount" => $item->price * $percent
        ]);
    }

    public function setCouponCode(CartItem $item, string $code): void
    {
        $item->update([
            "coupon_code" => $code
        ]);

        $this->updatePrices($item);
    }

    public function setPrice(CartItem $item, float $price): void
    {
        $item->update([
            "price" => $price,
            "tax_amount" => $price * $item->tax_percent
        ]);
    }

    public function setWeight(CartItem $item, float $weight): void
    {
        $item->update([
            "weight" => $weight
        ]);
    }

    public function setPLU(CartItem $item, string $plu): void
    {
        if (!$plu) {
            return;
        }

        $item->update([
            "plu" => $plu
        ]);
    }

    public function setAdditionalData(CartItem $item, array $data): void
    {
        $item->update([
            'additional' => collect($item->additional)
                ->merge($data)
        ]);
    }

    private function updatePrices(CartItem $item)
    {
        if (!$item->coupon) {
            return;
        }

        $discount = $item->coupon->discount($item->price * $item->quantity, $item->quantity, $item->price);
        $item->update([
            "discount" => $discount
        ]);

        app(CartDatabase::class)->updateTotal();
    }

    private function updateSubproducts(CartItem $item, float $multiplier, Closure $shouldUpdate=null)
    {
        $item->subproducts->map(function(CartItem $subproduct) use ($multiplier, $shouldUpdate) {
            if (($shouldUpdate && $shouldUpdate($subproduct))
                || $shouldUpdate === null) {
                $subproduct->quantity = $subproduct->quantity * $multiplier;
            }
            $subproduct->save();
        });
    }
}
