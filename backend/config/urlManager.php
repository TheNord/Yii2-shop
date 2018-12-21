<?php

return [
    'class' => 'yii\web\UrlManager',
    'hostInfo' => $params['backendHostInfo'],
    'enablePrettyUrl' => true,
    'showScriptName' => false,
    'rules' => [
        [
            'pattern'=>'/',
            'route' => 'site/index',
            'suffix' => '',
        ],
        '<action:\w+>' => 'site/<action>',
    ],
];