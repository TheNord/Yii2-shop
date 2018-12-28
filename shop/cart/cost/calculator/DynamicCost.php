<?php

namespace shop\cart\cost\calculator;

use shop\cart\cost\Cost;
use shop\cart\cost\Discount as CartDiscount;
use shop\entities\Shop\Discount as DiscountEntity;

class DynamicCost implements CalculatorInterface
{
    private $next;

    // создаем цепочку из калькуляторов
    // вся цепочка задается в бутстрапе (SetUp)
    public function __construct(CalculatorInterface $next)
    {
        $this->next = $next;
    }

    /** Получаем стоимость применяя все скидки из бд */
    public function getCost(array $items): Cost
    {
        /** @var DiscountEntity[] $discounts */
        // загружаем все активные скидки из бд
        $discounts = DiscountEntity::find()->active()->orderBy('sort')->all();
        // получаем оригинальную стоимость (из SimpleCost)
        $cost = $this->next->getCost($items);
        // проходим циклом по всем скидкам
        foreach ($discounts as $discount) {
            if ($discount->isEnabled()) {
                // получаем стоимость на которую будет снижен товар
                $new = new CartDiscount($cost->getOrigin() * $discount->percent / 100, $discount->name);
                // созраняем цену с полученным дискоунтом
                $cost = $cost->withDiscount($new);
            }
        }
        return $cost;
    }
}