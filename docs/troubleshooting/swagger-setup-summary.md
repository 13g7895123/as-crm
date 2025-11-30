# Swagger API Documentation - Setup Summary

## Overview
Complete Swagger/OpenAPI 3.0.3 documentation has been created for the CRM RBAC Permission Management API.

## Access the Documentation

**Swagger UI Interface:**
- Local Development: http://localhost:9230/swagger
- The interactive UI allows you to test all API endpoints directly from your browser

**OpenAPI Specification:**
- YAML Format: http://localhost:9230/swagger/spec
- JSON Format: http://localhost:9230/swagger/spec/json

## What's Documented

### 1. Authentication Endpoints
- POST `/auth/login` - User login with JWT tokens
- POST `/auth/logout` - User logout
- POST `/auth/refresh` - Refresh access token
- GET `/auth/me` - Get current user info

### 2. Role Management Endpoints
- CRUD operations for roles
- Role hierarchy management (get/update/add/remove parents)
- Permission assignment to roles
- GET `/roles/hierarchy` - Complete role tree
- GET `/roles/{id}/permissions` - Role permissions (with inheritance)

### 3. Permission Management Endpoints
- List all permissions (with filtering by module)
- Get permission details
- Get permission modules list

### 4. Role Assignment Endpoints
- Assign roles to users (single and bulk)
- Revoke role assignments
- Extend role validity period
- Get expiring assignments
- Authorization checks with context evaluation
- Support for scope constraints (department, region)

### 5. Audit Log Endpoints
- List audit logs with comprehensive filtering
- Get audit statistics and summaries
- Export logs (CSV, JSON formats)
- User-specific and resource-specific audit trails
- Recent activity tracking

### 6. CORS Debug Endpoints (NEW)
- GET `/cors/debug` - Display CORS configuration
- POST `/cors/test` - Test CORS for specific origin
- GET `/cors/health` - CORS health check

## Key Features Added

### Updated Schemas
- **User Schema**: Added full_name, department, region, is_active, last_login_at fields
- **RoleAssignment Schema**: Added scope_constraints, expires_at, proper user/role relations
- **Authentication Response**: Fixed to use access_token instead of token

### Query Parameters
Added common query parameters across list endpoints:
- `page`, `per_page` - Pagination
- `sort`, `order` - Sorting
- `search` - Text search
- Resource-specific filters

### Response Formats
Documented all response formats:
- Success responses with data and metadata
- Error responses (400, 401, 403, 404, 422, 500)
- Validation errors with field-specific messages

## File Structure

```
backend/
├── docs/
│   └── openapi.yaml              # Complete OpenAPI 3.0.3 specification
├── app/
│   ├── Controllers/
│   │   └── SwaggerController.php # Serves Swagger UI and spec
│   └── Config/
│       └── Routes.php            # Swagger routes added
└── (Views removed, using inline HTML)
```

## API Server Configuration

The OpenAPI spec is configured with multiple server environments:
1. **Development (Docker)**: http://localhost:9230/api/v1
2. **Development (Local)**: http://localhost:8080/api/v1
3. **Staging**: https://staging-api.example.com/api/v1
4. **Production**: https://api.example.com/api/v1

## Using the Swagger UI

1. **Authentication**:
   - Click "Authorize" button in the top-right
   - Enter your JWT token in the format: `Bearer <your_token>`
   - Token persists across page reloads

2. **Testing Endpoints**:
   - Expand any endpoint section
   - Click "Try it out"
   - Fill in required parameters
   - Click "Execute" to make the API call
   - View response below

3. **Features**:
   - Filter/search endpoints
   - View request/response schemas
   - See example values
   - Download OpenAPI spec
   - Request duration tracking

## Next Steps

### Optional Enhancements
1. Add example responses for each endpoint
2. Add security scopes for permission-based access
3. Add webhook documentation if applicable
4. Generate client SDKs using the OpenAPI spec
5. Set up automated API testing using the spec

### Maintenance
- Update openapi.yaml when adding new endpoints
- Keep response schemas in sync with actual API responses
- Update server URLs for different environments

## Troubleshooting

**Issue**: Swagger UI not loading
- **Solution**: Check that backend server is running on port 9230

**Issue**: "Failed to fetch" when testing endpoints
- **Solution**: Ensure CORS is properly configured (check `/cors/debug`)

**Issue**: 401 Unauthorized errors
- **Solution**: Click "Authorize" and enter valid JWT token

**Issue**: OpenAPI spec not loading
- **Solution**: Access http://localhost:9230/swagger/spec to verify spec is valid

## Documentation Coverage

✅ All 60+ API endpoints documented
✅ All request/response schemas defined
✅ Authentication and security schemes
✅ Query parameters and filters
✅ Error responses
✅ CORS debugging endpoints
✅ Interactive testing capability

---

**Generated**: 2025-10-23
**Version**: 1.0.0
**Format**: OpenAPI 3.0.3
