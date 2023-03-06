<?php


namespace juniorE\ShoppingCart\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use juniorE\ShoppingCart\Data\Interfaces\CartDatabase;
use juniorE\ShoppingCart\Enums\ItemTypes;
use juniorE\ShoppingCart\Events\CartItems\CartItemCreatedEvent;
use juniorE\ShoppingCart\Events\CartItems\CartItemDeletedEvent;
use juniorE\ShoppingCart\Events\CartItems\CartItemUpdatedEvent;

/**
 * Class CartItem
 * @package juniorE\ShoppingCart\Models
 *
 * @property int $id
 * @property int $cart_id
 * @property int|null $parent_id
 * @property string row_hash
 * @property int $quantity
 * @property string $plu
 * @property int $type
 * @property double $weight
 * @property double $total_weight
 * @property string|null $coupon_code
 * @property double $price
 * @property double $total
 * @property double $discount
 * @property double|null $tax_percent
 * @property double|null $tax_amount
 * @property array|null $additional
 * @property Carbon $updated_at
 * @property Carbon $created_at
 */
class CartItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        "price" => "float",
        "cart_id" => "int",
        "plu" => "int",
        "type" => "int",
        "additional" => "array",
        "updated_at" => "datetime",
        "created_at" => "datetime",

    ];

    public function cart()
    {
        return $this->belongsTo(\juniorE\ShoppingCart\Cart::class);
    }

    public function subproducts()
    {
        return $this->hasMany(CartItem::class, "parent_id");
    }

    public function parent()
    {
        return $this->belongsTo(CartItem::class, "parent_id");
    }

    public function coupon()
    {
        return $this->hasOne(CartCoupon::class, 'name', 'coupon_code');
    }

    public function onUpdate()
    {
        $this->updateHash();
        $this->updateTotals();
    }

    public function updateHash()
    {
        $this->row_hash = $this->getRowHash();
    }

    public function updateTotals()
    {
        $this->total = ($this->price ?? 0) * ($this->quantity ?? 0);
        if ($this->taxable()) {
            $this->tax_amount = $this->total - ($this->total / (1 + ($this->tax_percent ?? 0)));
        } else {
            $this->tax_amount = 0;
        }
        if ($this->coupon_code && $this->discountable()) {
            $coupon = CartCoupon::firstWhere('name', $this->coupon_code);

            if ($coupon && !collect("shoppingcart.discount_exempt_types")->contains($this->type)) {
                $this->discount = $coupon->discount($this->price * $this->quantity, $this->quantity, $this->price);
            }
        }
    }

    public function discountable()
    {
        return !collect(config('shoppingcart.discount_exempt_types'))
            ->contains($this->type);
    }

    public function taxable()
    {
        return self::isTaxable($this->type);
    }

    public static function isTaxable($type)
    {
        return !collect(config('shoppingcart.tax_exempt_types'))
            ->contains($type);
    }

    public static function getHash($attributes)
    {
        return (new static($attributes))->getRowHash();
    }

    public function getRowHash()
    {
        $additional = collect($this->attributes)
            ->only([
                'additional'
            ])
            ->toArray();

        if (isset($additional["additional"])) {
            $additional = $additional["additional"];

            $additional = json_decode($additional, true);

            ksort($additional);
        }

        return sha1(
            collect($additional)
                ->put('cart_id', (int) $this->cart_id)
                ->put('plu', (int) $this->plu)
                ->put('type', (int) ($this->type ?? ItemTypes::PLU))
                ->toJson()
        );
    }

    public function getSubproductsOfType($type)
    {
        return $this->subproducts->filter(function(CartItem $product) use ($type) {
            return (string) $product->type === (string) $type;
        });
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function(CartItem $model) {
            event(new CartItemUpdatedEvent($model));
            app(CartDatabase::class)->updateTotal($model->cart_id);
        });

        static::created(function(CartItem $model) {
            event(new CartItemCreatedEvent($model));
            app(CartDatabase::class)->updateTotal($model->cart_id);
        });

        static::deleted(function(CartItem $model) {
            event(new CartItemDeletedEvent($model));
            app(CartDatabase::class)->updateTotal($model->cart_id);
        });

        static::creating(function(CartItem $model) {
            $model->onUpdate();
        });

        static::saving(function(CartItem $model) {
            $model->onUpdate();
        });

        static::updating(function(CartItem $model) {
            $model->onUpdate();
        });
    }
}
