<?php declare(strict_types=1);

namespace Tests\Feature\Modules;

use App\Models\User;
use Modules\Admin\Services\Contracts\AdminServiceInterface;
use Modules\Shared\Http\Middleware\PlantContextMiddleware;
use Mockery;
use Tests\TestCase;

class RolePermissionModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlantContextMiddleware::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Unauthenticated — all endpoints return 401
    // -------------------------------------------------------------------------

    public function test_it_returns_401_on_index_without_auth(): void
    {
        $this->getJson('/api/v1/admin/users')->assertStatus(401);
    }

    public function test_it_returns_401_on_store_without_auth(): void
    {
        $this->postJson('/api/v1/admin/users', [])->assertStatus(401);
    }

    public function test_it_returns_401_on_update_without_auth(): void
    {
        $this->putJson('/api/v1/admin/users/1', [])->assertStatus(401);
    }

    public function test_it_returns_401_on_destroy_without_auth(): void
    {
        $this->deleteJson('/api/v1/admin/users/1')->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // Authenticated — GET /admin/users returns 200 with mocked service
    // -------------------------------------------------------------------------

    public function test_it_returns_user_list_when_authenticated(): void
    {
        $actingUser = User::factory()->make();

        $users = [
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@eods.local', 'roles' => [['name' => 'admin']]],
            ['id' => 2, 'name' => 'Bob',   'email' => 'bob@eods.local',   'roles' => [['name' => 'staff']]],
        ];

        $serviceMock = Mockery::mock(AdminServiceInterface::class);
        $serviceMock->shouldReceive('listUsers')
            ->once()
            ->andReturn($users);
        $this->app->instance(AdminServiceInterface::class, $serviceMock);

        $response = $this->actingAs($actingUser, 'sanctum')
            ->getJson('/api/v1/admin/users');

        $response->assertStatus(200)
            ->assertJsonPath('status', 1)
            ->assertJsonCount(2, 'data');
    }

    // -------------------------------------------------------------------------
    // Authenticated — GET /admin/users returns empty list
    // -------------------------------------------------------------------------

    public function test_it_returns_empty_user_list(): void
    {
        $actingUser = User::factory()->make();

        $serviceMock = Mockery::mock(AdminServiceInterface::class);
        $serviceMock->shouldReceive('listUsers')
            ->once()
            ->andReturn([]);
        $this->app->instance(AdminServiceInterface::class, $serviceMock);

        $response = $this->actingAs($actingUser, 'sanctum')
            ->getJson('/api/v1/admin/users');

        $response->assertStatus(200)
            ->assertJsonPath('status', 1)
            ->assertJsonCount(0, 'data');
    }

    // -------------------------------------------------------------------------
    // Authenticated — POST /admin/users → 422 missing required fields
    // -------------------------------------------------------------------------

    public function test_it_returns_422_when_store_user_missing_required_fields(): void
    {
        $actingUser = User::factory()->make();

        $serviceMock = Mockery::mock(AdminServiceInterface::class);
        $this->app->instance(AdminServiceInterface::class, $serviceMock);

        $response = $this->actingAs($actingUser, 'sanctum')
            ->postJson('/api/v1/admin/users', [
                // missing name, email, password, role
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    }

    // -------------------------------------------------------------------------
    // Authenticated — POST /admin/users → 422 invalid email format
    // Role is omitted so validation fails on email before reaching exists:roles,name
    // -------------------------------------------------------------------------

    public function test_it_returns_422_when_store_user_email_is_invalid(): void
    {
        $actingUser = User::factory()->make();

        $serviceMock = Mockery::mock(AdminServiceInterface::class);
        $this->app->instance(AdminServiceInterface::class, $serviceMock);

        $response = $this->actingAs($actingUser, 'sanctum')
            ->postJson('/api/v1/admin/users', [
                'name'     => 'Test User',
                'email'    => 'not-an-email',
                'password' => 'Secret1234',
                // role intentionally omitted — tests email validation only
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // -------------------------------------------------------------------------
    // Authenticated — POST /admin/users → 422 password too short
    // Email is invalid so unique:users rule never runs; password fails min:8
    // -------------------------------------------------------------------------

    public function test_it_returns_422_when_password_is_too_short(): void
    {
        $actingUser = User::factory()->make();

        $serviceMock = Mockery::mock(AdminServiceInterface::class);
        $this->app->instance(AdminServiceInterface::class, $serviceMock);

        $response = $this->actingAs($actingUser, 'sanctum')
            ->postJson('/api/v1/admin/users', [
                'name'     => 'Short Pass',
                'email'    => 'not-valid-email', // invalid — stops before unique:users
                'password' => '1234567',         // 7 chars, min is 8
                // role intentionally omitted
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    // -------------------------------------------------------------------------
    // Authenticated — DELETE /admin/users/{id} → 200 success
    // -------------------------------------------------------------------------

    public function test_it_deletes_user_successfully(): void
    {
        $actingUser = User::factory()->make(['id' => 1]);
        $targetUser = (object)['id' => 99, 'name' => 'DeleteMe', 'email' => 'del@eods.local'];

        $serviceMock = Mockery::mock(AdminServiceInterface::class);
        $serviceMock->shouldReceive('findUserById')
            ->once()
            ->with(99)
            ->andReturn($targetUser);
        $serviceMock->shouldReceive('deleteUser')
            ->once()
            ->with(99)
            ->andReturn(true);
        $this->app->instance(AdminServiceInterface::class, $serviceMock);

        $response = $this->actingAs($actingUser, 'sanctum')
            ->deleteJson('/api/v1/admin/users/99');

        $response->assertStatus(200)
            ->assertJsonPath('status', 1);
    }

    // -------------------------------------------------------------------------
    // Authenticated — DELETE /admin/users/{id} → 404 user not found
    // -------------------------------------------------------------------------

    public function test_it_returns_404_when_deleting_nonexistent_user(): void
    {
        $actingUser = User::factory()->make();

        $serviceMock = Mockery::mock(AdminServiceInterface::class);
        $serviceMock->shouldReceive('findUserById')
            ->once()
            ->with(888)
            ->andReturn(null);
        $this->app->instance(AdminServiceInterface::class, $serviceMock);

        $response = $this->actingAs($actingUser, 'sanctum')
            ->deleteJson('/api/v1/admin/users/888');

        $response->assertStatus(404)
            ->assertJsonPath('status', 0);
    }

    // -------------------------------------------------------------------------
    // Authenticated — DELETE /admin/users/{id} → 403 cannot delete self
    // -------------------------------------------------------------------------

    public function test_it_returns_403_when_user_tries_to_delete_own_account(): void
    {
        // Give the acting user a known numeric ID via make()
        $actingUser = User::factory()->make(['id' => 42]);

        $targetUser = (object)[
            'id'    => 42,
            'name'  => 'Self',
            'email' => 'self@eods.local',
        ];

        $serviceMock = Mockery::mock(AdminServiceInterface::class);
        $serviceMock->shouldReceive('findUserById')
            ->once()
            ->with(42)
            ->andReturn($targetUser);
        $this->app->instance(AdminServiceInterface::class, $serviceMock);

        $response = $this->actingAs($actingUser, 'sanctum')
            ->deleteJson('/api/v1/admin/users/42');

        $response->assertStatus(403)
            ->assertJsonPath('status', 0);
    }

    // -------------------------------------------------------------------------
    // Authenticated — GET /admin/users → 500 when service throws
    // -------------------------------------------------------------------------

    public function test_it_returns_500_when_service_throws_on_index(): void
    {
        $actingUser = User::factory()->make();

        $serviceMock = Mockery::mock(AdminServiceInterface::class);
        $serviceMock->shouldReceive('listUsers')
            ->once()
            ->andThrow(new \RuntimeException('Database unreachable'));
        $this->app->instance(AdminServiceInterface::class, $serviceMock);

        $response = $this->actingAs($actingUser, 'sanctum')
            ->getJson('/api/v1/admin/users');

        // UserController::index() has no try/catch — Laravel returns 500
        $response->assertStatus(500);
    }
}
