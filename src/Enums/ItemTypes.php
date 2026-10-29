<?php

namespace juniorE\ShoppingCart\Enums;

class ItemTypes
{
    const PLU = 1;

    const RENT = 2;

    const SERVICE = 3;

    const WARRANTY = 4;

    const PACKAGES = 5;

    const MENU = 6;

    /**
     * Not 7: the shop writes these as O_DETAILS.LIJNTYPE, and the till
     * (ExquisStudio) reads 7 as its own package line.
     */
    const VOUCHER = 9;
}
