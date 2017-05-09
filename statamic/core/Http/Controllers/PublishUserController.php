<?php

namespace Statamic\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\API\User;

class PublishUserController extends PublishController
{
    /**
     * Build the redirect.
     *
     * @param  Request  $request
     * @param  \Statamic\Contracts\Data\Users\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirect(Request $request, $user)
    {
        if ($request->continue || $this->cannotManageUsers($user)) {
            return route('user.edit', $user->username());
        }

        return route('users');
    }

    /**
     * Check if the current logged user can manage all the users.
     *
     * @param  \Statamic\Contracts\Data\Users\User  $user
     * @return bool
     */
    private function cannotManageUsers($user)
    {
        $current = User::getCurrent();

        return $user == $current && ! $user->hasPermission('user:manage');
    }
}
