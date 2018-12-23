<?php

namespace shop\forms\manage\Shop\Product;

use yii\base\Model;
use yii\web\UploadedFile;

class PhotosForm extends Model
{
    /**
     * @var UploadedFile[]
     */
    public $files;

    public function rules(): array
    {
        // применяем правило image ко всем файлам (each)
        return [
            ['files', 'each', 'rule' => ['image']],
        ];
    }

    /** Получаем файлы перед валидацией */
    public function beforeValidate(): bool
    {
        if (parent::beforeValidate()) {
            $this->files = UploadedFile::getInstances($this, 'files');
            return true;
        }
        return false;
    }
}