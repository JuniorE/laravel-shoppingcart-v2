<?php


namespace juniorE\ShoppingCart\Tests\Feature;

use DB;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use juniorE\ShoppingCart\Enums\CouponTypes;
use juniorE\ShoppingCart\Tests\TestCase;

$prices = [97.6415,5.8585,3.1050];

/**
 * For when you want to quickly create a product but don't care what it's named,
 * @param $price
 * @return array
 * @throws Exception
 */
function createProduct($price, $tax=0.21): array
{
    return [
        "plu" => random_int(1, 100000),
        "price" => $price,
        "quantity" => 1,
        "tax_percent" => $tax,
    ];
}

class RoundingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up tests
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @throws Exception
     * @test
     */
    public function discountGetsRoundedCorrectly()
    {
        $product = createProduct(103.5, 0.06);

        cart()->addProduct($product);

        $this->assertEquals(103.5, cart()->getCart()->grand_total);

        $coupon = cart()->couponsRepository->addCoupon([
            "name" => "WELCOME3",
            "coupon_type" => CouponTypes::PERCENT,
            "discount_percent" => 0.03,
        ]);

        cart()->addCoupon($coupon);

        $this->assertEquals(100.39, cart()->getCart()->grand_total);
    }
}
