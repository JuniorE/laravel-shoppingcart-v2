<?php

namespace juniorE\ShoppingCart\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use juniorE\ShoppingCart\Events\CartShippingRate\CartShippingRateCreatedEvent;
use juniorE\ShoppingCart\Events\CartShippingRate\CartShippingRateDeletedEvent;
use juniorE\ShoppingCart\Events\CartShippingRate\CartShippingRateUpdatedEvent;

/**
 * Class CartShippingRate
 *
 * @property int $id
 * @property string $method
 * @property string|null $method_description
 * @property float $price
 * @property float $minimum_cart_price
 * @property Carbon $updated_at
 * @property Carbon $created_at
 */
class CartShippingRate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'additional' => 'array',
        'updated_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updated(function (CartShippingRate $model) {
            event(new CartShippingRateUpdatedEvent($model));
        });

        static::created(function (CartShippingRate $model) {
            event(new CartShippingRateCreatedEvent($model));
        });

        static::deleted(function (CartShippingRate $model) {
            event(new CartShippingRateDeletedEvent($model));
        });
    }
}
