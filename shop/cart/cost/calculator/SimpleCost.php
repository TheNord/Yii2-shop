<?php

namespace shop\cart\cost\calculator;

use shop\cart\cost\Cost;

class SimpleCost implements CalculatorInterface
{
    /** Получение общей стоимости товаров */
    public function getCost(array $items): Cost
    {
        $cost = 0;
        // проходим циклом по всем товарам
        foreach ($items as $item) {
            // прибавляем к общей цене каждый конкретный товар с учетом количества
            $cost += $item->getCost();
        }
        return new Cost($cost);
    }
}