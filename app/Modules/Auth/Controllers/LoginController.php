<?php

namespace ProjectsManager\Modules\Auth\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ProjectsManager\Http\Controllers\Controller;
use ProjectsManager\Modules\Auth\Handlers\LoginHandler;
use ProjectsManager\Modules\Auth\Handlers\LogoutHandler;
use ProjectsManager\Modules\Auth\Requests\LoginRequest;

class LoginController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param LoginHandler $loginHandler
     * @param LogoutHandler $logoutHandler
     */
    public function __construct(
        private readonly LoginHandler $loginHandler,
        private readonly LogoutHandler $logoutHandler
    ) {
    }

    /**
     * Show the application login form.
     *
     * @return View
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle user authentication attempt.
     *
     * @param LoginRequest $request
     *
     * @return RedirectResponse
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $authenticated = $this->loginHandler->handle(
            $request,
            $request->only('email', 'password'),
            $request->boolean('remember')
        );

        if (! $authenticated) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Thông tin đăng nhập không chính xác.']);
        }

        return redirect()->intended('/home');
    }

    /**
     * Log the user out of the application.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        $this->logoutHandler->handle($request);

        return redirect()->route('login');
    }
}

