<?php

use juniorE\ShoppingCart\Data\Repositories\EloquentCartDatabase;
use juniorE\ShoppingCart\Enums\ItemTypes;

return [
    /**
     * Database config properties
     *
     * implementation: Switch between Redis/Eloquent to handle data
     * ttl: "time to live", number of days the data can remain idle in the database before getting deleted.
     * table: name of the carts table. Change it only on a fresh install or
     *        alongside a rename of the existing table — every deployment that
     *        already ran the migrations holds its carts in 'carts'.
     *        An application that publishes this file must set the key there:
     *        mergeConfigFrom() merges one level deep, so a published
     *        'database' array replaces this one wholesale rather than
     *        inheriting the keys it leaves out. Cart::tableName() falls back
     *        to 'carts' for exactly that case.
     */
    'database' => [
        'implementation' => EloquentCartDatabase::class,
        'ttl' => 30,
        'table' => 'carts',
    ],

    /**
     * If set to false, by default every product will become a new line.
     */
    'merge_lines' => true,

    /**
     * Item types on which tax should not be calculated
     */
    'tax_exempt_types' => [
        ItemTypes::WARRANTY,
    ],

    /**
     * Item types on which the coupon should not be applied
     */
    'discount_exempt_types' => [
        ItemTypes::WARRANTY,
    ],

    'login' => [
        'userIdColumn' => 'id',
    ],
];
