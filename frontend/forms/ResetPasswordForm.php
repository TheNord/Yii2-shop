<?php
namespace frontend\forms;

use yii\base\Model;
use yii\base\InvalidParamException;
use common\entities\user;

/**
 * Password reset form
 */
class ResetPasswordForm extends Model
{
    public $password;

    public function rules()
    {
        return [
            ['password', 'required'],
            ['password', 'string', 'min' => 6],
        ];
    }
}
