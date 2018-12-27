<?php

namespace shop\readModels\Shop;

use Elasticsearch\Client;
use shop\entities\Shop\Category;
use yii\helpers\ArrayHelper;
use shop\readModels\Shop\views\CategoryView;

class CategoryReadRepository
{
    private $client;
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /** Получаем главную категорию, для вывода всех рубрик */
    public function getRoot(): Category
    {
        return Category::find()->roots()->one();
    }

    public function getAll(): array
    {
        return Category::find()->andWhere(['>', 'depth', 0])->orderBy('lft')->all();
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

        $aggs = $this->client->search([
            'index' => 'shop',
            'type' => 'products',
            'body' => [
                'size' => 0,
                'aggs' => [
                    'group_by_category' => [
                        'terms' => [
                            'field' => 'categories',
                        ]
                    ]
                ],
            ],
        ]);
        $counts = ArrayHelper::map($aggs['aggregations']['group_by_category']['buckets'], 'key', 'doc_count');
        return array_map(function (Category $category) use ($counts) {
            return new CategoryView($category, ArrayHelper::getValue($counts, $category->id, 0));
        }, $query->all());
    }
}