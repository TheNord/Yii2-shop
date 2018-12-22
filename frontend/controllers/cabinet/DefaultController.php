<?php

namespace frontend\controllers\cabinet;

use yii\filters\AccessControl;
use yii\web\Controller;

class DefaultController extends Controller
{
    public function behaviors(): array
    {
        return [
            // доступ только для авторизованных пользователей
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
}