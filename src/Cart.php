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
        public function addProduct(array $product, bool $forceNewLine = null): CartItem
        {
            if ($forceNewLine === null) {
                $forceNewLine = ! config('shoppingcart.merge_lines');
            }

            $product = collect($product)
                ->merge(['cart_id' => $this->id])
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
            $this->cartItems->filter(function ($cartItem) use ($item) {
                return $cartItem->id !== $item->id;
            });

            app(CartDatabase::class)->removeCartItem($item);
        }

        public function items(): Collection
        {
            return $this->cartItems;
        }

        public function empty()
        {
            $this->cartItems = collect();
            app(CartItemDatabase::class)->emptyCart($this->id);

            return $this;
        }

        public function addCoupon(CartCoupon $coupon): void
        {
            if ($coupon->ends_other_coupons && $this->getAllCouponsOnCart()->count() > 0) {
                return;
            }

            app(CartDatabase::class)->addCoupon($coupon);
        }

        public function removeCoupon(): void
        {
            app(CartDatabase::class)->removeCoupon();
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
            $database->setCheckoutMethod($checkoutMethod);
            $database->setConversionTime(now()->diffInMinutes($this->getCart()->created_at));
        }

        public function setShippingMethod(string $method): void
        {
            $cartDatabase = app(CartDatabase::class);
            $cartDatabase->setShippingMethod($method);
            $cartDatabase->updateTotal();
        }

        public function setAdditionalData(array $data)
        {
            app(CartDatabase::class)->setAdditionalData($data);
        }

        public function getCart(): Models\Cart
        {
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

        public function getDeliveryCost()
        {
            return $this->getCart()->shipping_method
                ? $this->getShippingRate()->price
                : 0;
        }

        public function getShippingRate()
        {
            $rates = $this->shippingRateRepository->shippingRatesForMethod($this->getCart()->shipping_method)
                ->where('minimum_cart_price', '<=', $this->getCart()->sub_total + $this->getCart()->tax_total)
                ->sortBy('minimum_cart_price');

            return $rates->last();
        }

        public function contains(array $plus): bool
        {
            $items = cart()->items()->map->only('plu')->flatten();
            $success = true;
            foreach ($plus as $plu) {
                if (! $items->contains($plu)) {
                    $success = false;

                    continue;
                } else {
                    $success = true;
                }
            }

            return $success;
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

            $this->cartItems = $this->getCart()->items;

            return $item;
        }

        private function createCartItem(array $product): CartItem
        {
            $database = app(CartDatabase::class);

            $cartItem = $database->createCartItem($product);
            $this->cartItems->push($cartItem);

            return $cartItem;
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
