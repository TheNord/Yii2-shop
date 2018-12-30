<?php

use yii\bootstrap\ActiveForm;
use yii\captcha\Captcha;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $post \shop\entities\Blog\Post\Post */
/* @var $items \frontend\widgets\Blog\CommentView[] */
/* @var $count integer */
/* @var $commentForm \shop\forms\Blog\CommentForm */
?>

    <div id="comments" class="inner-bottom-xs">
        <h2>Comments</h2>
        <!-- Проходим циклом по первому уровню комментариев -->
        <?php foreach ($items as $item): ?>
            <?= $this->render('_comment', ['item' => $item]) ?>
        <?php endforeach; ?>
    </div>

    <div id="reply-block" class="leave-reply">
        <?php $form = ActiveForm::begin([
            'action' => ['comment', 'id' => $post->id],
        ]); ?>

        <?= Html::activeHiddenInput($commentForm, 'parentId') ?>
        <?= $form->field($commentForm, 'text')->textarea(['rows' => 5]) ?>

        <div class="form-group">
            <?= Html::submitButton('Send own comment', ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

<?php $this->registerJs("
    // навешиваемся на клик по кнопке comment-reply
    jQuery(document).on('click', '#comments .comment-reply', function () {
        // извлекаем текущую ссылку по которой кликнули
        var link = jQuery(this);
        // берем форму создания комментария
        var form = jQuery('#reply-block');
        // используем closest и поднимается наверх пока не найдем comment-item (_comment)
        // так мы найдем тот комментарий в котором находится данная ссылка по которой кликнули
        var comment = link.closest('.comment-item');
        // из комментария извлекаем data-id и заполняем им в форме скрытое поле CommentForm[parentId]
        jQuery('#commentform-parentid').val(comment.data('id'));
        // перекидываем форму создания комментария под выбранный комментарий, под reply-block
        form.detach().appendTo(comment.find('.reply-block:first'));
        return false;
    });
"); ?>