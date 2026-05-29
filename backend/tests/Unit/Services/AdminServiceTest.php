<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use Modules\Admin\Services\AdminService;
use Modules\Admin\Repositories\Contracts\AdminRepositoryInterface;
use Mockery;

class AdminServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_can_list_users(): void
    {
        $repoMock = Mockery::mock(AdminRepositoryInterface::class);
        $expectedUsers = [(object)['id' => 1, 'name' => 'John']];

        $repoMock->shouldReceive('getAllUsers')
            ->once()
            ->andReturn($expectedUsers);

        $adminService = new AdminService($repoMock);
        $result = $adminService->listUsers();

        $this->assertEquals($expectedUsers, $result);
    }

    public function test_it_can_create_user(): void
    {
        $repoMock = Mockery::mock(AdminRepositoryInterface::class);
        $data = ['name' => 'Alice', 'email' => 'alice@test.com'];
        $expectedUser = (object)['id' => 2, 'name' => 'Alice'];

        $repoMock->shouldReceive('createUser')
            ->once()
            ->with($data)
            ->andReturn($expectedUser);

        $adminService = new AdminService($repoMock);
        $result = $adminService->createUser($data);

        $this->assertEquals($expectedUser, $result);
    }
}
