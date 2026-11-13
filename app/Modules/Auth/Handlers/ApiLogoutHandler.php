<?php

namespace ProjectsManager\Modules\Auth\Handlers;

use ProjectsManager\User;

class ApiLogoutHandler
{
    /**
     * Execute mobile API logout use case.
     *
     * @param User $user
     *
     * @return void
     */
    public function handle(User $user): void
    {
        $currentToken = $user->currentAccessToken();

        if ($currentToken) {
            $currentToken->delete();
        }
    }
}

