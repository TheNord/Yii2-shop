<?php
return [
    'id' => 'app-common-tests',
    'basePath' => dirname(__DIR__),
    'components' => [
        'user' => [
            'class' => 'yii\web\User',
            'identityClass' => 'common\entities\user',
        ],
        'request' => [
            'cookieValidationKey' => '324234fgfdg346346aa',
        ],
    ],
];
