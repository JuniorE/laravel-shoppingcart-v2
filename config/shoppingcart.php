<?php

return [
    /**
     * Database config properties
     *
     * implementation: Switch between Redis/Eloquent to handle data
     * ttl: "time to live", number of days the data can remain idle in the database before getting deleted.
     */
    'database' => [
        'implementation' => \juniorE\ShoppingCart\Data\Repositories\EloquentCartDatabase::class,
        'ttl' => 30,

    ],

    /**
     * If set to false, by default every product will become a new line.
     */
    'merge_lines' => true,

    /**
     * Item types on which tax should not be calculated
     */
    'tax_exempt_types' => [
        \juniorE\ShoppingCart\Enums\ItemTypes::WARRANTY,
    ],

    /**
     * Item types on which the coupon should not be applied
     */
    'discount_exempt_types' => [
        \juniorE\ShoppingCart\Enums\ItemTypes::WARRANTY,
    ],

    'login' => [
        'userIdColumn' => 'id',
    ],
];
