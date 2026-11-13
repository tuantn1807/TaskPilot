<?php

namespace ProjectsManager\Modules\Auth\Handlers;

use ProjectsManager\Modules\Auth\Repositories\AuthRepository;
use ProjectsManager\User;

class UpdateFcmTokenHandler
{
    /**
     * Create a new handler instance.
     *
     * @param AuthRepository $repository
     */
    public function __construct(
        private readonly AuthRepository $repository
    ) {
    }

    /**
     * Execute update FCM token use case.
     *
     * @param User $user
     * @param string $fcmToken
     *
     * @return void
     */
    public function handle(User $user, string $fcmToken): void
    {
        $this->repository->updateFcmToken($user, $fcmToken);
    }
}

