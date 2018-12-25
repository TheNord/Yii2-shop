<?php

namespace shop\readModels\Shop;

use shop\entities\Shop\Brand;
use shop\entities\Shop\Category;
use shop\entities\Shop\Product\Product;
use shop\entities\Shop\Tag;
use yii\data\ActiveDataProvider;
use yii\data\DataProviderInterface;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

class ProductReadRepository
{
    /** Получение всех товаров с главным фото */
    public function getAll(): DataProviderInterface
    {
        $query = Product::find()->alias('p')->active('p')->with('mainPhoto');
        return $this->getProvider($query);
    }

    public function getAllByCategory(Category $category): DataProviderInterface
    {
        // находим активные продукты, назначаем алиас p, жадной загрузкой получаем главные фото и категории
        $query = Product::find()->alias('p')->active('p')->with('mainPhoto', 'category');
        // получаем все идшники всех вложенных категорий в текущей категории
        // получаем ид текущей категории, запрашиваем все ид вложенных категорий от текущей категории и склеиваем
        $ids = ArrayHelper::merge([$category->id], $category->getDescendants()->select('id')->column());
        // джойним таблицу categoryAssignments
        $query->joinWith(['categoryAssignments ca'], false);
        // и ищем все товары у которых в привязанной категории, в поле category_id в промежуточной таблице
        // попадают под идшники нужных нам категорий
        $query->andWhere(['or', ['p.category_id' => $ids], ['ca.category_id' => $ids]]);
        // групируем по идшнику товара
        $query->groupBy('p.id');
        return $this->getProvider($query);
    }

    /** Находим все товары для текущего бренда */
    public function getAllByBrand(Brand $brand): DataProviderInterface
    {
        $query = Product::find()->alias('p')->active('p')->with('mainPhoto');
        $query->andWhere(['p.brand_id' => $brand->id]);
        return $this->getProvider($query);
    }

    /** Находим все товары для тэгов */
    public function getAllByTag(Tag $tag): DataProviderInterface
    {
        $query = Product::find()->alias('p')->active('p')->with('mainPhoto');
        $query->joinWith(['tagAssignments ta'], false);
        $query->andWhere(['ta.tag_id' => $tag->id]);
        $query->groupBy('p.id');
        return $this->getProvider($query);
    }

    public function find($id): ?Product
    {
        return Product::find()->active()->andWhere(['id' => $id])->one();
    }

    /** Устанавливаем сортировку
     * Это нужно чтобы при выборке из нескольких таблиц (связующих) данные не путались
     */
    private function getProvider(ActiveQuery $query): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC],
                'attributes' => [
                    'id' => [
                        'asc' => ['p.id' => SORT_ASC],
                        'desc' => ['p.id' => SORT_DESC],
                    ],
                    'name' => [
                        'asc' => ['p.name' => SORT_ASC],
                        'desc' => ['p.name' => SORT_DESC],
                    ],
                    'price' => [
                        'asc' => ['p.price_new' => SORT_ASC],
                        'desc' => ['p.price_new' => SORT_DESC],
                    ],
                    'rating' => [
                        'asc' => ['p.rating' => SORT_ASC],
                        'desc' => ['p.rating' => SORT_DESC],
                    ],
                ],
            ],
        ]);
    }
}