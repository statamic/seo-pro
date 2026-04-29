<?php

namespace Statamic\SeoPro\Policies;

use Statamic\Facades\User;
use Statamic\SeoPro\Redirects\Redirect;

class RedirectPolicy
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
        return $user->hasPermission('view seo redirects');
    }

    public function create($user): bool
    {
        return $user->hasPermission('create seo redirects');
    }

    public function store($user): bool
    {
        return $user->hasPermission('create seo redirects');
    }

    public function edit($user, Redirect $redirect): bool
    {
        return $user->hasPermission('edit seo redirects');
    }

    public function update($user, Redirect $redirect): bool
    {
        return $user->hasPermission('edit seo redirects');
    }

    public function delete($user, Redirect $redirect): bool
    {
        return $user->hasPermission('delete seo redirects');
    }
}
