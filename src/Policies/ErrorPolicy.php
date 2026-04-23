<?php

namespace Statamic\SeoPro\Policies;

use Illuminate\Support\Facades\Gate;
use Statamic\Facades\User;
use Statamic\SeoPro\Redirects\Redirect;

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
        return Gate::authorize('index', Redirect::class);
    }
}
