<?php

namespace console\controllers;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use shop\entities\Shop\Category;
use shop\entities\Shop\Product\Product;
use shop\entities\Shop\Product\Value;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class SearchController extends Controller
{
    private $client;

    public function __construct($id, $module, Client $client, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->client = $client;
    }

    public function actionReindex(): void
    {
        // Получаем все активные товары со связями с: категориями, связанными категориями, связанными тэгами,
        // и атрибутами
        $query = Product::find()
            ->active()
            ->with(['category', 'categoryAssignments', 'tagAssignments', 'values'])
            ->orderBy('id');

        $this->stdout('Clearing' . PHP_EOL);

        // удаляем старые данные в индексе
        try {
            $this->client->indices()->delete([
                'index' => 'shop'
            ]);
        } catch (Missing404Exception $e) {
            $this->stdout('Index is empty' . PHP_EOL);
        }

        $this->stdout('Creating of index' . PHP_EOL);

        $this->client->indices()->create([
            'index' => 'shop',
            'body' => [
                'mappings' => [
                    'products' => [
                        '_source' => [
                            'enabled' => true,
                        ],
                        'properties' => [
                            'id' => [
                                'type' => 'integer',
                            ],
                            'name' => [
                                'type' => 'text',
                            ],
                            'description' => [
                                'type' => 'text',
                            ],
                            'price' => [
                                'type' => 'integer',
                            ],
                            'rating' => [
                                'type' => 'float',
                            ],
                            'brand' => [
                                'type' => 'integer',
                            ],
                            'categories' => [
                                'type' => 'integer',
                            ],
                            'tags' => [
                                'type' => 'integer',
                            ],
                            'values' => [
                                'type' => 'nested',
                                'properties' => [
                                    'characteristic' => [
                                        'type' => 'integer'
                                    ],
                                    'value_string' => [
                                        'type' => 'keyword',
                                    ],
                                    'value_int' => [
                                        'type' => 'integer',
                                    ],
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->stdout('Indexing of products' . PHP_EOL);

        // проходим циклом по всем продуктам (each - загрузит пачками по 100шт, вместо всех сразу)
        foreach ($query->each() as $product) {
            /** @var Product $product */
            $this->stdout('Product #' . $product->id . PHP_EOL);

            // добавляем продукт в индекс
            $this->client->index([
                // название индекса
                'index' => 'shop',
                // тип (аналог таблиц mysql)
                'type' => 'products',
                // ид под которым будем сохранять
                'id' => $product->id,
                // тело, основная информация
                'body' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => strip_tags($product->description),
                    'price' => $product->price_new,
                    'rating' => $product->rating,
                    'brand' => $product->brand_id,
                    // в категории записываем массив из идшников
                    'categories' => ArrayHelper::merge(
                        [$product->category->id],
                        ArrayHelper::getColumn($product->category->parents, 'id'),
                        ArrayHelper::getColumn($product->categories, 'id'),
                        array_reduce(array_map(function (Category $category) {
                            return ArrayHelper::getColumn($category->parents, 'id');
                        }, $product->categories), 'array_merge', [])
                    ),
                    'tags' => ArrayHelper::getColumn($product->tagAssignments, 'tag_id'),
                    // для упрощенной записи записываем характеристики и в строку и в число
                    // строке не будет переведена в число, не нужно возиться с разделением вручную
                    'values' => array_map(function (Value $value) {
                        return [
                            'characteristic' => $value->characteristic_id,
                            'value_string' => (string)$value->value,
                            'value_int' => (int)$value->value,
                        ];
                    }, $product->values),
                ],
            ]);
        }
        $this->stdout('Done!' . PHP_EOL);
    }
}