<?php

namespace frontend\controllers\cabinet;

use shop\readModels\Shop\OrderReadRepository;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class OrderController extends Controller
{
    public $layout = 'cabinet';
    private $orders;

    public function __construct($id, $module, OrderReadRepository $orders, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->orders = $orders;
    }

    public function behaviors(): array
    {
        return [
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

    public function actionIndex()
    {
        $dataProvider = $this->orders->getOwm(\Yii::$app->user->id);
        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        if (!$order = $this->orders->findOwn(\Yii::$app->user->id, $id)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        return $this->render('view', [
            'order' => $order,
        ]);
    }
}