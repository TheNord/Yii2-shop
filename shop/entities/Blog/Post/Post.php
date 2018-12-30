<?php

namespace shop\entities\Blog\Post;

use shop\entities\Blog\Post\Comment;
use lhs\Yii2SaveRelationsBehavior\SaveRelationsBehavior;
use shop\entities\behaviors\MetaBehavior;
use shop\entities\Blog\Post\queries\PostQuery;
use shop\entities\Blog\Post\queries\CommentQuery;
use shop\entities\Meta;
use shop\entities\Blog\Category;
use shop\entities\Blog\Tag;
use shop\services\WaterMarker;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;
use yiidreamteam\upload\ImageUploadBehavior;

/**
 * @property integer $id
 * @property integer $category_id
 * @property integer $created_at
 * @property string $title
 * @property string $description
 * @property string $content
 * @property string $photo
 * @property integer $status
 * @property integer $comments_count
 *
 * @property Meta $meta
 * @property Category $category
 * @property TagAssignment[] $tagAssignments
 * @property Tag[] $tags
 * @property Comment[] $comments
 *
 * @mixin ImageUploadBehavior
 */
class Post extends ActiveRecord
{
    const STATUS_DRAFT = 0;
    const STATUS_ACTIVE = 1;
    public $meta;

    public static function create($categoryId, $title, $description, $content, Meta $meta): self
    {
        $post = new static();
        $post->category_id = $categoryId;
        $post->title = $title;
        $post->description = $description;
        $post->content = $content;
        $post->meta = $meta;
        $post->status = self::STATUS_DRAFT;
        $post->created_at = time();
        $post->comments_count = 0;
        return $post;
    }

    public function setPhoto(UploadedFile $photo): void
    {
        $this->photo = $photo;
    }

    public function edit($categoryId, $title, $description, $content, Meta $meta): void
    {
        $this->category_id = $categoryId;
        $this->title = $title;
        $this->description = $description;
        $this->content = $content;
        $this->meta = $meta;
    }

    public function activate(): void
    {
        if ($this->isActive()) {
            throw new \DomainException('Post is already active.');
        }
        $this->status = self::STATUS_ACTIVE;
    }

    public function draft(): void
    {
        if ($this->isDraft()) {
            throw new \DomainException('Post is already draft.');
        }
        $this->status = self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    public function isDraft(): bool
    {
        return $this->status == self::STATUS_DRAFT;
    }

    public function getSeoTitle(): string
    {
        return $this->meta->title ?: $this->title;
    }

    // Tags
    public function assignTag($id): void
    {
        $assignments = $this->tagAssignments;
        foreach ($assignments as $assignment) {
            if ($assignment->isForTag($id)) {
                return;
            }
        }
        $assignments[] = TagAssignment::create($id);
        $this->tagAssignments = $assignments;
    }

    public function revokeTag($id): void
    {
        $assignments = $this->tagAssignments;
        foreach ($assignments as $i => $assignment) {
            if ($assignment->isForTag($id)) {
                unset($assignments[$i]);
                $this->tagAssignments = $assignments;
                return;
            }
        }
        throw new \DomainException('Assignment is not found.');
    }

    public function revokeTags(): void
    {
        $this->tagAssignments = [];
    }

    // Комментарии

    public function addComment($userId, $parentId, $text): Comment
    {
        // если parentId указан, пробуем найти среди текущего поста этот
        // родительский комментарий
        $parent = $parentId ? $this->getComment($parentId) : null;
        // если нашли родительский и он не активен - исключение
        if ($parent && !$parent->isActive()) {
            throw new \DomainException('Cannot add comment to inactive parent.');
        }
        // получаем массив комментариев
        $comments = $this->comments;
        // создаем комментарий и добавляем его к массиву (для пересчета в updateComments)
        $comments[] = $comment = Comment::create($userId, $parent ? $parent->id : null, $text);
        $this->updateComments($comments);

        return $comment;
    }

    public function editComment($id, $parentId, $text): void
    {
        $parent = $parentId ? $this->getComment($parentId) : null;
        $comments = $this->comments;
        foreach ($comments as $comment) {
            if ($comment->isIdEqualTo($id)) {
                $comment->edit($parent ? $parent->id : null, $text);
                $this->updateComments($comments);
                return;
            }
        }
        throw new \DomainException('Comment is not found.');
    }

    public function activateComment($id): void
    {
        $comments = $this->comments;
        foreach ($comments as $comment) {
            if ($comment->isIdEqualTo($id)) {
                $comment->activate();
                $this->updateComments($comments);
                return;
            }
        }
        throw new \DomainException('Comment is not found.');
    }

    /** Удаление комментария */
    public function removeComment($id): void
    {
        $comments = $this->comments;
        foreach ($comments as $i => $comment) {
            if ($comment->isIdEqualTo($id)) {
                // если у комментария есть подкомментарии
                if ($this->hasChildren($comment->id)) {
                    // то скрываем его (Комментарий удален)
                    $comment->draft();
                } else {
                    // если нет, то просто удаляем
                    unset($comments[$i]);
                }
                $this->updateComments($comments);
                return;
            }
        }
        throw new \DomainException('Comment is not found.');
    }

    /** Поиск комментария */
    public function getComment($id): Comment
    {
        // проходим по всем комментариям поста
        foreach ($this->comments as $comment) {
            // сравниваем переданный ид с ид комментария
            if ($comment->isIdEqualTo($id)) {
                // если нашли возвращаем комментарий
                return $comment;
            }
        }
        throw new \DomainException('Comment is not found.');
    }

    /** Проверка на наличие подкомментариев */
    private function hasChildren($id): bool
    {
        // проходим циклом по всем комментариям
        foreach ($this->comments as $comment) {
            // если у комментария родитель = переданный ид
            if ($comment->isChildOf($id)) {
                return true;
            }
        }
        return false;
    }

    private function updateComments(array $comments): void
    {
        $this->comments = $comments;
        // обновляем количество активных комментариев текущего поста
        $this->comments_count = count(array_filter($comments, function (Comment $comment) {
            return $comment->isActive();
        }));
    }


    ##########################
    public function getCategory(): ActiveQuery
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    public function getComments(): ActiveQuery
    {
        return $this->hasMany(Comment::class, ['post_id' => 'id']);
    }

    public function getTagAssignments(): ActiveQuery
    {
        return $this->hasMany(TagAssignment::class, ['post_id' => 'id']);
    }

    public function getTags(): ActiveQuery
    {
        return $this->hasMany(Tag::class, ['id' => 'tag_id'])->via('tagAssignments');
    }

    ##########################
    public static function tableName(): string
    {
        return '{{%blog_posts}}';
    }

    public function behaviors(): array
    {
        return [
            MetaBehavior::className(),
            [
                'class' => SaveRelationsBehavior::className(),
                'relations' => ['tagAssignments', 'comments'],
            ],
            [
                'class' => ImageUploadBehavior::className(),
                'attribute' => 'photo',
                'createThumbsOnRequest' => true,
                'filePath' => '@staticRoot/origin/posts/[[id]].[[extension]]',
                'fileUrl' => '@static/origin/posts/[[id]].[[extension]]',
                'thumbPath' => '@staticRoot/cache/posts/[[profile]]_[[id]].[[extension]]',
                'thumbUrl' => '@static/cache/posts/[[profile]]_[[id]].[[extension]]',
                'thumbs' => [
                    'admin' => ['width' => 100, 'height' => 70],
                    'thumb' => ['width' => 640, 'height' => 480],
                    'origin' => ['processor' => [new WaterMarker(1024, 768, '@frontend/web/image/logo.png'), 'process']],
                ],
            ],
        ];
    }

    public function transactions(): array
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    public static function find(): PostQuery
    {
        return new PostQuery(static::class);
    }
}