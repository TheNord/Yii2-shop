<?php

namespace shop\readModels\Shop;

use shop\entities\Shop\Brand;
use shop\entities\Shop\Category;
use shop\entities\Shop\Product\Product;
use shop\entities\Shop\Product\Value;
use shop\entities\Shop\Tag;
use shop\forms\Shop\Search\SearchForm;
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

    public function getFeatured($limit): array
    {
        return Product::find()->active()->with('mainPhoto')->orderBy(['id' => SORT_DESC])->limit($limit)->all();
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
            'pagination' => [
                'pageSizeLimit' => [15, 100],
            ]
        ]);
    }

    public function search(SearchForm $form): DataProviderInterface
    {
        // получаем все товары с жадной загрузкой категории и главного фото
        $query = Product::find()->alias('p')->active('p')->with('mainPhoto', 'category');

        // если указан бренд выводим и его
        if ($form->brand) {
            $query->andWhere(['p.brand_id' => $form->brand]);
        }

        // если указали категорию - добавляем в выборку категорию
        if ($form->category) {
            if ($category = Category::findOne($form->category)) {
                $ids = ArrayHelper::merge([$form->category], $category->getChildren()->select('id')->column());
                $query->joinWith(['categoryAssignments ca'], false);
                $query->andWhere(['or', ['p.category_id' => $ids], ['ca.category_id' => $ids]]);
            } else {
                $query->andWhere(['p.id' => 0]);
            }
        }

        if ($form->values) {
            $productIds = null;
            // проходим по заполненным значениям
            foreach ($form->values as $value) {
                if ($value->isFilled()) {
                    // находим в промежуточной таблице характеристики с нужным нам ид
                    // прим: если ищем по весу то: "найди все значения,
                    // у которых characteristic_id = значению веса (идшнику)"
                    $q = Value::find()->andWhere(['characteristic_id' => $value->getId()]);

                    // если из формы пришло from и to то ищем на значение больше/меньше или равно
                    // value AS SIGNED - приводим значение values к целому со знаком
                    $q->andFilterWhere(['>=', 'CAST(value AS SIGNED)', $value->from]);
                    $q->andFilterWhere(['<=', 'CAST(value AS SIGNED)', $value->to]);
                    // если из формы пришло $value->equal, сравниваем на равенство
                    $q->andFilterWhere(['value' => $value->equal]);

                    // выбираем из таблицы поле product_id
                    // прим.продолжение.: "...и найди все идшники товаров у которых совпали значения)
                    $foundIds = $q->select('product_id')->column();
                    // сохраняем идшники и повторяем цикл
                    // array_intersect используем чтобы из двух массивов найти пересекающиеся значения
                    // после всех циклов (например по 5 атрибутам) в $productIds останутся только те ид
                    // которые нашлись для каждого из этих 5 товаров
                    // выводим только те продукты которые удовлетворяют всем условиям поиска
                    $productIds = $productIds === null ? $foundIds : array_intersect($productIds, $foundIds);
                }
            }
            if ($productIds !== null) {
                $query->andWhere(['p.id' => $productIds]);
            }
        }

        // поиск по тексу, ищем либо по коду товара либо по названию
        if (!empty($form->text)) {
            $query->andWhere(['or', ['like', 'code', $form->text], ['like', 'name', $form->text]]);
        }

        // группируем значения чтобы при джойнах не было проблем
        $query->groupBy('p.id');

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
                ],
            ]
        ]);
    }

    public function getWishList($userId): ActiveDataProvider
    {
        // возвращаем провайдер данных
        return new ActiveDataProvider([
            // находим все продукты
            'query' => Product::find()
                // присваиваем алиас, оставляем только активные
                ->alias('p')->active('p')
                // джойним таблицу wishlistItems (INNER JOIN - показываем только общие записи обоих таблиц)
                ->joinWith('wishlistItems w', false, 'INNER JOIN')
                // в таблице wishlistItems user_id должен быть равен идшнику переданного пользователя
                ->andWhere(['w.user_id' => $userId]),
            'sort' => false,
        ]);
    }
}