<?php


namespace juniorE\ShoppingCart\Data\Interfaces;


interface VisitsHistoryDatabase
{
    /**
     * @param string $plu
     */
    public function markVisited(string $plu): void;
}
