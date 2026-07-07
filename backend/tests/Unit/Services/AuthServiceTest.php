<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\Hash;
use Mockery;
use Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Modules\Auth\Services\AuthService;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_can_login_user_successfully(): void
    {
        $repoMock = Mockery::mock(AuthRepositoryInterface::class);
        $credentials = ['email' => 'admin@ecogreen.com', 'password' => 'password'];

        $mockUser = Mockery::mock(\stdClass::class);
        $mockUser->password = Hash::make('password');
        $mockUser->isActive = 1;

        $mockUserData = Mockery::mock(\stdClass::class);
        $mockUserData->id = 1;
        $mockUserData->name = 'Admin';
        $mockUserData->email = 'admin@ecogreen.com';
        $mockUserData->id_plant = 1002;
        $mockUserData->role = 'admin';
        $mockUserData->shouldReceive('getRoleNames')->andReturn(collect(['admin']));
        $mockUserData->shouldReceive('getAllPermissions')->andReturn(collect([
            (object) ['name' => 'task-read'],
        ]));
        $mockUserData->shouldReceive('plants')->andReturn(
            Mockery::mock(['get' => Mockery::mock(['toArray' => [['id_plant' => 1, 'code_3' => 'PLN', 'description' => 'Plant 1']]])])
        );

        $repoMock->shouldReceive('findByEmail')
            ->once()
            ->with($credentials['email'])
            ->andReturn($mockUser);

        $repoMock->shouldReceive('createToken')
            ->once()
            ->with($mockUser, 'auth_token')
            ->andReturn('mocked-sanctum-token');

        $repoMock->shouldReceive('getUserWithPermissions')
            ->once()
            ->with($mockUser)
            ->andReturn($mockUserData);

        $authService = new AuthService($repoMock);
        $result = $authService->login($credentials);

        $this->assertEquals(1, $result['status']);
        $this->assertEquals('mocked-sanctum-token', $result['token']);
        $this->assertEquals('Admin', $result['data']['name']);
    }

    public function test_it_can_logout_user(): void
    {
        $repoMock = Mockery::mock(AuthRepositoryInterface::class);
        $user = (object) ['id' => 1];

        $repoMock->shouldReceive('revokeCurrentToken')
            ->once()
            ->with($user)
            ->andReturnNull();

        $authService = new AuthService($repoMock);
        $this->expectNotToPerformAssertions();
        $authService->logout($user);
    }
}
