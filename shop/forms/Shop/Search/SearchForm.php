<?php

namespace shop\forms\Shop\Search;

use shop\entities\Shop\Brand;
use shop\entities\Shop\Category;
use shop\entities\Shop\Characteristic;
use shop\forms\CompositeForm;
use yii\helpers\ArrayHelper;

/**
 * @property ValueForm[] $values
 */
class SearchForm extends CompositeForm
{
    // поиск по тексту, категории и бренду
    public $text;
    public $category;
    public $brand;

    public function __construct(array $config = [])
    {
        // дополнительным поиском будут характеристики
        // через array_map проходим по всем характеристикам и передаем через ValueForm
        $this->values = array_map(function (Characteristic $characteristic) {
            return new ValueForm($characteristic);
        }, Characteristic::find()->orderBy('sort')->all());
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [['text'], 'string'],
            [['category', 'brand'], 'integer'],
        ];
    }

    /** Выводим дерево категорий */
    public function categoriesList(): array
    {
        return ArrayHelper::map(Category::find()->andWhere(['>', 'depth', 0])->orderBy('lft')->asArray()->all(), 'id', function (array $category) {
            return ($category['depth'] > 1 ? str_repeat('-- ', $category['depth'] - 1) . ' ' : '') . $category['name'];
        });
    }

    /** Получаем список брэндов */
    public function brandsList(): array
    {
        return ArrayHelper::map(Brand::find()->orderBy('name')->asArray()->all(), 'id', 'name');
    }

    /** Проставляем пустое название, чтобы в адресной строке выводить без названия */
    public function formName(): string
    {
        return '';
    }

    protected function internalForms(): array
    {
        return ['values'];
    }
}