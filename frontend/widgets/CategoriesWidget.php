<?php

namespace frontend\widgets;

use shop\entities\Shop\Category;
use shop\readModels\Shop\CategoryReadRepository;
use shop\readModels\Shop\views\CategoryView;
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
        // после вызова getTreeWithSubsOf, сюда прилетит объект CategoryView, с полями ид категории => количество
        // проходим по нему циклом применяя коллбэк
        // извлекаем категорию через $view->category (берем глубину)
        // извлекаем число товаров через $view->count
        return Html::tag('div', implode(PHP_EOL, array_map(function (CategoryView $view) {
            // добавляем пробелов в зависимости от глубины вложенности, добавляем дефис
            $indent = ($view->category->depth > 1 ? str_repeat('&nbsp;&nbsp;&nbsp;', $view->category->depth - 1) . '- ' : '');
            // делаем проверку на активность текущей рубрики, если активная категория не задана, назначаем активной дочернюю категорию
            $active = $this->active && ($this->active->id == $view->category->id || $this->active->isChildOf($view->category));

            return Html::a(
            // выводим отступ, название рубрики, ссылку на рубрику, количество товаров и проставляем активность
                $indent . Html::encode($view->category->name) . ' (' . $view->count . ')',
                ['/shop/catalog/category', 'id' => $view->category->id],
                ['class' => $active ? 'list-group-item active' : 'list-group-item']
            );
        }, $this->categories->getTreeWithSubsOf($this->active))), [
            'class' => 'list-group',
        ]);
    }
}