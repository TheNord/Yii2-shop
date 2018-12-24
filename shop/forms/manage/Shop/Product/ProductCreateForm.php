<?php

namespace shop\forms\manage\Shop\Product;

use shop\entities\Shop\Characteristic;
use shop\entities\Shop\Product\Product;
use shop\forms\CompositeForm;
use shop\forms\manage\MetaForm;
use shop\entities\Shop\Brand;
use yii\helpers\ArrayHelper;

/**
 * @property PriceForm $price
 * @property MetaForm $meta
 * @property CategoriesForm $categories
 * @property PhotosForm $photos
 * @property TagsForm $tags
 * @property ValueForm[] $values
 */
class ProductCreateForm extends CompositeForm
{
    public $brandId;
    public $code;
    public $name;
    public $description;

    public function __construct($config = [])
    {
        $this->price = new PriceForm();
        $this->meta = new MetaForm();
        $this->categories = new CategoriesForm();
        $this->photos = new PhotosForm();
        $this->tags = new TagsForm();
        // в values: получаем все доступные на сайте характеристики
        // сортируем и составляем массивом из полей ValueForm
        $this->values = array_map(function (Characteristic $characteristic) {
            return new ValueForm($characteristic);
        }, Characteristic::find()->orderBy('sort')->all());
        parent::__construct($config);
    }

    public function brandsList(): array
    {
        return ArrayHelper::map(Brand::find()->orderBy('name')->asArray()->all(), 'id', 'name');
    }

    public function rules(): array
    {
        return [
            [['brandId', 'code', 'name'], 'required'],
            [['code', 'name'], 'string', 'max' => 255],
            [['brandId'], 'integer'],
            [['code'], 'unique', 'targetClass' => Product::class],
            ['description', 'string'],
        ];
    }

    /** Добавляем дополнительные формы */
    protected function internalForms(): array
    {
        return ['price', 'meta', 'photos', 'categories', 'tags', 'values'];
    }
}