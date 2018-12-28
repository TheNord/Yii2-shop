<?php

namespace shop\cart\storage;

use Yii;

class SessionStorage implements StorageInterface
{
    private $key;

    public function __construct($key)
    {
        $this->key = $key;
    }

    /** Загрузка товаров по ключу из сессии */
    public function load(): array
    {
        return Yii::$app->session->get($this->key, []);
    }

    /** Сохранение товаров по ключу в сессию */
    public function save(array $items): void
    {
        Yii::$app->session->set($this->key, $items);
    }
}