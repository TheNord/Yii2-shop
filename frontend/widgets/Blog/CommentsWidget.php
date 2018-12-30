<?php

namespace frontend\widgets\Blog;

use shop\entities\Blog\Post\Comment;
use frontend\widgets\Blog\CommentView;
use shop\entities\Blog\Post\Post;
use shop\forms\Blog\CommentForm;
use yii\base\InvalidConfigException;
use yii\base\Widget;

class CommentsWidget extends Widget
{
    /**
     * @var Post
     */
    public $post;

    public function init(): void
    {
        if (!$this->post) {
            throw new InvalidConfigException('Specify the post.');
        }
    }

    public function run(): string
    {
        // инициализируем форму комментариев
        $form = new CommentForm();

        // получаем все комментарии текущего поста
        $comments = $this->post->getComments()
            ->orderBy(['parent_id' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        // строим рекурсивное дерево из комментариев (вложенныые комметарии)
        $items = $this->treeRecursive($comments, null);

        return $this->render('comments/comments', [
            'post' => $this->post,
            'items' => $items,
            'commentForm' => $form,
        ]);
    }

    /**
     * @param Comment[] $comments
     * @param integer $parentId
     * @return CommentView[]
     */
    public function treeRecursive(&$comments, $parentId): array
    {
        $items = [];
        // проходим циклом по комментариям
        foreach ($comments as $comment) {
            // если ид родителя равен переданному ид (в нашем случае нулевому, так как выводим все комменты)
            if ($comment->parent_id == $parentId) {
                // повторно вызывается метод treeRecursive но уже от комментария ид которого мы передаем
                $items[] = new CommentView($comment, $this->treeRecursive($comments, $comment->id));
            }
        }
        return $items;
    }
}