<?php

namespace common\bootstrap;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use shop\cart\Cart;
use shop\cart\cost\calculator\SimpleCost;
use shop\cart\cost\calculator\DynamicCost;
use shop\cart\storage\SessionStorage;
use shop\services\ContactService;
use yii\base\BootstrapInterface;
use yii\mail\MailerInterface;
use yii\caching\Cache;


class SetUp implements BootstrapInterface
{

    public function bootstrap($app)
    {
        $container = \Yii::$container;

        // внедряем клиента ES
        $container->setSingleton(Client::class, function () {
            // подключаем сервер ES
            return ClientBuilder::create()->build();
        });

        // Устанавливаем для MailerInterface $app->mailer
        $container->setSingleton(MailerInterface::class, function () use ($app) {
            return $app->mailer;
        });

        // настраиваем конструктор ContactService
        $container->setSingleton(ContactService::class, [], [
            $app->params['adminEmail']
        ]);

        // инжектим корзину
        $container->setSingleton(Cart::class, function () {
            // создаем корзину
            return new Cart(
                // в качестве хранилища выбираем сессии, ключ под которым будем сохранять cart
                new SessionStorage('cart'),
                // калькулятор стоимости
                new DynamicCost(new SimpleCost())
            );
        });

        // подставляем класс через конструктор вместо Yii::$app->cache
        $container->setSingleton(Cache::class, function () use ($app) {
            return $app->cache;
        });
    }
}