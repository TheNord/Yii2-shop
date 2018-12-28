<?php

namespace shop\entities\Shop\Product;

use yii\db\ActiveRecord;
use yii\web\UploadedFile;
use yiidreamteam\upload\ImageUploadBehavior;
use shop\services\WaterMarker;

/**
 * @property integer $id
 * @property string $file
 * @property integer $sort
 *
 * @mixin ImageUploadBehavior
 */
class Photo extends ActiveRecord
{
    public static function create(UploadedFile $file): self
    {
        $photo = new static();
        $photo->file = $file;
        return $photo;
    }

    public function setSort($sort): void
    {
        $this->sort = $sort;
    }

    public function isIdEqualTo($id): bool
    {
        return $this->id == $id;
    }

    public static function tableName(): string
    {
        return '{{%shop_photos}}';
    }

    public function behaviors(): array
    {
        return [
            [
                // подключаем ImageUploadBehavior
                'class' => ImageUploadBehavior::className(),
                // отслеживаем поле file
                'attribute' => 'file',
                // генерируем превьюшки по запросу, генерируем только при отсутствии
                'createThumbsOnRequest' => true,
                // указываем пути к статике
                'filePath' => '@staticRoot/origin/products/[[attribute_product_id]]/[[id]].[[extension]]',
                'fileUrl' => '@static/origin/products/[[attribute_product_id]]/[[id]].[[extension]]',
                'thumbPath' => '@staticRoot/cache/products/[[attribute_product_id]]/[[profile]]_[[id]].[[extension]]',
                'thumbUrl' => '@static/cache/products/[[attribute_product_id]]/[[profile]]_[[id]].[[extension]]',
                // размеры превьюшек, для админ панели и основные
                'thumbs' => [
                    'admin' => ['width' => 100, 'height' => 70],
                    'thumb' => ['width' => 640, 'height' => 480],
                    'cart_list' => ['width' => 150, 'height' => 150],
                    // размер картинки для вывода в каталоге, пример использования
                    // <img src="<?= Html::encode($product->mainPhoto->getThumbFileUrl('file', 'catalog_list'))" class="img-responsive"/>
                    'catalog_list' => ['width' => 228, 'height' => 228],
                    // добавляем вотемарку к изображению в каталоге
                    'catalog_product_main' => ['processor' => [new WaterMarker(750, 1000, '@frontend/web/image/logo.png'), 'process']],
                    'catalog_product_additional' => ['width' => 66, 'height' => 66],
                    // добавляем вотемарку к основному изображению
                    'catalog_origin' => ['processor' => [new WaterMarker(1024, 768, '@frontend/web/image/logo.png'), 'process']],

                ],
            ],
        ];
    }
}