<?php

namespace ProjectsManager\Modules\Auth\Handlers;

use ProjectsManager\Modules\Auth\Repositories\AuthRepository;
use ProjectsManager\User;

class UpdateProfileHandler
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
     * Execute update user profile use case.
     *
     * @param User $user
     * @param array<string, mixed> $data
     *
     * @return User
     */
    public function handle(User $user, array $data): User
    {
        return $this->repository->updateProfile($user, $data);
    }
}

