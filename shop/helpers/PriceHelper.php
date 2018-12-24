<?php

namespace shop\helpers;

class PriceHelper
{
    /** Округляем стоимость, после запятой выводим 0 знаков */
    public static function format($price): string
    {
        return number_format($price, 0, '.', ' ');
    }
}