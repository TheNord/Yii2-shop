<?php

namespace shop\readModels\Shop;

use shop\entities\Shop\Category;

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
}