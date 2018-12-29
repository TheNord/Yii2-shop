<?php

namespace shop\entities\Shop\queries;

use yii\db\ActiveQuery;

class DeliveryMethodQuery extends ActiveQuery
{
    /** Добавляем поиск доставки по массе товара */
    public function availableForWeight($weight)
    {
        return $this->andWhere(['and',
            ['or', ['min_weight' => null], ['<=', 'min_weight', $weight]],
            ['or', ['max_weight' => null], ['>=', 'max_weight', $weight]],
        ]);
    }
}