<?php

namespace ProjectsManager\Modules\Auth\Handlers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use ProjectsManager\Modules\Auth\Repositories\AuthRepository;
use ProjectsManager\User;

class RegisterUserHandler
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
     * Execute user registration use case.
     *
     * @param Request $request
     * @param array $data
     *
     * @return User
     */
    public function handle(Request $request, array $data): User
    {
        $user = $this->repository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return $user;
    }
}

