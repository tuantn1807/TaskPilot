<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ProjectsManager\Modules\Auth\Handlers\LogoutHandler;
use ProjectsManager\Modules\Auth\Handlers\RegisterUserHandler;
use ProjectsManager\Modules\Auth\Repositories\AuthRepository;
use ProjectsManager\Modules\Auth\Requests\LoginRequest;
use ProjectsManager\Modules\Auth\Requests\RegisterRequest;
use ProjectsManager\User;

class AuthModuleTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Test login request rules structure.
     *
     * @return void
     */
    public function test_login_request_has_expected_rules(): void
    {
        $request = new LoginRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertTrue($request->authorize());
    }

    /**
     * Test register request rules structure.
     *
     * @return void
     */
    public function test_register_request_has_expected_rules(): void
    {
        $request = new RegisterRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertTrue($request->authorize());
    }

    /**
     * Test logout handler invalidates session and regenerates token.
     *
     * @return void
     */
    public function test_logout_handler_clears_session(): void
    {
        Auth::shouldReceive('logout')->once();

        $sessionMock = Mockery::mock();
        $sessionMock->shouldReceive('invalidate')->once();
        $sessionMock->shouldReceive('regenerateToken')->once();

        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('session')->twice()->andReturn($sessionMock);

        $handler = new LogoutHandler();
        $handler->handle($requestMock);

        $this->assertTrue(true);
    }

    /**
     * Test register user handler creates user and logs in.
     *
     * @return void
     */
    public function test_register_user_handler_creates_user(): void
    {
        $fakeUser = new User();
        $fakeUser->name = 'Test User';
        $fakeUser->email = 'test@example.com';

        Hash::shouldReceive('make')
            ->once()
            ->with('secret123')
            ->andReturn('hashed_secret123');

        $repositoryMock = Mockery::mock(AuthRepository::class);
        $repositoryMock->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['name'] === 'Test User'
                    && $data['email'] === 'test@example.com'
                    && $data['password'] === 'hashed_secret123';
            }))
            ->andReturn($fakeUser);

        Auth::shouldReceive('login')->once()->with($fakeUser);

        $sessionMock = Mockery::mock();
        $sessionMock->shouldReceive('regenerate')->once();

        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('session')->once()->andReturn($sessionMock);

        $handler = new RegisterUserHandler($repositoryMock);
        $created = $handler->handle($requestMock, [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret123',
        ]);

        $this->assertSame($fakeUser, $created);
    }
}
