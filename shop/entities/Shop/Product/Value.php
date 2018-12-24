<?php

namespace shop\entities\Shop\Product;

use yii\db\ActiveRecord;
use shop\entities\Shop\Characteristic;
use yii\db\ActiveQuery;

/**
 * @property integer $characteristic_id
 * @property string $value
 *
 * @property Characteristic $characteristic
 */
class Value extends ActiveRecord
{
    public static function create($characteristicId, $value): self
    {
        $object = new static();
        $object->characteristic_id = $characteristicId;
        $object->value = $value;
        return $object;
    }

    /** Вспомогательный конструктор для создания пустого значения */
    public static function blank($characteristicId): self
    {
        $object = new static();
        $object->characteristic_id = $characteristicId;
        return $object;
    }

    public function change($value): void
    {
        $this->value = $value;
    }

    public function isForCharacteristic($id): bool
    {
        return $this->characteristic_id == $id;
    }

    /** Выводим характеристику для продукта */
    public function getCharacteristic(): ActiveQuery
    {
        return $this->hasOne(Characteristic::class, ['id' => 'characteristic_id']);
    }

    public static function tableName(): string
    {
        return '{{%shop_values}}';
    }
}