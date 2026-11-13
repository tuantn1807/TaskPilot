<?php

namespace Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ProjectsManager\Modules\Auth\Handlers\ApiLogoutHandler;
use ProjectsManager\Modules\Auth\Handlers\UpdateFcmTokenHandler;
use ProjectsManager\Modules\Auth\Handlers\UpdateProfileHandler;
use ProjectsManager\Modules\Auth\Repositories\AuthRepository;
use ProjectsManager\Modules\Auth\Requests\ApiLoginRequest;
use ProjectsManager\Modules\Auth\Requests\ApiRegisterRequest;
use ProjectsManager\Modules\Auth\Requests\UpdateFcmTokenRequest;
use ProjectsManager\Modules\Auth\Requests\UpdateProfileRequest;
use ProjectsManager\User;

class ApiAuthModuleTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Test API login request rules structure.
     *
     * @return void
     */
    public function test_api_login_request_rules(): void
    {
        $request = new ApiLoginRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertArrayHasKey('device_name', $rules);
        $this->assertTrue($request->authorize());
    }

    /**
     * Test API register request rules structure.
     *
     * @return void
     */
    public function test_api_register_request_rules(): void
    {
        $request = new ApiRegisterRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertTrue($request->authorize());
    }

    /**
     * Test update profile request rules structure.
     *
     * @return void
     */
    public function test_update_profile_request_rules(): void
    {
        $request = new UpdateProfileRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('job_title', $rules);
        $this->assertArrayHasKey('department', $rules);
        $this->assertTrue($request->authorize());
    }

    /**
     * Test update FCM token request rules structure.
     *
     * @return void
     */
    public function test_update_fcm_token_request_rules(): void
    {
        $request = new UpdateFcmTokenRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('fcm_token', $rules);
        $this->assertTrue($request->authorize());
    }

    /**
     * Test update profile handler delegates to repository.
     *
     * @return void
     */
    public function test_update_profile_handler_delegates(): void
    {
        $user = new User();
        $repoMock = Mockery::mock(AuthRepository::class);
        $repoMock->shouldReceive('updateProfile')
            ->once()
            ->with($user, ['name' => 'Updated Name'])
            ->andReturn($user);

        $handler = new UpdateProfileHandler($repoMock);
        $result = $handler->handle($user, ['name' => 'Updated Name']);

        $this->assertSame($user, $result);
    }

    /**
     * Test update FCM token handler delegates to repository.
     *
     * @return void
     */
    public function test_update_fcm_token_handler_delegates(): void
    {
        $user = new User();
        $repoMock = Mockery::mock(AuthRepository::class);
        $repoMock->shouldReceive('updateFcmToken')
            ->once()
            ->with($user, 'sample-fcm-token-123');

        $handler = new UpdateFcmTokenHandler($repoMock);
        $handler->handle($user, 'sample-fcm-token-123');

        $this->assertTrue(true);
    }

    /**
     * Test API logout handler deletes current access token.
     *
     * @return void
     */
    public function test_api_logout_handler_deletes_current_token(): void
    {
        $tokenMock = Mockery::mock();
        $tokenMock->shouldReceive('delete')->once();

        $userMock = Mockery::mock(User::class);
        $userMock->shouldReceive('currentAccessToken')->once()->andReturn($tokenMock);

        $handler = new ApiLogoutHandler();
        $handler->handle($userMock);

        $this->assertTrue(true);
    }
}

