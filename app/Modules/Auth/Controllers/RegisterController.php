<?php

namespace ProjectsManager\Modules\Auth\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use ProjectsManager\Http\Controllers\Controller;
use ProjectsManager\Modules\Auth\Handlers\RegisterUserHandler;
use ProjectsManager\Modules\Auth\Requests\RegisterRequest;

class RegisterController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param RegisterUserHandler $registerHandler
     */
    public function __construct(
        private readonly RegisterUserHandler $registerHandler
    ) {
    }

    /**
     * Show the user registration form.
     *
     * @return View
     */
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    /**
     * Handle user registration request.
     *
     * @param RegisterRequest $request
     *
     * @return RedirectResponse
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $this->registerHandler->handle($request, $request->validated());

        return redirect('/home');
    }
}

