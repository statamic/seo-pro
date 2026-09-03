<?php

namespace Statamic\SeoPro\Policies;

use Statamic\Facades\User;
use Statamic\SeoPro\Redirects\Error;

class ErrorPolicy
{
    public function before($user, $ability)
    {
        $user = User::fromUser($user);

        if ($user->isSuper()) {
            return true;
        }
    }

    public function index($user): bool
    {
        return User::fromUser($user)->hasPermission('view seo redirects');
    }

    public function delete($user, Error $error): bool
    {
        return User::fromUser($user)->hasPermission('delete seo errors');
    }
}
