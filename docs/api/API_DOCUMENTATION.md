# CRM RBAC API Documentation

Complete API reference for the CRM RBAC Permission Management System.

## Table of Contents

- [Overview](#overview)
- [Accessing the Documentation](#accessing-the-documentation)
- [Authentication](#authentication)
- [API Endpoints](#api-endpoints)
- [Request/Response Format](#requestresponse-format)
- [Error Handling](#error-handling)
- [Rate Limiting](#rate-limiting)
- [Testing the API](#testing-the-api)
- [Code Examples](#code-examples)

---

## Overview

The CRM RBAC API provides a complete RESTful interface for managing:

- **Authentication**: User login, logout, token refresh
- **Roles**: Create, update, delete, and query roles with hierarchy support
- **Permissions**: Query and manage permissions
- **Role Assignments**: Assign roles to users with time-based constraints
- **Audit Logs**: Track all system activities and changes
- **Authorization**: Check user permissions and authorization

### Base URL

```
Development: http://localhost:8080/api/v1
Staging:     https://staging-api.example.com/api/v1
Production:  https://api.example.com/api/v1
```

### API Version

Current version: **v1.0.0**

---

## Accessing the Documentation

### Interactive Documentation (Swagger UI)

Access the interactive API documentation at:

```
http://localhost:8080/api-docs.html
```

The Swagger UI provides:
- Complete endpoint listing with descriptions
- Request/response schemas
- Try-it-out functionality
- Code examples in multiple languages
- Authentication testing

### OpenAPI Specification

The OpenAPI 3.0 specification file is available at:

```
/backend/docs/openapi.yaml
```

You can import this file into:
- **Postman**: File → Import → openapi.yaml
- **Insomnia**: Create → Import From → File
- **Swagger Editor**: https://editor.swagger.io/

---

## Authentication

### JWT Bearer Authentication

All endpoints (except `/auth/login` and `/auth/refresh`) require authentication using JWT Bearer tokens.

#### Obtaining a Token

**Request:**
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "username": "admin",
  "password": "your-password"
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expires_in": 3600,
    "user": {
      "id": 1,
      "username": "admin",
      "email": "admin@example.com"
    }
  }
}
```

#### Using the Token

Include the token in the `Authorization` header:

```http
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

#### Token Expiration

- Access tokens expire after **3600 seconds (1 hour)** by default
- Use the refresh token to obtain a new access token without re-authenticating

**Request:**
```http
POST /api/v1/auth/refresh
Content-Type: application/json

{
  "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

---

## API Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/login` | User login |
| POST | `/auth/logout` | User logout |
| POST | `/auth/refresh` | Refresh access token |
| GET | `/auth/me` | Get current user info |

### Roles

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/roles` | List all roles |
| POST | `/roles` | Create new role |
| GET | `/roles/{id}` | Get role by ID |
| PUT | `/roles/{id}` | Update role |
| DELETE | `/roles/{id}` | Delete role |
| GET | `/roles/hierarchy` | Get role hierarchy tree |
| GET | `/roles/{id}/hierarchy` | Get role hierarchy info |
| PUT | `/roles/{id}/parent` | Update role parent |
| POST | `/roles/{id}/parents` | Add parent role |
| DELETE | `/roles/{id}/parents/{parentId}` | Remove parent role |
| GET | `/roles/{id}/permissions` | Get role permissions |
| POST | `/roles/{id}/permissions` | Assign permissions to role |

### Permissions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/permissions` | List all permissions |
| GET | `/permissions/{id}` | Get permission by ID |
| GET | `/permissions/modules` | Get permission modules |

### Role Assignments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/role-assignments` | List role assignments |
| POST | `/role-assignments` | Create role assignment |
| GET | `/role-assignments/{id}` | Get assignment by ID |
| DELETE | `/role-assignments/{id}` | Revoke assignment |
| PUT | `/role-assignments/{id}/extend` | Extend/shorten assignment |
| POST | `/role-assignments/bulk` | Bulk assign roles |
| GET | `/role-assignments/expiring` | Get expiring assignments |
| POST | `/role-assignments/check-authorization` | Check authorization |

### Audit Logs

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/audit-logs` | List audit logs |
| GET | `/audit-logs/{id}` | Get audit log by ID |
| GET | `/audit-logs/recent` | Get recent logs |
| GET | `/audit-logs/statistics` | Get statistics |
| GET | `/audit-logs/summary` | Get summary |
| POST | `/audit-logs/export` | Export logs |
| GET | `/audit-logs/user/{userId}` | Get user logs |
| GET | `/audit-logs/resource/{type}/{id}` | Get resource audit trail |

### User Permissions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/users/{userId}/permissions` | Get user permissions |
| GET | `/users/{userId}/roles` | Get user roles |
| POST | `/users/{userId}/check-permission` | Check user permission |

---

## Request/Response Format

### Request Headers

```http
Content-Type: application/json
Authorization: Bearer <token>
Accept: application/json
```

### Pagination

List endpoints support pagination:

**Query Parameters:**
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 20, max: 100)

**Example:**
```http
GET /api/v1/roles?page=2&per_page=50
```

**Response includes pagination metadata:**
```json
{
  "data": [...],
  "pagination": {
    "current_page": 2,
    "per_page": 50,
    "total": 245,
    "total_pages": 5
  }
}
```

### Filtering

Most list endpoints support filtering:

**Roles:**
- `is_system`: true/false
- `is_active`: true/false
- `search`: Search term

**Permissions:**
- `module`: Permission module
- `is_system`: true/false

**Role Assignments:**
- `user_id`: User ID
- `role_id`: Role ID
- `status`: active/expired/revoked

**Audit Logs:**
- `user_id`: User ID
- `entity_type`: Entity type
- `entity_id`: Entity ID
- `action`: Action type
- `start_date`: Start date (YYYY-MM-DD)
- `end_date`: End date (YYYY-MM-DD)

### Response Structure

**Success Response:**
```json
{
  "status": "success",
  "message": "Operation completed successfully",
  "data": { ... }
}
```

**Error Response:**
```json
{
  "status": "error",
  "message": "Error description",
  "code": 400
}
```

**Validation Error Response:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message 1", "Error message 2"]
  }
}
```

---

## Error Handling

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Internal Server Error |

### Common Error Messages

**401 Unauthorized:**
```json
{
  "status": "error",
  "message": "Invalid or expired token",
  "code": 401
}
```

**404 Not Found:**
```json
{
  "status": "error",
  "message": "Resource not found",
  "code": 404
}
```

**422 Validation Error:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required"],
    "email": ["The email field must be a valid email"]
  }
}
```

---

## Rate Limiting

The API implements rate limiting to prevent abuse:

- **Default**: 100 requests per 60 seconds per IP
- **Headers returned**:
  - `X-RateLimit-Limit`: Request limit
  - `X-RateLimit-Remaining`: Remaining requests
  - `X-RateLimit-Reset`: Unix timestamp when limit resets

**Rate Limit Exceeded Response:**
```json
{
  "status": "error",
  "message": "Too many requests. Please try again later.",
  "code": 429
}
```

---

## Testing the API

### Using cURL

**Login:**
```bash
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'
```

**Get Roles (with token):**
```bash
curl -X GET http://localhost:8080/api/v1/roles \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

**Create Role:**
```bash
curl -X POST http://localhost:8080/api/v1/roles \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "manager",
    "display_name": "Manager",
    "description": "Department manager role"
  }'
```

### Using Postman

1. Import the OpenAPI specification: `docs/openapi.yaml`
2. Create an environment with variable `baseUrl` = `http://localhost:8080/api/v1`
3. Authenticate using `/auth/login`
4. Save the token in environment variable `token`
5. Use `{{token}}` in Authorization header

### Using HTTPie

**Login:**
```bash
http POST localhost:8080/api/v1/auth/login \
  username=admin \
  password=password
```

**Get Roles:**
```bash
http GET localhost:8080/api/v1/roles \
  "Authorization:Bearer YOUR_TOKEN_HERE"
```

---

## Code Examples

### JavaScript (Fetch API)

```javascript
// Login
const login = async (username, password) => {
  const response = await fetch('http://localhost:8080/api/v1/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password })
  });
  const data = await response.json();
  return data.data.token;
};

// Get Roles
const getRoles = async (token) => {
  const response = await fetch('http://localhost:8080/api/v1/roles', {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  return await response.json();
};

// Create Role
const createRole = async (token, roleData) => {
  const response = await fetch('http://localhost:8080/api/v1/roles', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(roleData)
  });
  return await response.json();
};
```

### Python (requests)

```python
import requests

BASE_URL = 'http://localhost:8080/api/v1'

# Login
def login(username, password):
    response = requests.post(
        f'{BASE_URL}/auth/login',
        json={'username': username, 'password': password}
    )
    return response.json()['data']['token']

# Get Roles
def get_roles(token):
    response = requests.get(
        f'{BASE_URL}/roles',
        headers={'Authorization': f'Bearer {token}'}
    )
    return response.json()

# Create Role
def create_role(token, role_data):
    response = requests.post(
        f'{BASE_URL}/roles',
        json=role_data,
        headers={'Authorization': f'Bearer {token}'}
    )
    return response.json()
```

### PHP (Guzzle)

```php
<?php
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'http://localhost:8080/api/v1'
]);

// Login
$response = $client->post('/auth/login', [
    'json' => [
        'username' => 'admin',
        'password' => 'password'
    ]
]);
$data = json_decode($response->getBody(), true);
$token = $data['data']['token'];

// Get Roles
$response = $client->get('/roles', [
    'headers' => [
        'Authorization' => "Bearer {$token}"
    ]
]);
$roles = json_decode($response->getBody(), true);

// Create Role
$response = $client->post('/roles', [
    'headers' => [
        'Authorization' => "Bearer {$token}"
    ],
    'json' => [
        'name' => 'manager',
        'display_name' => 'Manager',
        'description' => 'Department manager role'
    ]
]);
```

---

## Advanced Topics

### Role Hierarchy

The API supports hierarchical roles with inheritance:

```javascript
// Get complete hierarchy tree
const hierarchyTree = await fetch('/api/v1/roles/hierarchy', {
  headers: { 'Authorization': `Bearer ${token}` }
});

// Get hierarchy info for specific role
const roleHierarchy = await fetch('/api/v1/roles/5/hierarchy', {
  headers: { 'Authorization': `Bearer ${token}` }
});

// Set parent role
await fetch('/api/v1/roles/10/parent', {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({ parent_role_id: 5 })
});
```

### Time-Based Role Assignments

Assign roles with time constraints:

```javascript
// Assign role with expiry
await fetch('/api/v1/role-assignments', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    user_id: 123,
    role_id: 5,
    valid_from: '2025-01-01 00:00:00',
    valid_until: '2025-12-31 23:59:59',
    reason: 'Annual access grant'
  })
});

// Find expiring assignments
const expiring = await fetch('/api/v1/role-assignments/expiring?days=7', {
  headers: { 'Authorization': `Bearer ${token}` }
});
```

### Scope-Based Permissions

Assign permissions with specific scope:

```javascript
// Assign permissions with scope
await fetch('/api/v1/roles/5/permissions', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    permissions: [
      {
        permission_id: 10,
        scope_type: 'organization',
        scope_id: 3
      },
      {
        permission_id: 15,
        scope_type: 'department',
        scope_id: 7,
        conditions: {
          max_amount: 10000,
          approval_required: true
        }
      }
    ]
  })
});
```

### Audit Trail Queries

Query audit logs for compliance and security:

```javascript
// Get user activity timeline
const userLogs = await fetch('/api/v1/audit-logs/user/123?page=1&per_page=50', {
  headers: { 'Authorization': `Bearer ${token}` }
});

// Get resource audit trail
const resourceAudit = await fetch('/api/v1/audit-logs/resource/role/5', {
  headers: { 'Authorization': `Bearer ${token}` }
});

// Export audit logs
const exportResponse = await fetch('/api/v1/audit-logs/export', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    format: 'csv',
    filters: {
      start_date: '2025-01-01',
      end_date: '2025-01-31',
      entity_type: 'role'
    }
  })
});
```

---

## Support

For API support and questions:

- **Documentation Issues**: Create an issue on GitHub
- **API Bugs**: Report via issue tracker
- **Feature Requests**: Submit via GitHub discussions

---

## Changelog

### v1.0.0 (2025-01-22)
- Initial API release
- Complete RBAC functionality
- Role hierarchy support
- Time-based assignments
- Comprehensive audit logging
