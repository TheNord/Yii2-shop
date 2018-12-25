<?php

namespace shop\entities\Shop\Product\queries;

use shop\entities\Shop\Product\Product;
use yii\db\ActiveQuery;

class ProductQuery extends ActiveQuery
{
    public function active($alias = null)
    {
        // используем alias для добавления псевдонима, на случай если у нас
        // при поиске сразу в двух таблицах будет поле статус
        // Product::find->alias('p')->active()
        // ->joinWith('categories c')->andWhere('p.status' => 1)
        // ->andWhere(['c.status' => 2])->all();

        return $this->andWhere([
            ($alias ? $alias . '.' : '') . 'status' => Product::STATUS_ACTIVE,
        ]);
    }
}