<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use juniorE\ShoppingCart\Data\Interfaces\CartDatabase;
use juniorE\ShoppingCart\Enums\ItemTypes;
use juniorE\ShoppingCart\Models\CartItem;
use juniorE\ShoppingCart\Tests\TestCase;

class CartItemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function can_set_parent_product()
    {
        $parent = cart()->addProduct([
            'plu' => 5,
        ]);

        $sub1 = cart()->addProduct([
            'plu' => 6,
        ]);

        $sub2 = cart()->addProduct([
            'plu' => 7,
        ]);

        cart()->itemsRepository->setParentCartItem($sub1, $parent->id);
        cart()->itemsRepository->setParentCartItem($sub2, $parent->id);

        $this->assertEquals($parent->id, $sub1->parent->id);
        $this->assertEquals($parent->id, $sub2->parent->id);

        $this->assertCount(2, $parent->subproducts);
        $this->assertEquals($sub1->id, $parent->subproducts->first()->id);
        $this->assertEquals($sub2->id, $parent->subproducts->last()->id);
    }

    /**
     * @test
     */
    public function can_set_additional_data()
    {
        $product = cart()->addProduct([
            'plu' => 5,
        ]);

        cart()->itemsRepository->setAdditionalData($product, [
            'unit' => 'kilogram',

        ]);

        cart()->itemsRepository->setAdditionalData($product, [
            'key' => 'value',
        ]);

        $this->assertEquals('kilogram', $product->additional['unit']);
        $this->assertEquals('value', $product->additional['key']);

        cart()->itemsRepository->setAdditionalData($product, [
            'unit' => 'person',

        ]);

        $this->assertEquals('person', $product->additional['unit']);
    }

    /**
     * @test
     */
    public function can_set_price()
    {
        $product = cart()->addProduct([
            'plu' => 5,
            'price' => 10,
        ]);

        $this->assertEquals(10, $product->price);

        cart()->itemsRepository->setPrice($product, 15);

        $this->assertEquals(15, $product->price);
    }

    /**
     * @test
     */
    public function can_update_weight()
    {
        $product = cart()->addProduct([
            'plu' => 5,
            'weight' => 10,
        ]);

        $this->assertEquals(10, $product->weight);

        cart()->itemsRepository->setWeight($product, 15);

        $this->assertEquals(15, $product->weight);
    }

    /**
     * @test
     */
    public function can_update_tax_percent()
    {
        $product = cart()->addProduct([
            'plu' => 5,
            'tax_percent' => 0.21,
        ]);

        $this->assertEquals(0.21, $product->tax_percent);

        cart()->itemsRepository->setTaxPercent($product, 0.06);

        $this->assertEquals(0.06, $product->tax_percent);
    }

    /**
     * @test
     */
    public function does_tax_amount_get_updated_automatically()
    {
        $product = cart()->addProduct([
            'plu' => 5,
            'tax_percent' => 0.21,
            'quantity' => 1,
        ], true);

        $product2 = cart()->addProduct([
            'plu' => 5,
            'tax_percent' => 0.21,
            'price' => 10,
            'quantity' => 1,
        ], true);

        $this->assertEquals(0, $product->tax_amount);
        $this->assertEqualsWithDelta(1.74, $product2->tax_amount, .005);

        cart()->itemsRepository->setTaxPercent($product, 0.06);
        cart()->itemsRepository->setTaxPercent($product2, 0.06);

        $this->assertEquals(0, $product->tax_amount);
        $this->assertEqualsWithDelta(0.57, $product2->tax_amount, .005);

        cart()->itemsRepository->setPrice($product, 5);
        cart()->itemsRepository->setPrice($product2, 100);

        $this->assertEqualsWithDelta(0.28, $product->tax_amount, 0.005);
        $this->assertEqualsWithDelta(5.66, $product2->tax_amount, 0.005);
    }

    /**
     * @test
     */
    public function can_update_plu()
    {
        $product = cart()->addProduct([
            'plu' => 5,
        ]);

        $this->assertEquals(5, $product->plu);

        cart()->itemsRepository->setPLU($product, 10);

        $this->assertEquals(10, $product->plu);

        cart()->itemsRepository->setPLU($product, '');

        $this->assertEquals(10, $product->plu);
    }

    /**
     * @test
     */
    public function does_not_throw_errors_when_cart_item_doesnt_exist()
    {
        try {
            $this->assertCount(0, CartItem::all());
            $this->assertNull(app(CartDatabase::class)->getCartItem(1));
            $item = cart()->addProduct([
                'plu' => 5,
            ]);
            $dbItem = app(CartDatabase::class)->getCartItem($item->id);
            $this->assertNotNull($dbItem);
            $this->assertEquals($item->plu, $dbItem->plu);
        } catch (\Exception $ex) {
            $this->assertTrue(false);
        }
    }

    /**
     * @test
     */
    public function does_price_total_get_set_automatically_on_model_update()
    {
        $cart = cart();
        $product = $cart->addProduct([
            'plu' => 29,
            'price' => 29.95,
            'quantity' => 2,
            'tax_percent' => 0.06,
        ]);
        $this->assertEquals(59.90, (float) $product->total);
        $this->assertEqualsWithDelta(3.39, (float) $product->tax_amount, .005);
        $product->update([
            'price' => 32.15,
        ]);
        $this->assertEquals(64.30, (float) $product->total);
        $this->assertEqualsWithDelta(3.64, (float) $product->tax_amount, .005);

        $product->update([
            'tax_percent' => 0.21,
        ]);
        $this->assertEqualsWithDelta(11.16, (float) $product->tax_amount, .005);
    }

    /**
     * @test
     */
    public function can_merge_rows()
    {
        $cart = cart();
        $product = [
            'quantity' => 1,
            'plu' => 695,
            'type' => 1,
            'weight' => 0,
            'total_weight' => 0,
            'price' => 4.6,
            'tax_percent' => 0.06,
            'additional' => [
                'name' => 'ANANASSAP FLES 1L',
                'slug' => 'ananassap-fles-1l',
                'unit' => 'FLES',
                'unit_id' => 9,
                'unit_price' => 4.6,
                'image_url' => '/images/placeholder.png',
                'subunit' => 'FLES',
                'subunit_id' => 9,
                'subunitverh' => 1,
                'min_bestel_aant' => 1,
                'comment' => '',
            ],
        ];
        $product2 = [
            'quantity' => 1,
            'plu' => 690,
            'price' => 10,
            'tax_percent' => 0.06,
            'type' => 1,
        ];

        $item = $cart->addProduct($product);

        $this->assertCount(1, $cart->items());

        $cart->addProduct($product2);

        $this->assertCount(2, $cart->items());

        $cart->addProduct($product);

        $this->assertCount(2, $cart->items());

        $this->assertCount(2, $cart->items());

        $cart->addProduct($product);

        $cart->addProduct($product);

        $cart->addProduct($product);

        $this->assertEquals(5, $cart->getItem($item->id)->quantity);
        $this->assertCount(2, $cart->items());
    }

    /**
     * @test
     */
    public function can_merge_rows_if_keys_arent_in_same_order()
    {
        $product = [
            'quantity' => 1,
            'plu' => 695,
            'type' => 1,
            'additional' => [
                'name' => 'ANANASSAP FLES 1L',
                'unit' => 'FLES',
                'comment' => '',
            ],
        ];

        $product2 = [
            'quantity' => 1,
            'plu' => 695,
            'type' => 1,
            'additional' => [
                'comment' => '',
                'unit' => 'FLES',
                'name' => 'ANANASSAP FLES 1L',
            ],
        ];

        $cart = cart();

        $product = $cart->addProduct($product);

        $cart->addProduct($product2);

        $this->assertCount(1, $cart->items());

        $this->assertEquals(2, $cart->getItem($product->id)->quantity);
    }

    /**
     * @test
     */
    public function can_add_item_with_decimal_quantity()
    {
        $cart = cart();

        $product = [
            'quantity' => 0.25,
            'plu' => 695,
            'type' => 1,
            'additional' => [
                'name' => 'Préparé 250g',
                'unit' => 'KG',
                'comment' => '',
            ],
        ];

        $cart->addProduct($product);
        $this->assertEquals((float) 0.25, $cart->items()->first()->quantity);
    }

    /**
     * @test
     */
    public function can_update_subproduct_quantities()
    {
        $cart = cart();

        $product = [
            'quantity' => 0.25,
            'plu' => 695,
            'type' => ItemTypes::PLU,
            'price' => 10,
        ];

        $parent = $cart->addProduct($product);

        $subproduct = $cart->addProduct([
            'parent_id' => $parent->id,
            'plu' => 123,
            'type' => ItemTypes::PLU,
            'quantity' => 3,
            'price' => 1,
        ]);

        $warranty = $cart->addProduct([
            'parent_id' => $parent->id,
            'plu' => 456,
            'type' => ItemTypes::WARRANTY,
            'quantity' => 1,
            'price' => 5,
        ]);
        $this->assertEquals(1, $warranty->quantity);

        $this->assertEquals(10.5, $cart->getCart()->grand_total);
        $this->assertEquals(3, $subproduct->quantity);
        $this->assertEquals(1, $warranty->quantity);
        $this->assertEquals(0.25, $parent->quantity);

        $cart->itemsRepository->setQuantity($parent, 1, true);
        $subproduct->refresh();
        $warranty->refresh();
        $this->assertEquals(12, $subproduct->quantity);
        $this->assertEquals(1, $parent->quantity);
        $this->assertEquals(4, $warranty->quantity);
        $this->assertEquals(42, $cart->getCart()->grand_total);

        $cart->itemsRepository->setQuantity($parent, 0.25, false);
        $subproduct->refresh();
        $warranty->refresh();
        $this->assertEquals(12, $subproduct->quantity);
        $this->assertEquals(0.25, $parent->quantity);
        $this->assertEquals(4, $warranty->quantity);
        $this->assertEquals(34.5, $cart->getCart()->grand_total);

        $cart->itemsRepository->setQuantity($parent, 1, function (CartItem $product) {
            return $product->type === ItemTypes::WARRANTY;
        });
        $subproduct->refresh();
        $warranty->refresh();
        $this->assertEquals(12, $subproduct->quantity);
        $this->assertEquals(1, $parent->quantity);
        $this->assertEquals(16, $warranty->quantity);
        $this->assertEquals(102, $cart->getCart()->grand_total);
    }
}
