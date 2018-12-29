<?php

namespace shop\readModels\Shop;

use Elasticsearch\Client;
use shop\entities\Shop\Brand;
use shop\entities\Shop\Category;
use shop\entities\Shop\Product\Product;
use shop\entities\Shop\Tag;
use shop\forms\Shop\Search\SearchForm;
use shop\forms\Shop\Search\ValueForm;
use yii\data\ActiveDataProvider;
use yii\data\DataProviderInterface;
use yii\data\Pagination;
use yii\data\Sort;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class ProductReadRepository
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

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
        // создаем пагинацию
        $pagination = new Pagination([
            // выводим до 100 элементов
            'pageSizeLimit' => [15, 100],
            // отключаем валидацию страницы
            'validatePage' => false,
        ]);

        // создаем сортировщика
        $sort = new Sort([
            'defaultOrder' => ['id' => SORT_DESC],
            'attributes' => [
                'id',
                'name',
                'price',
                'rating',
            ],
        ]);

        $response = $this->client->search([
            'index' => 'shop',
            'type' => 'products',
            'body' => [
                // отключаем вывод всех данных, оставляем только id
                '_source' => ['id'],
                // задаем паджинаторы
                'from' => $pagination->getOffset(),
                'size' => $pagination->getLimit(),
                // задаем сортировку
                'sort' => array_map(function ($attribute, $direction) {
                    return [$attribute => ['order' => $direction === SORT_ASC ? 'asc' : 'desc']];
                }, array_keys($sort->getOrders()), $sort->getOrders()),
                'query' => [
                    // оборачиваем в bool => must если у нас несколько запросов
                    'bool' => [
                        // склеиваем полученные после фильтров запросы
                        'must' => array_merge(
                            // через array_filter откинем пустые элементы (фильтры)
                            array_filter([
                                // если категория указана делаем точный поиск (совпадение)
                                // через 'term' => и ищем по ид категории из формы
                                !empty($form->category) ? ['term' => ['categories' => $form->category]] : false,
                                !empty($form->brand) ? ['term' => ['brand' => $form->brand]] : false,
                                // multi_match - поиск по множеству полей
                                !empty($form->text) ? ['multi_match' => [
                                    'query' => $form->text,
                                    // указываем множетель ^3 у поля name, для указания его важности (веса)
                                    'fields' => [ 'name^3', 'description' ]
                                ]] : false,
                            ]),
                            // через array_map формируем массив nested для каждого элемента в системе
                            array_map(function (ValueForm $value) {
                                // если ищем во вложенных элементах то указываем 'nested'
                                return ['nested' => [
                                    'path' => 'values',
                                    'query' => [
                                        'bool' => [
                                            'must' => array_filter([
                                                // характеристика должна совпадать с ид характеристики
                                                ['match' => ['values.characteristic' => $value->getId()]],
                                                // текстовое значение характеристики должно совпадать с текстом значения
                                                !empty($value->equal) ? ['match' => ['values.value_string' => $value->equal]] : false,
                                                // проверяем что значение должно быть большее значения от (from)
                                                !empty($value->from) ? ['range' => ['values.value_int' => ['gte' => $value->from]]] : false,
                                                // и меньше значения до (to)
                                                !empty($value->to) ? ['range' => ['values.value_int' => ['lte' => $value->to]]] : false,
                                            ]),
                                        ],
                                    ],
                                ]];
                                // передаем из формы значения
                                // через array_filter фильтруем только те которые заполненны isFilled
                            }, array_filter($form->values, function (ValueForm $value) { return $value->isFilled(); }))
                        )
                    ],
                ],
            ],
        ]);

        // получаем идшники из результата поиска
        $ids = ArrayHelper::getColumn($response['hits']['hits'], '_source.id');

        // получаем продукты по полученным идшникам
        if ($ids) {
            $query = Product::find()
                ->active()
                ->with('mainPhoto')
                ->andWhere(['id' => $ids])
                // сортируем в виде: ORDER BY FIELD(id, 5, 2, 7, 78, 14)
                // имплодим идшники через запятую
                // оборачиваем в Yii DB Expression чтобы не экранировались значения через фильтры
                ->orderBy(new Expression('FIELD(id,' . implode(',', $ids) . ')'));
        } else {
            $query = Product::find()->andWhere(['id' => 0]);
        }

        return new SimpleActiveDataProvider([
            'query' => $query,
            // указываем общее число найденных элементов
            'totalCount' => $response['hits']['total'],
            'pagination' => $pagination,
            'sort' => $sort,
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