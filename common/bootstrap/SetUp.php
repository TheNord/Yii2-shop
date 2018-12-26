<?php

namespace common\bootstrap;

use shop\services\ContactService;
use yii\base\BootstrapInterface;
use yii\mail\MailerInterface;
use yii\caching\Cache;


class SetUp implements BootstrapInterface
{

    public function bootstrap($app)
    {
        $container = \Yii::$container;

        // Устанавливаем для MailerInterface $app->mailer
        $container->setSingleton(MailerInterface::class, function () use ($app) {
            return $app->mailer;
        });

        // настраиваем конструктор ContactService
        $container->setSingleton(ContactService::class, [], [
            $app->params['adminEmail']
        ]);

        // подставляем класс через конструктор вместо Yii::$app->cache
        $container->setSingleton(Cache::class, function () use ($app) {
            return $app->cache;
        });
    }
}