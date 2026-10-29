<?php

namespace juniorE\ShoppingCart\Tests\Feature;

use juniorE\ShoppingCart\Enums\ItemTypes;
use juniorE\ShoppingCart\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;

/**
 * The shop writes these as O_DETAILS.LIJNTYPE, which the till (ExquisStudio)
 * imports by number. 7 is the till's own package line there, so a voucher
 * written as 7 came in as a package and its discount never reached the till.
 */
class ItemTypesTest extends TestCase
{
    #[Test]
    public function a_voucher_is_line_type_nine()
    {
        $this->assertSame(9, ItemTypes::VOUCHER);
    }

    #[Test]
    public function no_two_types_share_a_number()
    {
        $numbers = (new ReflectionClass(ItemTypes::class))->getConstants();

        $this->assertSame(count($numbers), count(array_unique($numbers)));
        $this->assertNotContains(7, $numbers, 'the till reads 7 as a package line');
    }
}
