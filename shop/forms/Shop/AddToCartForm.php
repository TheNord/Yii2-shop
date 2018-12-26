<?php

namespace shop\forms\Shop;

use shop\entities\Shop\Product\Modification;
use shop\entities\Shop\Product\Product;
use shop\helpers\PriceHelper;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class AddToCartForm extends Model
{
    public $modification;
    public $quantity;
    private $_product;

    public function __construct(Product $product, $config = [])
    {
        $this->_product = $product;
        $this->quantity = 1;
        parent::__construct($config);
    }

    /** правила валидации для формы */
    public function rules(): array
    {
        // если модификации имеются, делаем поле обязательным к заполнению
        // число товаров - обязательно к заполнению
        return array_filter([
            $this->_product->modifications ? ['modification', 'required'] : false,
            ['quantity', 'required'],
        ]);
    }

    public function modificationsList(): array
    {
        // проходим по списку всех модификаций товара, для ключей выпадающего списка используем ид
        return ArrayHelper::map($this->_product->modifications, 'id', function (Modification $modification) {
            // а в качестве надписи выводим Код-Название(Цена)
            return $modification->code . ' - ' . $modification->name . ' (' . PriceHelper::format($modification->price ?: $this->_product->price_new) . ')';
        });
    }
}