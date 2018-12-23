<?php

namespace shop\entities\behaviors;

use shop\entities\Meta;
use yii\base\Behavior;
use yii\base\Event;
use yii\db\ActiveRecord;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;

/** Поведение для мета данных */
class MetaBehavior extends Behavior
{
    public $attribute = 'meta';
    public $jsonAttribute = 'meta_json';

    public function events(): array
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'onAfterFind',
            ActiveRecord::EVENT_BEFORE_INSERT => 'onBeforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'onBeforeSave',
        ];
    }

    /** Перегоняем данные из meta_json в класс Meta
     * afterFind - выполняется при получении записи из бд (findOne)
     */
    public function onAfterFind(Event $event): void
    {
        // получаем информацию о событии из Эвента, класс вызвавший событие (class Brand)
        $model = $event->sender;
        // получаем значение meta_json из бд, декодируем его
        $meta = Json::decode($model->getAttribute($this->jsonAttribute));
        // записываем в свойство meta, новый объект Meta на основе полей
        // полученных через декодирование
        $model->{$this->attribute} = new Meta(
            ArrayHelper::getValue($meta, 'title'),
            ArrayHelper::getValue($meta, 'description'),
            ArrayHelper::getValue($meta, 'keywords')
        );
    }

    /** Перегоняем данные из класса Meta в meta_json
     * afterSave - выполняется при сохранении записи из бд (save)
     */
    public function onBeforeSave(Event $event): void
    {
        // информация о событии (class Brand)
        $model = $event->sender;
        // кодируем данные из класса Meta (свойства meta) и записываем
        // их в поле meta_json
        $model->setAttribute('meta_json', Json::encode([
            'title' => $model->{$this->attribute}->title,
            'description' => $model->{$this->attribute}->description,
            'keywords' => $model->{$this->attribute}->keywords,
        ]));
    }
}