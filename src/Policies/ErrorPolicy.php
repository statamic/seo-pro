<?php

namespace Statamic\SeoPro\Policies;

use Statamic\Facades\User;

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
        return $user->hasPermission('view seo redirects');
    }
}
