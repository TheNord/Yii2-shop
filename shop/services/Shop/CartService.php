<?php

namespace shop\services\Shop;

use shop\cart\Cart;
use shop\cart\CartItem;
use shop\repositories\Shop\ProductRepository;

class CartService
{
    private $cart;
    private $products;

    public function __construct(Cart $cart, ProductRepository $products)
    {
        $this->cart = $cart;
        $this->products = $products;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    /** Добавление товара в корзину */
    public function add($productId, $modificationId, $quantity): void
    {
        if ($quantity <= 0) {
            throw new \DomainException('Invalid amount of product.');
        }

        // находим товар в бд
        $product = $this->products->get($productId);
        // проверяем на наличие подификации, если есть добавляем выбранную
        $modId = $modificationId ? $product->getModification($modificationId)->id : null;
        // добавляем товар в корзину, в функцию Cart add передаем объект-значение CartItem с нужными функциями
        // если товар был добавлен ранее приплюсуем нужное количество
        $this->cart->add(new CartItem($product, $modId, $quantity));
    }

    /** Изменение количества товара */
    public function set($id, $quantity): void
    {
        if ($quantity <= 0) {
            throw new \DomainException('Invalid amount of product.');
        }

        $this->cart->set($id, $quantity);
    }

    public function remove($id): void
    {
        $this->cart->remove($id);
    }

    public function clear(): void
    {
        $this->cart->clear();
    }
}
