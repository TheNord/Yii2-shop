<?php

namespace shop\cart;

use shop\entities\Shop\Product\Modification;
use shop\entities\Shop\Product\Product;

class CartItem
{
    private $product;
    private $modificationId;
    private $quantity;

    /** Получаем выбранный продукт, ид модификации и количество продукта */
    public function __construct(Product $product, $modificationId, $quantity)
    {
        if (!$product->canBeCheckout($modificationId, $quantity)) {
            throw new \DomainException('Quantity is too big.');
        }
        $this->product = $product;
        $this->modificationId = $modificationId;
        $this->quantity = $quantity;
    }

    /** Получение ид товара
     * Склеиваем ид продукта и модификации чтобы в корзину можно было
     * Добавлять одинаковые товары но с разной модификацией
     */
    public function getId(): string
    {
        return md5(serialize([$this->product->id, $this->modificationId]));
    }

    /** Получение продукта (для вывода фотографии, названия, ссылки и тд) */
    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getModificationId(): int
    {
        return $this->modificationId;
    }

    /** Получение выбранной модификации товара (для вывода в шаблоне корзины) */
    public function getModification(): ?Modification
    {
        if ($this->modificationId) {
            return $this->product->getModification($this->modificationId);
        }
        return null;
    }

    /** Получаем количество текущего товара */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /** Получение стоимости товара (одного) */
    public function getPrice(): int
    {
        // если у товара указанна модификация
        if ($this->modificationId) {
            // получаем стоимость этой модификации
            return $this->product->getModificationPrice($this->modificationId);
        }
        // если модификации нет возвращаем стоимость товара
        return $this->product->price_new;
    }

    /** Получаем суммарный вес товара */
    public function getWeight(): int
    {
        return $this->product->weight * $this->quantity;
    }

    /** Получение стоимости товара (с учетом количества) */
    public function getCost(): int
    {
        // умножаем стоимость товара (обычного либо с модификацией) на количество шт. товара
        return $this->getPrice() * $this->quantity;
    }

    /** Прибавление новое количества товара к уже имеющемуся в хранилище
     * Возвращаем сами себя, но уже с измененным количеством товара
     */
    public function plus($quantity)
    {
        return new static($this->product, $this->modificationId, $this->quantity + $quantity);
    }

    /** ИЗменение количества товара */
    public function changeQuantity($quantity)
    {
        return new static($this->product, $this->modificationId, $quantity);
    }
}