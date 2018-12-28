<?php

namespace shop\cart;

use shop\cart\cost\calculator\CalculatorInterface;
use shop\cart\cost\Cost;
use shop\cart\storage\StorageInterface;

class Cart
{
    private $storage;
    private $calculator;
    /**
     * @var CartItem[]
     * */
    private $items;

    public function __construct(StorageInterface $storage, CalculatorInterface $calculator)
    {
        $this->storage = $storage;
        $this->calculator = $calculator;
    }

    /**
     * Получение списка всех товаров корзины из хранилища
     *
     * @return CartItem[]
     */
    public function getItems(): array
    {
        $this->loadItems();
        return $this->items;
    }

    /** Получение общего количества товаров в корзине */
    public function getAmount(): int
    {
        $this->loadItems();
        return count($this->items);
    }

    /** Добавление товара в корзину
     * Получаем объект-значение CartItem с нужными методами (получение продукта, цены, количества и тд)
     */
    public function add(CartItem $item): void
    {
        // загружаем товары из хранилища (сохранятся в переменную $this->items
        $this->loadItems();
        // проходим циклом по товарам
        foreach ($this->items as $i => $current) {
            // пытаемся найти товар в хранилище, на случай вдруг он был добавлен ранее
            // в getId() мы считаем идшник по комбинации ид товара + ид модификации
            // на случай если пользователю нужен товар с двумя разными модификациями
            // и чтобы они были отдельными позициями в корзине
            if ($current->getId() == $item->getId()) {
                // прибавляем новое количество товара к старому
                $this->items[$i] = $current->plus($item->getQuantity());
                $this->saveItems();
                return;
            }
        }
        // если товара еще нет в хранилище то записываем и сохраняем
        // в корзину мы сохраняем не отдельные куски значений (массив), а целый объект, для дальнейшей работы с ним
        $this->items[] = $item;
        $this->saveItems();
    }

    /** Установка нового количества выбранному товара */
    public function set($id, $quantity): void
    {
        // загружаем товары корзины из хранилища
        $this->loadItems();
        // проходим циклом по товарам
        foreach ($this->items as $i => $current) {
            // находим нужный товар
            if ($current->getId() == $id) {
                // изменяем количество товара на нужное нам и сохраняем
                $this->items[$i] = $current->changeQuantity($quantity);
                $this->saveItems();
                return;
            }
        }
        throw new \DomainException('Item is not found.');
    }

    public function remove($id): void
    {
        // загружаем товары
        $this->loadItems();
        foreach ($this->items as $i => $current) {
            // находим товар в массиве
            if ($current->getId() == $id) {
                // удаляем товар и сохраняем
                unset($this->items[$i]);
                $this->saveItems();
                return;
            }
        }
        throw new \DomainException('Item is not found.');
    }

    /** Очистка корзины */
    public function clear(): void
    {
        $this->items = [];
        $this->saveItems();
    }

    /** Получение стоимости всех товаров */
    public function getCost(): Cost
    {
        // загружаем товары
        $this->loadItems();
        // получаем стоимость общую товаров передавая калькулятору весь список товаров
        return $this->calculator->getCost($this->items);
    }

    /** Загрузка списка товаров из хранилища */
    private function loadItems(): void
    {
        // кэшируем товары в корзине, чтобы каждый раз не обращаться к сессии
        if ($this->items === null) {
            $this->items = $this->storage->load();
        }
    }

    private function saveItems(): void
    {
        $this->storage->save($this->items);
    }
}