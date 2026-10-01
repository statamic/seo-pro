<?php

namespace Statamic\SeoPro\Policies;

use Statamic\Facades\User;

class DeadLinkPolicy
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
        return $this->view($user);
    }

    public function view($user): bool
    {
        return User::fromUser($user)->hasPermission('view seo dead links');
    }
}
