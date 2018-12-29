<?php

namespace shop\forms\Shop\Order;

use yii\base\Model;

/** Данные о заказчике */
class CustomerForm extends Model
{
    public $phone;
    public $name;

    public function rules(): array
    {
        return [
            [['phone', 'name'], 'required'],
            [['phone', 'name'], 'string', 'max' => 255],
        ];
    }
}