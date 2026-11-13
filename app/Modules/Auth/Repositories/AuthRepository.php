<?php

namespace ProjectsManager\Modules\Auth\Repositories;

use ProjectsManager\User;

class AuthRepository
{
    /**
     * Create a new user record.
     *
     * @param array $data
     *
     * @return User
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Find a user by email address.
     *
     * @param string $email
     *
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Update user last login timestamp.
     *
     * @param User $user
     *
     * @return void
     */
    public function updateLastLogin(User $user): void
    {
        $user->update([
            'last_login_at' => now(),
        ]);
    }

    /**
     * Update user profile data.
     *
     * @param User $user
     * @param array $data
     *
     * @return User
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }

    /**
     * Update user FCM push notification token.
     *
     * @param User $user
     * @param string|null $fcmToken
     *
     * @return void
     */
    public function updateFcmToken(User $user, ?string $fcmToken): void
    {
        $user->update([
            'fcm_token' => $fcmToken,
        ]);
    }

    /**
     * Find user by ID.
     *
     * @param int $id
     *
     * @return User|null
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }
}

