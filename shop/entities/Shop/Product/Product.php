<?php

namespace shop\entities\Shop\Product;

use shop\entities\behaviors\MetaBehavior;
use lhs\Yii2SaveRelationsBehavior\SaveRelationsBehavior;
use shop\entities\Meta;
use shop\entities\Shop\Brand;
use shop\entities\Shop\Category;
use shop\entities\Shop\Product\queries\ProductQuery;
use shop\entities\Shop\Tag;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\web\UploadedFile;

/**
 * @property integer $id
 * @property integer $created_at
 * @property string $code
 * @property string $name
 * @property integer $category_id
 * @property integer $brand_id
 * @property integer $price_old
 * @property integer $price_new
 * @property integer $rating
 * @property integer $main_photo_id
 * @property string $description
 * @property integer $status
 *
 * @property Meta $meta
 * @property Brand $brand
 * @property Category $category
 * @property Category[] $categories
 * @property CategoryAssignment[] $categoryAssignments
 * @property Tag[] $tags
 * @property TagAssignment[] $tagAssignments
 * @property RelatedAssignment[] $relatedAssignments
 * @property Modification[] $modifications
 * @property Value[] $values
 * @property Photo $mainPhoto
 * @property Photo[] $photos
 * @property Review[] $reviews
 */
class Product extends ActiveRecord
{
    const STATUS_DRAFT = 0;
    const STATUS_ACTIVE = 1;

    public $meta;

    public static function create($brandId, $categoryId, $code, $name, $description, Meta $meta): self
    {
        $product = new static();
        $product->brand_id = $brandId;
        $product->category_id = $categoryId;
        $product->code = $code;
        $product->name = $name;
        $product->description = $description;
        $product->meta = $meta;
        $product->status = self::STATUS_DRAFT;
        $product->created_at = time();
        return $product;
    }

    public function edit($brandId, $code, $name, $description, Meta $meta): void
    {
        $this->brand_id = $brandId;
        $this->code = $code;
        $this->name = $name;
        $this->description = $description;
        $this->meta = $meta;
    }

    public function activate(): void
    {
        if ($this->isActive()) {
            throw new \DomainException('Product is already active.');
        }
        $this->status = self::STATUS_ACTIVE;
    }

    public function draft(): void
    {
        if ($this->isDraft()) {
            throw new \DomainException('Product is already draft.');
        }
        $this->status = self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    public function isDraft(): bool
    {
        return $this->status == self::STATUS_DRAFT;
    }

    public function setPrice($new, $old): void
    {
        $this->price_new = $new;
        $this->price_old = $old;
    }

    // Атрибуты

    public function setValue($id, $value): void
    {
        // получаем привязанные значения из промежуточной таблицы
        $values = $this->values;
        // проходим по ним циклом
        foreach ($values as $val) {
            // проверяем, если у него characteristic_id равно id
            if ($val->isForCharacteristic($id)) {
                // изменяем значение атрибута
                $val->change($value);
                // перезаписываем массив values
                $this->values = $values;
                return;
            }
        }
        // создаем новое значение атрибута
        $values[] = Value::create($id, $value);
        // перезаписываем массив values
        $this->values = $values;
    }

    /** Получение значения атрибутов
     *  Возвращает значение атрибута у указанного ид
     */
    public function getValue($id): Value
    {
        $values = $this->values;
        foreach ($values as $val) {
            if ($val->isForCharacteristic($id)) {
                return $val;
            }
        }
        return Value::blank($id);
    }

    // Категории

    public function changeMainCategory($categoryId): void
    {
        $this->category_id = $categoryId;
    }

    /** Добавление дополнительных категории */
    public function assignCategory($id): void
    {
        // получаем список дополнительных категорий через связь
        $assignments = $this->categoryAssignments;
        // проходим циклом по списку
        foreach ($assignments as $assignment) {
            // выходим если такая категория уже есть
            if ($assignment->isForCategory($id)) {
                return;
            }
        }
        // иначе добавляем новую категорию к списку
        $assignments[] = CategoryAssignment::create($id);
        // записываем обратно в массив
        $this->categoryAssignments = $assignments;
    }

    /** Удаление дополнительных категорий */
    public function revokeCategory($id): void
    {
        $assignments = $this->categoryAssignments;
        foreach ($assignments as $i => $assignment) {
            if ($assignment->isForCategory($id)) {
                unset($assignments[$i]);
                $this->categoryAssignments = $assignments;
                return;
            }
        }
        throw new \DomainException('Assignment is not found.');
    }

    /** Удаление всех дополнительных категорий */
    public function revokeCategories(): void
    {
        $this->categoryAssignments = [];
    }

    // Фотографии

    public function addPhoto(UploadedFile $file): void
    {
        $photos = $this->photos;
        // добавляем новую фотографию
        $photos[] = Photo::create($file);
        // сортируем фотографии
        $this->updatePhotos($photos);
    }

    public function removePhoto($id): void
    {
        // получаем список фотографий (через связь)
        $photos = $this->photos;
        // проходим циклом
        foreach ($photos as $i => $photo) {
            // удаляем если ид совпадает
            if ($photo->isIdEqualTo($id)) {
                unset($photos[$i]);
                // сортируем фотографии
                $this->updatePhotos($photos);
                return;
            }
        }
        throw new \DomainException('Photo is not found.');
    }

    public function removePhotos(): void
    {
        $this->updatePhotos([]);
    }

    public function movePhotoUp($id): void
    {
        $photos = $this->photos;
        foreach ($photos as $i => $photo) {
            // проверяем совпадение id
            if ($photo->isIdEqualTo($id)) {
                // находим предыдущую фотографию
                if ($prev = $photos[$i - 1] ?? null) {
                    // меняем элементы местами
                    $photos[$i - 1] = $photo;
                    $photos[$i] = $prev;
                    // сортируем фотографии
                    $this->updatePhotos($photos);
                }
                return;
            }
        }
        throw new \DomainException('Photo is not found.');
    }

    public function movePhotoDown($id): void
    {
        $photos = $this->photos;
        foreach ($photos as $i => $photo) {
            if ($photo->isIdEqualTo($id)) {
                // находим следующую фотографию
                if ($next = $photos[$i + 1] ?? null) {
                    // меняем фотографии местами
                    $photos[$i] = $next;
                    $photos[$i + 1] = $photo;
                    // сортируем фотографии
                    $this->updatePhotos($photos);
                }
                return;
            }
        }
        throw new \DomainException('Photo is not found.');
    }

    private function updatePhotos(array $photos): void
    {
        // обходим список фотографий циклом
        foreach ($photos as $i => $photo) {
            // перезаписываем сортировку фотографий
            $photo->setSort($i);
        }
        // присваиваем обратно в photos
        $this->photos = $photos;
        $this->populateRelation('mainPhoto', reset($photos));
    }

    // Тэги

    /** Связываем тэг с продуктом */
    public function assignTag($id): void
    {
        // получаем массив тэгов
        $assignments = $this->tagAssignments;
        // проходим по всему массиву, и если tag_id = id - выходим, тэг уже привязан
        foreach ($assignments as $assignment) {
            if ($assignment->isForTag($id)) {
                return;
            }
        }
        // создаем новую связь
        $assignments[] = TagAssignment::create($id);
        // перезаписываем список тэгов (через поведение)
        $this->tagAssignments = $assignments;
    }

    /** Отвязываем тэг от продукта */
    public function revokeTag($id): void
    {
        $assignments = $this->tagAssignments;
        foreach ($assignments as $i => $assignment) {
            if ($assignment->isForTag($id)) {
                unset($assignments[$i]);
                $this->tagAssignments = $assignments;
                return;
            }
        }
        throw new \DomainException('Assignment is not found.');
    }

    public function revokeTags(): void
    {
        $this->tagAssignments = [];
    }

    // Связанные продукты

    public function assignRelatedProduct($id): void
    {
        $assignments = $this->relatedAssignments;
        foreach ($assignments as $assignment) {
            if ($assignment->isForProduct($id)) {
                return;
            }
        }
        $assignments[] = RelatedAssignment::create($id);
        $this->relatedAssignments = $assignments;
    }

    public function revokeRelatedProduct($id): void
    {
        $assignments = $this->relatedAssignments;
        foreach ($assignments as $i => $assignment) {
            if ($assignment->isForProduct($id)) {
                unset($assignments[$i]);
                $this->relatedAssignments = $assignments;
                return;
            }
        }
        throw new \DomainException('Assignment is not found.');
    }

    // Модификации

    /** Получение модификации по ид */
    public function getModification($id): Modification
    {
        // проходим все модификации
        foreach ($this->modifications as $modification) {
            // возвращаем если идшник совпал
            if ($modification->isIdEqualTo($id)) {
                return $modification;
            }
        }
        throw new \DomainException('Modification is not found.');
    }

    /** Добавление новой модификации к продукту */
    public function addModification($code, $name, $price): void
    {
        // получаем список модификаций
        $modifications = $this->modifications;
        // проверяем на ранее добавленную модификацию
        foreach ($modifications as $modification) {
            if ($modification->isCodeEqualTo($code)) {
                throw new \DomainException('Modification already exists.');
            }
        }
        // создаем новую модификацию
        $modifications[] = Modification::create($code, $name, $price);
        // прокидываем в поведение
        $this->modifications = $modifications;
    }

    public function editModification($id, $code, $name, $price): void
    {
        $modifications = $this->modifications;
        foreach ($modifications as $i => $modification) {
            if ($modification->isIdEqualTo($id)) {
                $modification->edit($code, $name, $price);
                $this->modifications = $modifications;
                return;
            }
        }
        throw new \DomainException('Modification is not found.');
    }

    public function removeModification($id): void
    {
        $modifications = $this->modifications;
        foreach ($modifications as $i => $modification) {
            if ($modification->isIdEqualTo($id)) {
                unset($modifications[$i]);
                $this->modifications = $modifications;
                return;
            }
        }
        throw new \DomainException('Modification is not found.');
    }

    // Отзывы

    /**
     * Добавление отзыва, передаем пользователя, оценку и текст
     */
    public function addReview($userId, $vote, $text): void
    {
        // получаем список отзывов товара
        $reviews = $this->reviews;
        // добавляем отзыв к массиву
        $reviews[] = Review::create($userId, $vote, $text);
        // записываем в список отзывов и обновляем товар
        $this->updateReviews($reviews);
    }

    /** Редактирование рейтинга */
    public function editReview($id, $vote, $text): void
    {
        $reviews = $this->reviews;
        foreach ($reviews as $i => $review) {
            if ($review->isIdEqualTo($id)) {
                $review->edit($vote, $text);
                $this->updateReviews($reviews);
                return;
            }
        }
        throw new \DomainException('Review is not found.');
    }

    /** Активация отзыва */
    public function activateReview($id): void
    {
        $reviews = $this->reviews;
        foreach ($reviews as $i => $review) {
            if ($review->isIdEqualTo($id)) {
                $review->activate();
                $this->updateReviews($reviews);
                return;
            }
        }
        throw new \DomainException('Review is not found.');
    }

    /** Деактивация отзыва */
    public function draftReview($id): void
    {
        $reviews = $this->reviews;
        foreach ($reviews as $i => $review) {
            if ($review->isIdEqualTo($id)) {
                $review->draft();
                $this->updateReviews($reviews);
                return;
            }
        }
        throw new \DomainException('Review is not found.');
    }

    public function removeReview($id): void
    {
        $reviews = $this->reviews;
        foreach ($reviews as $i => $review) {
            if ($review->isIdEqualTo($id)) {
                unset($reviews[$i]);
                $this->updateReviews($reviews);
                return;
            }
        }
        throw new \DomainException('Review is not found.');
    }

    private function updateReviews(array $reviews): void
    {
        $amount = 0;
        $total = 0;

        foreach ($reviews as $review) {
            if ($review->isActive()) {
                // добавляем количество отзывов
                $amount++;
                // добавляем оценку
                $total += $review->getRating();
            }
        }

        // сохраняем отзывы
        $this->reviews = $reviews;
        // обновляем рейтинг товара (среднее арифметическое)
        $this->rating = $amount ? $total / $amount : null;
    }

    /** Отображаем только активные товары */
    public static function find(): ProductQuery
    {
        return new ProductQuery(static::class);
    }

    ##########################
    // Связь с брендом, категорией и дополнительными категориями

    public function getBrand(): ActiveQuery
    {
        return $this->hasOne(Brand::class, ['id' => 'brand_id']);
    }

    public function getCategory(): ActiveQuery
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    public function getCategoryAssignments(): ActiveQuery
    {
        return $this->hasMany(CategoryAssignment::class, ['product_id' => 'id']);
    }

    /** Прямая связь на категории через промежуточную таблицу
     * Для получения всех дополнительных категорий
     */
    public function getCategories(): ActiveQuery
    {
        return $this->hasMany(Category::class, ['id' => 'category_id'])->via('categoryAssignments');
    }

    public function getValues(): ActiveQuery
    {
        return $this->hasMany(Value::class, ['product_id' => 'id']);
    }

    public function getPhotos(): ActiveQuery
    {
        return $this->hasMany(Photo::class, ['product_id' => 'id'])->orderBy('sort');
    }

    /** Получаем основную фотографию (для вывода на главных страницах) */
    public function getMainPhoto(): ActiveQuery
    {
        return $this->hasOne(Photo::class, ['id' => 'main_photo_id']);
    }

    public function getTags(): ActiveQuery
    {
        return $this->hasMany(Tag::class, ['id' => 'tag_id'])->via('tagAssignments');
    }

    public function getTagAssignments(): ActiveQuery
    {
        return $this->hasMany(TagAssignment::class, ['product_id' => 'id']);
    }

    public function getRelateds(): ActiveQuery
    {
        return $this->hasMany(Product::class, ['id' => 'related_id'])->via('relatedAssignments');
    }

    public function getRelatedAssignments(): ActiveQuery
    {
        return $this->hasMany(RelatedAssignment::class, ['product_id' => 'id']);
    }

    public function getModifications(): ActiveQuery
    {
        return $this->hasMany(Modification::class, ['product_id' => 'id']);
    }

    public function getReviews(): ActiveQuery
    {
        return $this->hasMany(Review::class, ['product_id' => 'id']);
    }

    ##########################
    public static function tableName(): string
    {
        return '{{%shop_products}}';
    }

    public function behaviors(): array
    {
        return [
            // Подключаем поведение (для мета данных)
            MetaBehavior::className(),
            [
                // Подключаем сохранение для связанных таблиц
                // дает возможность сохранить информацию в связывающую таблицу
                // $this->reviews = $reviews;
                'class' => SaveRelationsBehavior::className(),
                'relations' => ['categoryAssignments', 'tagAssignments', 'relatedAssignments', 'modifications', 'values', 'photos', 'reviews'],
            ],
        ];
    }

    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    public function beforeDelete(): bool
    {
        if (parent::beforeDelete()) {
            foreach ($this->photos as $photo) {
                $photo->delete();
            }
            return true;
        }
        return false;
    }

    public function afterSave($insert, $changedAttributes): void
    {
        // получаем связанные записи
        $related = $this->getRelatedRecords();

        parent::afterSave($insert, $changedAttributes);

        // добавляем главное фото к товару
        if (array_key_exists('mainPhoto', $related)) {
            // генерируем голый sql запрос чтобы избежать зацикливания
            $this->updateAttributes(['main_photo_id' => $related['mainPhoto'] ? $related['mainPhoto']->id : null]);
        }
    }
}