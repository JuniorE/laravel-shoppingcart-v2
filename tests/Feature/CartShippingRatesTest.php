<?php


use Illuminate\Foundation\Testing\RefreshDatabase;
use juniorE\ShoppingCart\Models\CartShippingRate;
use juniorE\ShoppingCart\Tests\TestCase;

class CartShippingRatesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var CartShippingRate
     */
    private $invoice;

    /**
     * @var CartShippingRate
     */
    private $invoiceFree;

    /**
     * @var CartShippingRate
     */
    private $cash;

    public function setUp(): void
    {
        parent::setUp();

        $this->invoice = cart()->shippingRateRepository->addShippingRate([
            "method" => "invoice",
            "price" => 10,
            "minimum_cart_price" => 0
        ]);

        $this->invoiceFree = cart()->shippingRateRepository->addShippingRate([
            "method" => "invoice",
            "price" => 0,
            "minimum_cart_price" => 50
        ]);

        $this->cash = cart()->shippingRateRepository->addShippingRate([
            "method" => "cash",
            "price" => 0,
            "minimum_cart_price" => 0
        ]);
    }

    /**
     * @test
     */
    public function can_make_a_shipping_rate(){
        $this->assertEquals("invoice", $this->invoice->method);
        $this->assertEquals("invoice", $this->invoiceFree->method);
        $this->assertEquals("cash", $this->cash->method);

        $this->assertEquals(10, $this->invoice->price);
        $this->assertEquals(0, $this->invoiceFree->price);
        $this->assertEquals(0, $this->cash->price);

        $this->assertCount(3, CartShippingRate::all());
    }

    /**
     * @test
     */
    public function can_set_method_of_shipping_rate(){
        $mistakeInvoice = cart()->shippingRateRepository->addShippingRate([
            "method" => "cash",
            "price" => 0,
            "minimum_cart_price" => 0
        ]);

        $this->assertEquals("cash", $mistakeInvoice->method);

        cart()->shippingRateRepository->setMethod($mistakeInvoice, "invoice");

        $this->assertEquals("invoice", $mistakeInvoice->method);
    }
    
    /**
     * @test
     */
    public function can_set_method_description(){
        $this->assertNull($this->invoice->method_description);

        cart()->shippingRateRepository->setMethodDescription($this->invoice, "lorem ipsum");

        $this->assertEquals("lorem ipsum", $this->invoice->method_description);
    }

    /**
     * @test
     */
    public function can_set_price_of_shipping_rate(){
        $this->assertEquals(10, $this->invoice->price);

        cart()->shippingRateRepository->setPrice($this->invoice, 20);

        $this->assertEquals(20, $this->invoice->price);
        $this->assertEquals(0, $this->invoiceFree->price);
    }

    /**
     * @test
     */
    public function can_set_minimum_cart_price(){
        $this->assertEquals(0, $this->invoice->minimum_cart_price);
        $this->assertEquals(50, $this->invoiceFree->minimum_cart_price);

        cart()->shippingRateRepository->setMinimumCartPrice($this->invoiceFree, 100);

        $this->assertEquals(0, $this->invoice->minimum_cart_price);
        $this->assertEquals(100, $this->invoiceFree->minimum_cart_price);
    }

    /**
     * @test
     */
    public function can_remove_shipping_rates(){
        $this->assertCount(3, CartShippingRate::all());

        cart()->shippingRateRepository->removeShippingRate($this->invoice);
        cart()->shippingRateRepository->removeShippingRate($this->invoiceFree);
        cart()->shippingRateRepository->removeShippingRate($this->cash);

        $this->assertCount(0, CartShippingRate::all());
    }
    
    /**
     * @test
     */
    public function shipping_rate_price_does_not_count_for_shipping_rate_eligibility(){
        $cart = cart();
        $cart->addProduct([
            "plu" => 1,
            "quantity" => 1,
            "price" => 45
        ]);
        $cart->setShippingMethod($this->invoice->method);
        $this->assertEqualsWithDelta(45 + $cart->getShippingRate()->price, $cart->getCart()->grand_total, 0.5);
    }
}
