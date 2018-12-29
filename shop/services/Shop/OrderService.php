<?php

namespace shop\services\Shop;

use shop\cart\Cart;
use shop\cart\CartItem;
use shop\entities\Shop\Order\CustomerData;
use shop\entities\Shop\Order\DeliveryData;
use shop\entities\Shop\Order\Order;
use shop\entities\Shop\Order\OrderItem;
use shop\forms\Shop\Order\OrderForm;
use shop\repositories\Shop\DeliveryMethodRepository;
use shop\repositories\Shop\OrderRepository;
use shop\repositories\Shop\ProductRepository;
use shop\repositories\UserRepository;
use shop\services\TransactionManager;

class OrderService
{
    private $cart;
    private $orders;
    private $products;
    private $users;
    private $deliveryMethods;
    private $transaction;

    public function __construct(
        Cart $cart,
        OrderRepository $orders,
        ProductRepository $products,
        UserRepository $users,
        DeliveryMethodRepository $deliveryMethods,
        TransactionManager $transaction
    )
    {
        $this->cart = $cart;
        $this->orders = $orders;
        $this->products = $products;
        $this->users = $users;
        $this->deliveryMethods = $deliveryMethods;
        $this->transaction = $transaction;
    }

    /**
     * Оформляем (формируем) заказ
     *
     * передаем ид пользователя и форму оформления заказа
     */
    public function checkout($userId, OrderForm $form): Order
    {
        $user = $this->users->get($userId);

        // создаем временное хранилище продуктов, чтобы после сообщить в базу о измении остатков
        $products = [];

        // проходим циклом по элементам из корзины
        $items = array_map(function (CartItem $item) use (&$products) {
            // получаем продукт
            $product = $item->getProduct();
            // уменьшам количество товаров
            $product->checkout($item->getModificationId(), $item->getQuantity());
            // сохраняем полученный продукт во временный массив
            $products[] = $product;
            // создаем OrderItem передавая полученный продукт, модификацию, цену и количество
            return OrderItem::create(
                $product,
                $item->getModificationId(),
                $item->getPrice(),
                $item->getQuantity()
            );
           // получаем продукты из корзины для прохода циклом
        }, $this->cart->getItems());

        // создаем новый Заказ
        $order = Order::create(
            $user->id,
            new CustomerData(
                $form->customer->phone,
                $form->customer->name
            ),
            $items,
            // передаем полную стоимость всех товаров
            $this->cart->getCost()->getTotal(),
            $form->note
        );

        // устанавливаем данные о доставке
        $order->setDeliveryInfo(
            $this->deliveryMethods->get($form->delivery->method),
            new DeliveryData(
                $form->delivery->index,
                $form->delivery->address
            )
        );

        // оборачиваем создание в транзакцию
        $this->transaction->wrap(function () use ($order, $products) {
            // сохраняем сам заказ
            $this->orders->save($order);
            // изменяем товары у которых изменили количество
            foreach ($products as $product) {
                $this->products->save($product);
            }
            // очищаем корзину
            $this->cart->clear();
        });

        return $order;
    }
}