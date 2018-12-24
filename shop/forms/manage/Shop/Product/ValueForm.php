<?php

namespace shop\forms\manage\Shop\Product;

use shop\entities\Shop\Characteristic;
use shop\entities\Shop\Product\Value;
use yii\base\Model;

/**
 * @property integer $id
 */
class ValueForm extends Model
{
    public $value;

    private $_characteristic;

    /** Принимаем характеристику и текущее значение из продукта */
    public function __construct(Characteristic $characteristic, Value $value = null, $config = [])
    {
        if ($value) {
            $this->value = $value->value;
        }
        $this->_characteristic = $characteristic;
        parent::__construct($config);
    }

    public function variantsList(): array
    {
        return $this->_characteristic->variants ? array_combine($this->_characteristic->variants, $this->_characteristic->variants) : [];
    }

    public function rules(): array
    {
        // генерируем валидацию автоматически (тернарно), т.е если поле обязательно, добавляем required
        // если это строка, то добавляем правило string, max 255
        // и если правила не сработали назначаем safe (заглушка)
        return array_filter([
            $this->_characteristic->required ? ['value', 'required'] : false,
            $this->_characteristic->isString() ? ['value', 'string', 'max' => 255] : false,
            $this->_characteristic->isInteger() ? ['value', 'integer'] : false,
            $this->_characteristic->isFloat() ? ['value', 'number'] : false,
            ['value', 'safe'],
        ]);
    }

    /** Указываем название характеристики */
    public function attributeLabels(): array
    {
        return [
            'value' => $this->_characteristic->name,
        ];
    }

    /** Получаем ид, к какой характеристике присвоено значение*/
    public function getId(): int
    {
        return $this->_characteristic->id;
    }
}