<?php

namespace shop\forms\manage\Shop\Product;

use shop\entities\Shop\Product\Product;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class CategoriesForm extends Model
{
    // главная категория
    public $main;
    // дополнительные категории
    public $others = [];

    public function __construct(Product $product = null, $config = [])
    {
        if ($product) {
            // записываем главную категорию
            $this->main = $product->category_id;
            // записываем дополнительные категории, получая через связь categoryAssignments и полю category_id
            // categoryAssignments - связь на промежуточную таблицу между продуктом и категорией
            $this->others = ArrayHelper::getColumn($product->categoryAssignments, 'category_id');
        }
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            ['main', 'required'],
            ['main', 'integer'],
            ['others', 'each', 'rule' => ['integer']],
            ['others', 'default', 'value' => []],
        ];
    }
}