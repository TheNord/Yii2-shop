<?php

namespace shop\entities;

/** Дополнительный класс для хранения мета-данных в одной пачке */
class Meta
{
    public $title;
    public $description;
    public $keywords;

    public function __construct($title, $description, $keywords)
    {
        $this->title = $title;
        $this->description = $description;
        $this->keywords = $keywords;
    }
}