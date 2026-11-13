<?php

namespace ProjectsManager\Modules\Auth\Handlers;

use Illuminate\Support\Facades\Hash;
use ProjectsManager\Modules\Auth\Repositories\AuthRepository;

class ApiRegisterHandler
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
     * Execute mobile API registration use case.
     *
     * @param array<string, mixed> $data
     * @param string|null $deviceName
     *
     * @return array<string, mixed>
     */
    public function handle(array $data, ?string $deviceName = null): array
    {
        $user = $this->repository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $tokenName = $deviceName ?: 'mobile-app';
        $token = $user->createToken($tokenName)->plainTextToken;

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->fresh(['role']),
        ];
    }
}

