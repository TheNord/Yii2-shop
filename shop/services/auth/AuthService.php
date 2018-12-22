<?php

namespace shop\services\auth;

use shop\entities\User\User;
use shop\forms\auth\LoginForm;
use shop\repositories\UserRepository;

class AuthService
{
    private $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function auth(LoginForm $form): User
    {
        $user = $this->users->findByUsernameOrEmail($form->username);

        if (!$user || !$user->validatePassword($form->password)) {
            throw new \DomainException('Undefined user or password.');
        }

        if ($user->isWait()) {
            throw new \DomainException('Please confirm your email.');
        }
        return $user;
    }
}