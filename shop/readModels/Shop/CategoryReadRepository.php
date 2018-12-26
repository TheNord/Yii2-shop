<?php

namespace shop\readModels\Shop;

use shop\entities\Shop\Category;
use yii\helpers\ArrayHelper;

class CategoryReadRepository
{
    /** Получаем главную категорию, для вывода всех рубрик */
    public function getRoot(): Category
    {
        return Category::find()->roots()->one();
    }

    public function find($id): ?Category
    {
        return Category::find()->andWhere(['id' => $id])->andWhere(['>', 'depth', 0])->one();
    }

    public function findBySlug($slug): ?Category
    {
        // Ищем категорию с нужным слагом и глубиной больше нуля
        return Category::find()->andWhere(['slug' => $slug])->andWhere(['>', 'depth', 0])->one();
    }

    /** Дерево категорий для виджета */
    public function getTreeWithSubsOf(Category $category = null): array
    {
        // получаем категории с глубиной больше нуля (Исключаем рутовскую)
        $query = Category::find()->andWhere(['>', 'depth', 0])->orderBy('lft');

        if ($category) {
            // задаем критерии, выводим рубрики первого уровня
            $criteria = ['or', ['depth' => 1]];
            // проходим по массиву категории и ее родителей
            foreach (ArrayHelper::merge([$category], $category->parents) as $item) {
                // выводим текущие категории и на одну глубже
                $criteria[] = ['and', ['>', 'lft', $item->lft], ['<', 'rgt', $item->rgt], ['depth' => $item->depth + 1]];
            }
            $query->andWhere($criteria);
        } else {
            // если категория не передана выводим рубрики первой глубины
            $query->andWhere(['depth' => 1]);
        }

        return $query->all();
    }
}