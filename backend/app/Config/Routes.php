<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

/**
 * Routes Configuration
 *
 * API v1 路由結構
 * RESTful routes for roles, permissions, role-assignments, audit-logs
 */

// 預設路由
$routes->get('/', 'Home::index');

/**
 * API v1 Routes
 *
 * 所有 API 路由使用 /api/v1 前綴
 * 支援版本控制和向後相容
 */
$routes->group('api/v1', ['namespace' => 'App\Controllers\API'], function ($routes) {

    /**
     * 身份驗證路由 (Authentication)
     * 不需要 AuthFilter,因為登入本身就是取得 token 的過程
     */
    $routes->post('auth/login', 'AuthController::login', ['as' => 'api.auth.login']);
    $routes->post('auth/logout', 'AuthController::logout', ['as' => 'api.auth.logout', 'filter' => 'auth']);
    $routes->post('auth/refresh', 'AuthController::refresh', ['as' => 'api.auth.refresh']);
    $routes->get('auth/me', 'AuthController::me', ['as' => 'api.auth.me', 'filter' => 'auth']);

    /**
     * 角色管理路由 (Roles)
     * 需要 AuthFilter 驗證身份
     */
    $routes->group('roles', ['filter' => 'auth'], function ($routes) {
        $routes->get('/', 'RoleController::index', ['as' => 'api.roles.index']);
        $routes->get('(:num)', 'RoleController::show/$1', ['as' => 'api.roles.show']);
        $routes->post('/', 'RoleController::create', ['as' => 'api.roles.create']);
        $routes->put('(:num)', 'RoleController::update/$1', ['as' => 'api.roles.update']);
        $routes->delete('(:num)', 'RoleController::delete/$1', ['as' => 'api.roles.delete']);

        // 角色階層相關路由
        $routes->get('(:num)/hierarchy', 'RoleController::hierarchy/$1', ['as' => 'api.roles.hierarchy']);
        $routes->put('(:num)/parent', 'RoleController::updateParent/$1', ['as' => 'api.roles.updateParent']);

        // 角色權限相關路由
        $routes->get('(:num)/permissions', 'RoleController::permissions/$1', ['as' => 'api.roles.permissions']);
        $routes->post('(:num)/permissions', 'RoleController::assignPermissions/$1', ['as' => 'api.roles.assignPermissions']);
    });

    /**
     * 權限管理路由 (Permissions)
     * 需要 AuthFilter 驗證身份
     */
    $routes->group('permissions', ['filter' => 'auth'], function ($routes) {
        $routes->get('/', 'PermissionController::index', ['as' => 'api.permissions.index']);
        $routes->get('(:num)', 'PermissionController::show/$1', ['as' => 'api.permissions.show']);
        $routes->get('modules', 'PermissionController::modules', ['as' => 'api.permissions.modules']);
    });

    /**
     * 角色指派路由 (Role Assignments)
     * 需要 AuthFilter 驗證身份
     */
    $routes->group('role-assignments', ['filter' => 'auth'], function ($routes) {
        $routes->get('/', 'RoleAssignmentController::index', ['as' => 'api.roleAssignments.index']);
        $routes->get('(:num)', 'RoleAssignmentController::show/$1', ['as' => 'api.roleAssignments.show']);
        $routes->post('/', 'RoleAssignmentController::create', ['as' => 'api.roleAssignments.create']);
        $routes->put('(:num)', 'RoleAssignmentController::update/$1', ['as' => 'api.roleAssignments.update']);
        $routes->delete('(:num)', 'RoleAssignmentController::revoke/$1', ['as' => 'api.roleAssignments.revoke']);

        // 延長/縮短角色有效期限
        $routes->put('(:num)/extend', 'RoleAssignmentController::extend/$1', ['as' => 'api.roleAssignments.extend']);

        // 查詢特定使用者的角色
        $routes->get('user/(:num)', 'RoleAssignmentController::userRoles/$1', ['as' => 'api.roleAssignments.userRoles']);
    });

    /**
     * 審計記錄路由 (Audit Logs)
     * 需要 AuthFilter 驗證身份
     */
    $routes->group('audit-logs', ['filter' => 'auth'], function ($routes) {
        $routes->get('/', 'AuditLogController::index', ['as' => 'api.auditLogs.index']);
        $routes->get('(:num)', 'AuditLogController::show/$1', ['as' => 'api.auditLogs.show']);
        $routes->post('export', 'AuditLogController::export', ['as' => 'api.auditLogs.export']);

        // 查詢特定使用者的操作記錄
        $routes->get('user/(:num)', 'AuditLogController::userLogs/$1', ['as' => 'api.auditLogs.userLogs']);
    });

    /**
     * 使用者權限查詢路由 (User Permissions)
     * 需要 AuthFilter 驗證身份
     */
    $routes->group('users', ['filter' => 'auth'], function ($routes) {
        $routes->get('(:num)/permissions', 'UserPermissionController::permissions/$1', ['as' => 'api.users.permissions']);
        $routes->get('(:num)/roles', 'UserPermissionController::roles/$1', ['as' => 'api.users.roles']);
        $routes->post('(:num)/check-permission', 'UserPermissionController::checkPermission/$1', ['as' => 'api.users.checkPermission']);
    });
});

/**
 * 404 Override
 * 處理找不到的路由
 */
$routes->set404Override(function () {
    return response()->setJSON([
        'status'  => 'error',
        'message' => '找不到請求的資源',
        'code'    => 404,
    ])->setStatusCode(404);
});
