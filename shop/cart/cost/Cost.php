<?php

namespace shop\cart\cost;

/** Общая стоимость всех товаров с учетом скидок и без */
final class Cost
{
    private $value;
    private $discounts = [];

    public function __construct(float $value, array $discounts = [])
    {
        $this->value = $value;
        $this->discounts = $discounts;
    }

    public function withDiscount(Discount $discount): self
    {
        return new static($this->value, array_merge($this->discounts, [$discount]));
    }

    /** Оригинальная стоимость */
    public function getOrigin(): float
    {
        return $this->value;
    }

    /** Общая стоимость вместе с купонами */
    public function getTotal(): float
    {
        return $this->value - array_sum(array_map(function (Discount $discount) {
                return $discount->getValue();
            }, $this->discounts));
    }

    /**
     * Получение всех купонов
     *
     * @return Discount[]
     */
    public function getDiscounts(): array
    {
        return $this->discounts;
    }
}