<?php
/* @var $this yii\web\View */
/* @var $dataProvider yii\data\DataProviderInterface */

/* @var $category shop\entities\Shop\Category */

use yii\helpers\Html;

$this->title = $category->getSeoTitle();

$this->registerMetaTag(['name' => 'description', 'content' => $category->meta->description]);
$this->registerMetaTag(['name' => 'keywords', 'content' => $category->meta->keywords]);

$this->params['breadcrumbs'][] = ['label' => 'Catalog', 'url' => ['index']];
// проходим по родителям категории
foreach ($category->parents as $parent) {
    // отображаем все кроме родительской
    if (!$parent->isRoot()) {
        // вставляем имя и ссылку в хлебные крошки
        $this->params['breadcrumbs'][] = ['label' => $parent->name, 'url' => ['category', 'id' => $parent->id]];
    }
}
$this->params['breadcrumbs'][] = $category->name;

// помечаем активную категорию для виджета категорий
$this->params['active_category'] = $category;
?>

    <h1><?= Html::encode($category->getHeadingTile()) ?></h1>

<?= $this->render('_subcategories', [
    'category' => $category
]) ?>

<?php if (trim($category->description)): ?>
    <div class="panel panel-default">
        <div class="panel-body">
            <?= Yii::$app->formatter->asNtext($category->description) ?>
        </div>
    </div>
<?php endif; ?>

<?= $this->render('_list', [
    'dataProvider' => $dataProvider
]) ?>