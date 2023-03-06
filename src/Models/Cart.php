<?php


namespace juniorE\ShoppingCart\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;
use juniorE\ShoppingCart\Data\Interfaces\CartDatabase;
use juniorE\ShoppingCart\Events\Cart\CartCreatedEvent;
use juniorE\ShoppingCart\Events\Cart\CartDeletedEvent;
use juniorE\ShoppingCart\Events\Cart\CartUpdatedEvent;

/**
 * Class Cart
 * @package juniorE\ShoppingCart\Models
 *
 * @property int $id
 * @property string $identifier
 * @property string|null $shipping_method
 * @property string|null $coupon_code
 * @property int|null $items_count
 * @property double|null $grand_total
 * @property double|null $sub_total
 * @property double|null $tax_total
 * @property double|null $discount
 * @property string|null $checkout_method
 * @property int|null $conversion_time
 * @property array|null $additional
 * @property Carbon $updated_at
 * @property Carbon $created_at
 *
 * @property HasOne|VisitsHistory[]|VisitsHistory|null $history;
 * @property HasMany|CartItem[]|CartItem|null $items;
 *
 * @method static Builder|static where(string $column, string $operator, mixed $value, $boolean="and")
 * @method static Builder|static query()
 * @method static Model|Collection|static[]|static|null find(mixed $id, array $columns=[])
 */
class Cart extends Model
{
    protected $guarded = [];

    protected $casts = [
        "additional" => "array",
        "updated_at" => "datetime",
        "created_at" => "datetime",

    ];

    public function history()
    {
        return $this->hasOne(VisitsHistory::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function getCouponsAttribute()
    {
        return collect();
    }

    public function coupon()
    {
        return $this->hasOne(CartCoupon::class, "name", "coupon_code");
    }

    public static function clean(): void
    {
        try {
            Cart::where("updated_at", "<=", now()->subDays(config("shoppingcart.database.ttl")))
                ->delete();
        } catch (\Exception $e) {
            Log::error("Error while trying to clean up database.");
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function(Cart $model) {
            event(new CartUpdatedEvent($model));
        });

        static::created(function(Cart $model) {
            event(new CartCreatedEvent($model));
        });

        static::deleted(function(Cart $model) {
            event(new CartDeletedEvent($model));
        });
    }
}
