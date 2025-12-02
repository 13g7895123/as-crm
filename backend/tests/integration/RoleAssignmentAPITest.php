<?php

namespace Tests\integration;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Role Assignment API Integration Test
 *
 * Tests the complete workflow of assigning roles to users with time-based permissions
 *
 * @group integration
 */
class RoleAssignmentAPITest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $seed        = 'Tests\Support\Database\Seeds\TestSeeder';
    protected $namespace   = 'App';

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->getAuthToken();
    }

    /**
     * Test assigning a role to a user
     */
    public function testAssignRoleToUser(): void
    {
        $assignmentData = [
            'user_id' => 1,
            'role_id' => 2, // sales_manager role from seeder
            'expires_at' => '2025-12-31 23:59:59',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/role-assignments', $assignmentData);

        $response->assertStatus(201);
        $response->assertJSONFragment(['message' => '角色指派成功']);

        $json = $response->getJSON();
        $this->assertObjectHasProperty('data', $json);
        $this->assertEquals(1, $json->data->user_id);
        $this->assertEquals(2, $json->data->role_id);
    }

    /**
     * Test assigning role without expiry date (permanent)
     */
    public function testAssignPermanentRole(): void
    {
        $assignmentData = [
            'user_id' => 1,
            'role_id' => 2,
            'expires_at' => null,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/role-assignments', $assignmentData);

        $response->assertStatus(201);

        $json = $response->getJSON();
        $this->assertNull($json->data->expires_at);
    }

    /**
     * Test validation: expires_at must be in the future
     */
    public function testExpiryDateMustBeInFuture(): void
    {
        $assignmentData = [
            'user_id' => 1,
            'role_id' => 2,
            'expires_at' => '2020-01-01 00:00:00', // Past date
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/role-assignments', $assignmentData);

        $response->assertStatus(422);
    }

    /**
     * Test getting user's assigned roles
     */
    public function testGetUserRoles(): void
    {
        // Assign a role first
        $assignmentData = [
            'user_id' => 1,
            'role_id' => 2,
            'expires_at' => '2025-12-31 23:59:59',
        ];

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/role-assignments', $assignmentData);

        // Get user's roles
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/v1/users/1/roles');

        $response->assertStatus(200);

        $json = $response->getJSON();
        $this->assertIsArray($json->data);
        $this->assertGreaterThan(0, count($json->data));
    }

    /**
     * Test revoking a role assignment
     */
    public function testRevokeRoleAssignment(): void
    {
        // Assign a role
        $assignmentData = [
            'user_id' => 1,
            'role_id' => 2,
        ];

        $assignResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/role-assignments', $assignmentData);

        $assignmentId = $assignResponse->getJSON()->data->id;

        // Revoke the role
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->delete('/api/v1/role-assignments/' . $assignmentId);

        $response->assertStatus(200);
        $response->assertJSONFragment(['message' => '角色撤銷成功']);
    }

    /**
     * Test extending role validity period
     */
    public function testExtendRoleValidity(): void
    {
        // Assign a role with expiry
        $assignmentData = [
            'user_id' => 1,
            'role_id' => 2,
            'expires_at' => '2025-06-30 23:59:59',
        ];

        $assignResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/role-assignments', $assignmentData);

        $assignmentId = $assignResponse->getJSON()->data->id;

        // Extend the validity
        $extendData = [
            'expires_at' => '2025-12-31 23:59:59',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->put('/api/v1/role-assignments/' . $assignmentId . '/extend', $extendData);

        $response->assertStatus(200);

        $json = $response->getJSON();
        $this->assertEquals('2025-12-31 23:59:59', $json->data->expires_at);
    }

    /**
     * Test preventing duplicate role assignments
     */
    public function testPreventDuplicateAssignments(): void
    {
        $assignmentData = [
            'user_id' => 1,
            'role_id' => 2,
        ];

        // First assignment
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/role-assignments', $assignmentData);

        // Duplicate assignment
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/role-assignments', $assignmentData);

        $response->assertStatus(422);
    }

    /**
     * Test filtering expired roles
     */
    public function testFilterExpiredRoles(): void
    {
        // Assign a role that already expired
        $db = \Config\Database::connect();
        $db->table('role_assignments')->insert([
            'user_id' => 1,
            'role_id' => 2,
            'assigned_by' => 1,
            'assigned_at' => '2020-01-01 00:00:00',
            'expires_at' => '2020-12-31 23:59:59',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Get active roles (should exclude expired)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/v1/users/1/roles?include_expired=false');

        $response->assertStatus(200);

        $json = $response->getJSON();
        foreach ($json->data as $assignment) {
            if ($assignment->expires_at) {
                $this->assertGreaterThan(time(), strtotime($assignment->expires_at));
            }
        }
    }

    /**
     * Test getting roles expiring soon
     */
    public function testGetExpiringRoles(): void
    {
        // Assign role expiring in 5 days
        $expiryDate = date('Y-m-d H:i:s', strtotime('+5 days'));

        $assignmentData = [
            'user_id' => 1,
            'role_id' => 2,
            'expires_at' => $expiryDate,
        ];

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/role-assignments', $assignmentData);

        // Get roles expiring within 7 days
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('/api/v1/role-assignments/expiring?days=7');

        $response->assertStatus(200);

        $json = $response->getJSON();
        $this->assertGreaterThan(0, count($json->data));
    }

    /**
     * Test bulk role assignment
     */
    public function testBulkRoleAssignment(): void
    {
        // Create additional test users
        $db = \Config\Database::connect();
        $db->table('users')->insertBatch([
            [
                'username' => 'user2',
                'email' => 'user2@example.com',
                'password_hash' => password_hash('password', PASSWORD_DEFAULT),
                'full_name' => 'User 2',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'user3',
                'email' => 'user3@example.com',
                'password_hash' => password_hash('password', PASSWORD_DEFAULT),
                'full_name' => 'User 3',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ]);

        $bulkData = [
            'user_ids' => [1, 2, 3],
            'role_id' => 2,
            'expires_at' => '2025-12-31 23:59:59',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/role-assignments/bulk', $bulkData);

        $response->assertStatus(201);

        $json = $response->getJSON();
        $this->assertCount(3, $json->data);
    }

    /**
     * Test role assignment with scope constraints
     */
    public function testRoleAssignmentWithScopeConstraints(): void
    {
        $assignmentData = [
            'user_id' => 1,
            'role_id' => 2,
            'scope_constraints' => [
                'department' => '業務部',
                'region' => '華東',
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->withBodyFormat('json')->post('/api/v1/role-assignments', $assignmentData);

        $response->assertStatus(201);

        $json = $response->getJSON();
        $this->assertObjectHasProperty('scope_constraints', $json->data);
    }

    /**
     * Helper method to get authentication token
     */
    protected function getAuthToken(): string
    {
        // TODO: Implement proper JWT token generation for testing
        return 'mock-jwt-token-for-testing';
    }
}
