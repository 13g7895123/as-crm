<?php

namespace Tests\unit;

use Tests\Support\ApiTestCase;

/**
 * RoleController Unit Test
 *
 * Tests the role management API endpoints
 *
 * @group unit
 */
class RoleControllerTest extends ApiTestCase
{
    /**
     * Test get roles list
     */
    public function testGetRolesList(): void
    {
        $result = $this->authGet('/api/v1/roles');
        $result->assertStatus(200);
    }

    /**
     * Test get roles list with pagination
     */
    public function testGetRolesListWithPagination(): void
    {
        $result = $this->authGet('/api/v1/roles?page=1&per_page=5');
        $result->assertStatus(200);
    }

    /**
     * Test get role by ID
     */
    public function testGetRoleById(): void
    {
        $db = \Config\Database::connect();
        $role = $db->table('roles')->where('name', 'system_admin')->get()->getRowArray();

        $result = $this->authGet('/api/v1/roles/' . $role['id']);
        $result->assertStatus(200);
    }

    /**
     * Test roles endpoint requires authentication
     */
    public function testRolesRequiresAuth(): void
    {
        $result = $this->get('/api/v1/roles');
        $result->assertStatus(401);
    }
}
