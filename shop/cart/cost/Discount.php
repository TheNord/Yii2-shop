<?php

namespace shop\cart\cost;
final class Discount
{
    private $value;
    private $name;

    public function __construct(float $value, string $name)
    {
        $this->value = $value;
        $this->name = $name;
    }

    /** Стоимость на которую снизет купон */
    public function getValue(): float
    {
        return $this->value;
    }

    /** Название купона */
    public function getName(): string
    {
        return $this->name;
    }
}