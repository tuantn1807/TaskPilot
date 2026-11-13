<?php

namespace ProjectsManager\Modules\Auth\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ProjectsManager\Http\Controllers\Controller;
use ProjectsManager\Modules\Auth\Handlers\ApiLoginHandler;
use ProjectsManager\Modules\Auth\Handlers\ApiLogoutHandler;
use ProjectsManager\Modules\Auth\Handlers\ApiRegisterHandler;
use ProjectsManager\Modules\Auth\Handlers\UpdateFcmTokenHandler;
use ProjectsManager\Modules\Auth\Handlers\UpdateProfileHandler;
use ProjectsManager\Modules\Auth\Requests\ApiLoginRequest;
use ProjectsManager\Modules\Auth\Requests\ApiRegisterRequest;
use ProjectsManager\Modules\Auth\Requests\UpdateFcmTokenRequest;
use ProjectsManager\Modules\Auth\Requests\UpdateProfileRequest;
use ProjectsManager\User;

class AuthController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param ApiLoginHandler $loginHandler
     * @param ApiRegisterHandler $registerHandler
     * @param ApiLogoutHandler $logoutHandler
     * @param UpdateProfileHandler $profileHandler
     * @param UpdateFcmTokenHandler $fcmHandler
     */
    public function __construct(
        private readonly ApiLoginHandler $loginHandler,
        private readonly ApiRegisterHandler $registerHandler,
        private readonly ApiLogoutHandler $logoutHandler,
        private readonly UpdateProfileHandler $profileHandler,
        private readonly UpdateFcmTokenHandler $fcmHandler
    ) {
    }

    /**
     * Handle mobile user login attempt.
     *
     * @param ApiLoginRequest $request
     *
     * @return JsonResponse
     */
    public function login(ApiLoginRequest $request): JsonResponse
    {
        $result = $this->loginHandler->handle(
            $request->input('email'),
            $request->input('password'),
            $request->input('device_name')
        );

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công.',
            'data' => $result,
        ]);
    }

    /**
     * Handle mobile user registration attempt.
     *
     * @param ApiRegisterRequest $request
     *
     * @return JsonResponse
     */
    public function register(ApiRegisterRequest $request): JsonResponse
    {
        $result = $this->registerHandler->handle(
            $request->validated(),
            $request->input('device_name')
        );

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký tài khoản thành công.',
            'data' => $result,
        ], 201);
    }

    /**
     * Get the authenticated user profile.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof User) {
            $user->load(['role']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin tài khoản thành công.',
            'data' => [
                'user' => $user,
            ],
        ]);
    }

    /**
     * Update the authenticated user profile.
     *
     * @param UpdateProfileRequest $request
     *
     * @return JsonResponse
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $updatedUser = $this->profileHandler->handle($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin cá nhân thành công.',
            'data' => [
                'user' => $updatedUser,
            ],
        ]);
    }

    /**
     * Update mobile device FCM token for push notifications.
     *
     * @param UpdateFcmTokenRequest $request
     *
     * @return JsonResponse
     */
    public function updateFcmToken(UpdateFcmTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->fcmHandler->handle($user, $request->input('fcm_token'));

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật FCM token thành công.',
        ]);
    }

    /**
     * Handle mobile user logout.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof User) {
            $this->logoutHandler->handle($user);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đăng xuất thành công.',
        ]);
    }
}

