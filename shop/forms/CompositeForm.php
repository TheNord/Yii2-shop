<?php

namespace shop\forms;

use yii\base\Model;
use yii\helpers\ArrayHelper;

abstract class CompositeForm extends Model
{
    /**
     * @var Model[]|array[]
     */
    private $forms = [];

    /** метод для указания списка вложенных форм */
    abstract protected function internalForms(): array;

    /** Переопределяем метод load для загрузки данных в нескольких формах сразу*/
    public function load($data, $formName = null): bool
    {
        // заполняем данными основную форму
        $success = parent::load($data, $formName);
        // проходим циклом по дополнительным формам и заполняем данными
        foreach ($this->forms as $name => $form) {
            // если это массив форм
            if (is_array($form)) {
                // заполняем данные массива переданных дополнительных форм
                $success = Model::loadMultiple($form, $data, $formName === null ? null : $name) && $success;
            } else {
                // иначе заполняем данные одной дополнительной формы
                $success = $form->load($data, $formName !== '' ? null : $name) && $success;
            }
        }
        // возвращаем общий результат
        return $success;
    }

    /** Переопределяем метод validate для валидации данных в нескольких формах сразу*/
    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        // получаем строчные данные из POST запроса для валидации основной формы
        $parentNames = $attributeNames !== null ? array_filter((array)$attributeNames, 'is_string') : null;
        // валидируем основную форму
        $success = parent::validate($parentNames, $clearErrors);
        // проходим циклом по дополнительным формам и валидируем
        foreach ($this->forms as $name => $form) {
            // если это массив форм
            if (is_array($form)) {
                // валидируем данные массива переданных допонительных форм
                $success = Model::validateMultiple($form) && $success;
            } else {
                $innerNames = $attributeNames !== null ? ArrayHelper::getValue($attributeNames, $name) : null;
                // иначе валидируем одну дополнительную форму
                $success = $form->validate($innerNames ?: null, $clearErrors) && $success;
            }
        }
        // возвращаем общий результат
        return $success;
    }

    /** Переписываем магические методы для извлечения и записи списка вложенных форм*/
    public function __get($name)
    {
        if (isset($this->forms[$name])) {
            return $this->forms[$name];
        }
        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        if (in_array($name, $this->internalForms(), true)) {
            $this->forms[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    public function __isset($name)
    {
        return isset($this->forms[$name]) || parent::__isset($name);
    }
}