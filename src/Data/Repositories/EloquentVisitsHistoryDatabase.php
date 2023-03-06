<?php


namespace juniorE\ShoppingCart\Data\Repositories;


use juniorE\ShoppingCart\Data\Interfaces\VisitsHistoryDatabase;
use juniorE\ShoppingCart\Models\VisitsHistory;

class EloquentVisitsHistoryDatabase implements VisitsHistoryDatabase
{
    /**
     * @inheritDoc
     */
    public function markVisited(string $plu): void
    {
        $cart = cart();
        $history = VisitsHistory::firstWhere("cart_id", $cart->id);

        if (!$history) {
            VisitsHistory::create([
                "cart_id" => $cart->id,
                "visits" => [$plu]
            ]);
            return;
        }

        $history->update([
            "visits" => collect($history->visits)
                ->push($plu)->toArray()
        ]);
    }
}
