<?php

namespace juniorE\ShoppingCart\Data\Interfaces;

interface VisitsHistoryDatabase
{
    public function markVisited(string $plu): void;
}
