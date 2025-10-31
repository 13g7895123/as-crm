<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Services\RoleService;
use App\Services\RoleHierarchyService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Role Controller
 *
 * Handles REST API endpoints for role management
 * Endpoints: GET/POST /roles, GET/PUT/DELETE /roles/{id}, GET /roles/{id}/permissions
 */
class RoleController extends BaseController
{
    protected RoleService $roleService;
    protected RoleHierarchyService $hierarchyService;

    /**
     * Permission filters for role management
     * Requires 'role:view' for read operations and 'role:edit' for write operations
     */
    protected $filters = [
        'permission' => [
            'before' => [
                'index'       => ['module' => 'role', 'action' => 'view'],
                'show'        => ['module' => 'role', 'action' => 'view'],
                'create'      => ['module' => 'role', 'action' => 'edit'],
                'update'      => ['module' => 'role', 'action' => 'edit'],
                'delete'      => ['module' => 'role', 'action' => 'edit'],
            ],
        ],
    ];

    public function __construct()
    {
        $this->roleService = new RoleService();
        $this->hierarchyService = new RoleHierarchyService();
    }

    /**
     * GET /api/v1/roles
     *
     * Get all roles with pagination and filters
     *
     * @return ResponseInterface
     */
    public function index(): ResponseInterface
    {
        try {
            $filters = [
                'page'      => $this->request->getGet('page') ?? 1,
                'per_page'  => $this->request->getGet('per_page') ?? 20,
                'is_active' => $this->request->getGet('is_active'),
                'search'    => $this->request->getGet('search'),
                'sort'      => $this->request->getGet('sort') ?? 'created_at',
                'order'     => $this->request->getGet('order') ?? 'desc',
            ];

            // Validate per_page
            if ($filters['per_page'] > 100) {
                $filters['per_page'] = 100;
            }

            $result = $this->roleService->getRoles($filters);

            return $this->respond($result, 200);
        } catch (\Exception $e) {
            return $this->failServerError('取得角色清單失敗: ' . $e->getMessage());
        }
    }

    /**
     * POST /api/v1/roles
     *
     * Create a new role
     *
     * @return ResponseInterface
     */
    public function create(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);

            // Validate required fields
            if (empty($data['name']) || empty($data['display_name'])) {
                return $this->fail([
                    'error' => '驗證失敗',
                    'errors' => [
                        'name' => empty($data['name']) ? ['角色名稱為必填'] : [],
                        'display_name' => empty($data['display_name']) ? ['顯示名稱為必填'] : [],
                    ],
                ], 422);
            }

            // Validate name pattern
            if (!preg_match('/^[a-z0-9_]{3,100}$/', $data['name'])) {
                return $this->fail([
                    'error' => '驗證失敗',
                    'errors' => [
                        'name' => ['角色名稱只能包含小寫字母、數字和底線,長度3-100字元'],
                    ],
                ], 422);
            }

            $role = $this->roleService->createRole($data);

            return $this->respondCreated([
                'data' => $role,
                'message' => '角色建立成功',
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->fail([
                'error' => '驗證失敗',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), '已存在')) {
                return $this->fail([
                    'error' => '角色名稱已存在',
                    'message' => $e->getMessage(),
                    'code' => 'ROLE_NAME_DUPLICATE',
                ], 422);
            }

            return $this->failServerError($e->getMessage());
        } catch (\Exception $e) {
            return $this->failServerError('建立角色失敗: ' . $e->getMessage());
        }
    }

    /**
     * GET /api/v1/roles/{id}
     *
     * Get role details
     *
     * @param int $id
     * @return ResponseInterface
     */
    public function show($id = null): ResponseInterface
    {
        try {
            $role = $this->roleService->getRoleById((int)$id);

            return $this->respond([
                'data' => $role,
            ], 200);
        } catch (\RuntimeException $e) {
            return $this->failNotFound([
                'error' => '找不到資源',
                'message' => '角色不存在',
                'code' => 'ROLE_NOT_FOUND',
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('取得角色失敗: ' . $e->getMessage());
        }
    }

    /**
     * PUT /api/v1/roles/{id}
     *
     * Update role
     *
     * @param int $id
     * @return ResponseInterface
     */
    public function update($id = null): ResponseInterface
    {
        try {
            $id = (int)$id;
            $data = $this->request->getJSON(true);

            $role = $this->roleService->updateRole($id, $data);

            return $this->respond([
                'data' => $role,
                'message' => '角色更新成功',
            ], 200);
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), '不存在')) {
                return $this->failNotFound([
                    'error' => '找不到資源',
                    'message' => '角色不存在',
                    'code' => 'ROLE_NOT_FOUND',
                ]);
            }

            return $this->failServerError($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->fail([
                'error' => '驗證失敗',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return $this->failServerError('更新角色失敗: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /api/v1/roles/{id}
     *
     * Delete role
     *
     * @param int $id
     * @return ResponseInterface
     */
    public function delete($id = null): ResponseInterface
    {
        try {
            $id = (int)$id;
            $this->roleService->deleteRole($id);

            return $this->respond([
                'message' => '角色刪除成功',
            ], 200);
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), '不存在')) {
                return $this->failNotFound([
                    'error' => '找不到資源',
                    'message' => '角色不存在',
                    'code' => 'ROLE_NOT_FOUND',
                ]);
            }

            if (str_contains($e->getMessage(), '系統角色')) {
                return $this->fail([
                    'error' => '無法刪除系統角色',
                    'message' => $e->getMessage(),
                    'code' => 'SYSTEM_ROLE_DELETION',
                ], 400);
            }

            if (str_contains($e->getMessage(), '使用中')) {
                return $this->fail([
                    'error' => '無法刪除使用中的角色',
                    'message' => $e->getMessage(),
                    'code' => 'ROLE_IN_USE',
                ], 400);
            }

            return $this->failServerError($e->getMessage());
        } catch (\Exception $e) {
            return $this->failServerError('刪除角色失敗: ' . $e->getMessage());
        }
    }

    /**
     * GET /api/v1/roles/{id}/permissions
     *
     * Get role permissions
     *
     * @param int $id
     * @return ResponseInterface
     */
    public function permissions(int $id): ResponseInterface
    {
        try {
            $includeInherited = $this->request->getGet('include_inherited') !== 'false';

            $permissions = $this->roleService->getRolePermissions($id, $includeInherited);

            return $this->respond([
                'data' => $permissions,
            ], 200);
        } catch (\RuntimeException $e) {
            return $this->failNotFound([
                'error' => '找不到資源',
                'message' => '角色不存在',
                'code' => 'ROLE_NOT_FOUND',
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('取得角色權限失敗: ' . $e->getMessage());
        }
    }

    /**
     * GET /api/v1/roles/hierarchy
     *
     * Get complete role hierarchy tree
     *
     * @return ResponseInterface
     */
    public function hierarchy(): ResponseInterface
    {
        try {
            $tree = $this->hierarchyService->getHierarchyTree();

            return $this->respond([
                'data' => $tree,
            ], 200);
        } catch (\Exception $e) {
            return $this->failServerError('取得角色階層失敗: ' . $e->getMessage());
        }
    }

    /**
     * GET /api/v1/roles/{id}/hierarchy
     *
     * Get role's hierarchy information
     *
     * @param int $id
     * @return ResponseInterface
     */
    public function roleHierarchy(int $id): ResponseInterface
    {
        try {
            $info = $this->hierarchyService->getRoleHierarchyInfo($id);

            return $this->respond([
                'data' => $info,
            ], 200);
        } catch (\RuntimeException $e) {
            return $this->failNotFound([
                'error' => '找不到資源',
                'message' => '角色不存在',
                'code' => 'ROLE_NOT_FOUND',
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('取得角色階層資訊失敗: ' . $e->getMessage());
        }
    }

    /**
     * PUT /api/v1/roles/{id}/parent
     *
     * Update role's parent
     *
     * Request body: { "parent_role_id": 2 } or { "parent_role_id": null }
     *
     * @param int $id
     * @return ResponseInterface
     */
    public function updateParent(int $id): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);
            $parentRoleId = $data['parent_role_id'] ?? null;

            // TODO: Get current user ID from JWT
            $modifiedBy = 1;

            $this->hierarchyService->setParent($id, $parentRoleId, $modifiedBy);

            return $this->respond([
                'message' => '父角色更新成功',
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return $this->fail([
                'error' => '驗證失敗',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            return $this->fail([
                'error' => '更新失敗',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return $this->failServerError('更新父角色失敗: ' . $e->getMessage());
        }
    }

    /**
     * POST /api/v1/roles/{id}/parents
     *
     * Add parent role to a role
     *
     * Request body: { "parent_role_id": 2 }
     *
     * @param int $id
     * @return ResponseInterface
     */
    public function addParent(int $id): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);

            if (!isset($data['parent_role_id'])) {
                return $this->fail([
                    'error' => '驗證失敗',
                    'errors' => [
                        'parent_role_id' => ['父角色ID為必填'],
                    ],
                ], 422);
            }

            $parentRoleId = (int)$data['parent_role_id'];

            // TODO: Get current user ID from JWT
            $createdBy = 1;

            $this->hierarchyService->addParent($id, $parentRoleId, $createdBy);

            return $this->respondCreated([
                'message' => '父角色新增成功',
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->fail([
                'error' => '驗證失敗',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            return $this->fail([
                'error' => '新增失敗',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return $this->failServerError('新增父角色失敗: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /api/v1/roles/{id}/parents/{parentId}
     *
     * Remove parent role from a role
     *
     * @param int $id Role ID
     * @param int $parentId Parent role ID
     * @return ResponseInterface
     */
    public function removeParent(int $id, int $parentId): ResponseInterface
    {
        try {
            // TODO: Get current user ID from JWT
            $deletedBy = 1;

            $this->hierarchyService->removeParent($id, $parentId, $deletedBy);

            return $this->respond([
                'message' => '父角色移除成功',
            ], 200);
        } catch (\RuntimeException $e) {
            return $this->failNotFound([
                'error' => '找不到資源',
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            return $this->failServerError('移除父角色失敗: ' . $e->getMessage());
        }
    }
}
