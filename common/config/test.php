<?php
return [
    'id' => 'app-common-tests',
    'basePath' => dirname(__DIR__),
    'components' => [
        'user' => [
            'class' => 'yii\web\User',
            'identityClass' => 'shop\entities\User',
        ],
        'request' => [
            'cookieValidationKey' => '324234fgfdg346346aa',
        ],
    ],
];
