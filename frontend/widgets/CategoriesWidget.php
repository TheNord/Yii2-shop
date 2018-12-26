<?php

namespace frontend\widgets;

use shop\entities\Shop\Category;
use shop\readModels\Shop\CategoryReadRepository;
use yii\base\Widget;
use yii\helpers\Html;

class CategoriesWidget extends Widget
{
    /** @var Category|null */
    public $active;
    private $categories;

    public function __construct(CategoryReadRepository $categories, $config = [])
    {
        parent::__construct($config);
        $this->categories = $categories;
    }

    public function run(): string
    {
        return Html::tag('div', implode(PHP_EOL, array_map(function (Category $category) {
            // добавляем пробелов в зависимости от глубины вложенности, добавляем дефис
            $indent = ($category->depth > 1 ? str_repeat('&nbsp;&nbsp;&nbsp;', $category->depth - 1) . '- ' : '');
            // делаем проверку на активность текущей рубрики, если активная категория не задана, назначаем активной дочернюю категорию
            $active = $this->active && ($this->active->id == $category->id || $this->active->isChildOf($category));
            // выводим отступ, название рубрики, ссылку на рубрику и проставляем активность
            return Html::a(
                $indent . Html::encode($category->name),
                ['/shop/catalog/category', 'id' => $category->id],
                ['class' => $active ? 'list-group-item active' : 'list-group-item']
            );
            // источник данных для обработки
        }, $this->categories->getTreeWithSubsOf($this->active))), [
            'class' => 'list-group',
        ]);
    }
}