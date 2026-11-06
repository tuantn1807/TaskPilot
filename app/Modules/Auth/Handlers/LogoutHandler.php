<?php

namespace ProjectsManager\Modules\Auth\Handlers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutHandler
{
    /**
     * Execute user logout use case.
     *
     * @param Request $request
     *
     * @return void
     */
    public function handle(Request $request): void
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}

