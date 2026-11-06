<?php

namespace ProjectsManager\Modules\Auth\Handlers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProjectsManager\Modules\Auth\Repositories\AuthRepository;
use ProjectsManager\User;

class LoginHandler
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
     * Execute user authentication use case.
     *
     * @param Request $request
     * @param array $credentials
     * @param bool $remember
     *
     * @return bool
     */
    public function handle(Request $request, array $credentials, bool $remember = false): bool
    {
        if (! Auth::attempt($credentials, $remember)) {
            return false;
        }

        $user = Auth::user();

        if ($user instanceof User) {
            $this->repository->updateLastLogin($user);
        }

        $request->session()->regenerate();

        return true;
    }
}

