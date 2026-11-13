<?php

namespace ProjectsManager\Modules\Auth\Handlers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use ProjectsManager\Modules\Auth\Repositories\AuthRepository;
use ProjectsManager\User;

class ApiLoginHandler
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
     * Execute mobile API login use case.
     *
     * @param string $email
     * @param string $password
     * @param string|null $deviceName
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function handle(string $email, string $password, ?string $deviceName = null): array
    {
        $user = $this->repository->findByEmail($email);

        if (! $user instanceof User || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Thông tin đăng nhập không chính xác.'],
            ]);
        }

        $this->repository->updateLastLogin($user);

        $tokenName = $deviceName ?: 'mobile-app';
        $token = $user->createToken($tokenName)->plainTextToken;

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->fresh(['role']),
        ];
    }
}

