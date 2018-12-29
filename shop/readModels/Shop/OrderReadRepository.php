<?php

namespace shop\readModels\Shop;

use shop\entities\Shop\Order\Order;
use yii\data\ActiveDataProvider;

class OrderReadRepository
{
    public function getOwm($userId): ActiveDataProvider
    {
        // возвращаем заказы пользователя
        return new ActiveDataProvider([
            'query' => Order::find()
                ->andWhere(['user_id' => $userId])
                ->orderBy(['id' => SORT_DESC]),
            'sort' => false,
        ]);
    }

    /** Поиск заказа по ид, для детального отображения */
    public function findOwn($userId, $id): ?Order
    {
        return Order::find()->andWhere(['user_id' => $userId, 'id' => $id])->one();
    }
}