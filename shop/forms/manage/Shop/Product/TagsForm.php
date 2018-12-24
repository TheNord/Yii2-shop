<?php

namespace shop\forms\manage\Shop\Product;

use shop\entities\Shop\Product\Product;
use shop\entities\Shop\Tag;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * @property array $newNames
 */
class TagsForm extends Model
{
    // массив для существующих тэгов
    public $existing = [];
    // поле для ввода новых тэгов
    public $textNew;

    public function __construct(Product $product = null, $config = [])
    {
        if ($product) {
            // записываем существующие тэги в массив по связи tagAssignments, по полю tag_id
            $this->existing = ArrayHelper::getColumn($product->tagAssignments, 'tag_id');
        }
        parent::__construct($config);
    }

    public function tagsList(): array
    {
        return ArrayHelper::map(Tag::find()->orderBy('name')->asArray()->all(), 'id', 'name');
    }

    public function rules(): array
    {
        return [
            ['existing', 'each', 'rule' => ['integer']],
            ['existing', 'default', 'value' => []],
            ['textNew', 'string'],
        ];
    }

    /** Парсер для новых тэгов */
    public function getNewNames(): array
    {
        // удаляем пробелы и разделяем через запятую
        return array_filter(array_map('trim', preg_split('#\s*,\s*#i', $this->textNew)));
    }
}