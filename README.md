# CRM RBAC Permission Management System

A comprehensive Role-Based Access Control (RBAC) system with hierarchical roles, time-based assignments, and advanced permission management features.

[![Tests](https://github.com/yourusername/crm-rbac/actions/workflows/test.yml/badge.svg)](https://github.com/yourusername/crm-rbac/actions/workflows/test.yml)
[![Build](https://github.com/yourusername/crm-rbac/actions/workflows/build.yml/badge.svg)](https://github.com/yourusername/crm-rbac/actions/workflows/build.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

## 📋 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Architecture](#-architecture)
- [Prerequisites](#-prerequisites)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Usage](#-usage)
- [API Documentation](#-api-documentation)
- [Testing](#-testing)
- [Deployment](#-deployment)
- [Security](#-security)
- [Performance](#-performance)
- [Contributing](#-contributing)
- [License](#-license)

## ✨ Features

### Core Features

- **Role Management**
  - Create, update, and delete roles
  - System and custom roles
  - Role activation/deactivation
  - Hierarchical role inheritance

- **Permission Management**
  - Module-based permission organization
  - Granular permission control
  - Scope-based permissions (organization, department, etc.)
  - Condition-based access rules

- **User Role Assignments**
  - Time-based role assignments with expiry
  - Automatic expiration handling
  - Bulk role assignment operations
  - Role assignment history tracking

- **Role Hierarchy**
  - Parent-child role relationships
  - Permission inheritance
  - Circular dependency prevention
  - Ancestor and descendant traversal

- **Audit Logging**
  - Comprehensive activity tracking
  - Resource audit trails
  - User activity timeline
  - Security analysis and reporting

- **Authorization**
  - Real-time permission checking
  - Context-aware authorization
  - Effective permissions calculation
  - Authorization caching

### Advanced Features

- **Security**
  - JWT authentication
  - Rate limiting
  - Security headers
  - CORS protection
  - Input validation and sanitization

- **Performance**
  - Database indexing
  - Query optimization
  - Response caching
  - Efficient hierarchy traversal

- **Developer Experience**
  - Comprehensive API documentation (OpenAPI/Swagger)
  - Interactive API explorer
  - Health check endpoints
  - Detailed error messages

## 🛠 Tech Stack

### Backend

- **Framework**: CodeIgniter 4.4.8
- **Language**: PHP 8.1+
- **Database**: MariaDB 10.6+
- **Testing**: PHPUnit
- **Authentication**: JWT

### Frontend

- **Framework**: Nuxt 3
- **Language**: TypeScript
- **UI Framework**: Vue 3
- **Styling**: Tailwind CSS
- **State Management**: Pinia
- **Testing**: Vitest, Playwright

### DevOps

- **CI/CD**: GitHub Actions
- **Deployment**: Docker, Shell Scripts
- **Monitoring**: Health checks, Audit logs

## 🏗 Architecture

```
crm-rbac/
├── backend/                 # CodeIgniter 4 API
│   ├── app/
│   │   ├── Controllers/    # API endpoints
│   │   ├── Models/         # Data models
│   │   ├── Services/       # Business logic
│   │   ├── Filters/        # Middleware
│   │   └── Helpers/        # Utility functions
│   ├── tests/              # PHPUnit tests
│   └── docs/               # API documentation
│
├── frontend/               # Nuxt 3 application
│   ├── pages/             # Vue pages/routes
│   ├── components/        # Reusable components
│   ├── composables/       # Composition functions
│   ├── stores/            # Pinia stores
│   └── tests/             # Vitest + Playwright tests
│
├── scripts/               # Deployment scripts
│   ├── backup-database.sh
│   ├── run-migrations.sh
│   └── health-check.sh
│
├── docs/                  # Project documentation
└── .github/workflows/     # CI/CD workflows
```

### Database Schema

The system uses 6 core tables:

1. **roles** - Role definitions
2. **permissions** - Permission definitions
3. **role_permissions** - Role-permission mappings
4. **user_roles** - User role assignments
5. **role_hierarchy** - Role parent-child relationships
6. **audit_logs** - Comprehensive audit trail

## 📋 Prerequisites

### Required

- PHP 8.1 or higher
- Composer 2.x
- MariaDB 10.6+ or MySQL 8.0+
- Node.js 18.x or 20.x
- npm 9.x or higher

### Optional

- Docker & Docker Compose (for containerized deployment)
- Git (for version control)

## 🚀 Installation

### Quick Start with Docker (Recommended)

```bash
# Clone repository
git clone https://github.com/yourusername/crm-rbac.git
cd crm-rbac

# Start all services
./build-dev.sh
```

Services will be available at:
- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8080
- **Database**: localhost:3306

### Manual Installation

#### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/crm-rbac.git
cd crm-rbac
```

#### 2. Backend Setup

```bash
# Navigate to backend directory
cd backend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Edit .env and configure database credentials
nano .env

# Run database migrations
php spark migrate

# (Optional) Seed database with sample data
php spark db:seed DatabaseSeeder

# Start development server
php spark serve
```

The backend API will be available at `http://localhost:8080`

#### 3. Frontend Setup

```bash
# Navigate to frontend directory
cd ../frontend

# Install dependencies
npm install

# Copy environment file
cp .env.example .env

# Edit .env and configure API URL
nano .env

# Start development server
npm run dev
```

The frontend will be available at `http://localhost:3000`

#### 4. Verify Installation

Run the health check script:

```bash
cd ../scripts
chmod +x health-check.sh
./health-check.sh --verbose
```

## ⚙️ Configuration

### Backend Configuration

Edit `backend/.env`:

```bash
# Database
database.default.hostname = localhost
database.default.database = crm_rbac
database.default.username = root
database.default.password = your-password

# JWT Authentication
JWT_SECRET_KEY = your-secret-key-change-in-production
JWT_TIME_TO_LIVE = 3600
JWT_REFRESH_TIME_TO_LIVE = 20160

# CORS
CORS_ALLOWED_ORIGINS = http://localhost:3000
CORS_ALLOWED_HEADERS = Content-Type,Authorization,X-Requested-With
CORS_ALLOWED_METHODS = GET,POST,PUT,DELETE,OPTIONS

# Rate Limiting
RATE_LIMIT_ENABLED = true
RATE_LIMIT_REQUESTS = 100
RATE_LIMIT_PERIOD = 60

# Audit Log Retention
AUDIT_LOG_RETENTION_DAYS = 90
```

### Frontend Configuration

Edit `frontend/.env`:

```bash
# API Configuration
NUXT_PUBLIC_API_BASE_URL=http://localhost:8080/api/v1

# Application
NUXT_PUBLIC_APP_NAME=CRM RBAC System
NUXT_PUBLIC_APP_VERSION=1.0.0

# Authentication
NUXT_PUBLIC_SESSION_TIMEOUT=60
```

## 📖 Usage

### Quick Start

1. **Login to the system**
   ```
   Default credentials:
   Username: admin
   Password: admin123
   ```

2. **Create a new role**
   - Navigate to Roles → Create Role
   - Fill in role details
   - Assign permissions
   - Save

3. **Assign role to user**
   - Navigate to Role Assignments → Create Assignment
   - Select user and role
   - Set validity period (optional)
   - Confirm assignment

4. **View audit logs**
   - Navigate to Audit Logs
   - Filter by user, action, or date range
   - Export logs as needed

### Common Operations

#### Create Role with Hierarchy

```bash
# Via API
curl -X POST http://localhost:8080/api/v1/roles \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "manager",
    "display_name": "Manager",
    "description": "Department manager",
    "parent_role_id": 1
  }'
```

#### Assign Role with Expiry

```bash
# Via API
curl -X POST http://localhost:8080/api/v1/role-assignments \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 5,
    "role_id": 3,
    "valid_from": "2025-01-01 00:00:00",
    "valid_until": "2025-12-31 23:59:59"
  }'
```

#### Check User Permission

```bash
# Via API
curl -X POST http://localhost:8080/api/v1/users/5/check-permission \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "permission_name": "users.create"
  }'
```

## 📚 API Documentation

### Interactive Documentation

Access the interactive Swagger UI documentation:

```
http://localhost:8080/api-docs.html
```

### OpenAPI Specification

The complete OpenAPI 3.0 specification is available at:

```
/backend/docs/openapi.yaml
```

### Quick API Reference

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/auth/login` | POST | User login |
| `/api/v1/roles` | GET | List all roles |
| `/api/v1/roles` | POST | Create new role |
| `/api/v1/roles/{id}` | GET | Get role details |
| `/api/v1/roles/hierarchy` | GET | Get role hierarchy tree |
| `/api/v1/permissions` | GET | List all permissions |
| `/api/v1/role-assignments` | POST | Assign role to user |
| `/api/v1/audit-logs` | GET | Get audit logs |

For complete API documentation, see [API_DOCUMENTATION.md](backend/docs/API_DOCUMENTATION.md)

## 🧪 Testing

### Backend Tests

```bash
cd backend

# Run all tests
vendor/bin/phpunit

# Run with coverage
vendor/bin/phpunit --coverage-html coverage

# Run specific test suite
vendor/bin/phpunit --testsuite Unit
```

### Frontend Tests

```bash
cd frontend

# Run unit tests
npm run test:unit

# Run unit tests with coverage
npm run test:unit -- --coverage

# Run E2E tests
npm run test:e2e

# Run E2E tests in UI mode
npm run test:e2e --ui
```

### Integration Tests

```bash
# Run health check
./scripts/health-check.sh --verbose

# Test database connectivity
./scripts/health-check.sh --json
```

## 🚢 Deployment

### Using Docker

```bash
# Build images
docker-compose build

# Start services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down
```

### Manual Deployment

```bash
# 1. Backup database
./scripts/backup-database.sh production

# 2. Run migrations
./scripts/run-migrations.sh

# 3. Build frontend
cd frontend && npm run build

# 4. Deploy to server (customize for your environment)
rsync -avz --exclude='.git' ./ user@server:/path/to/deploy/

# 5. Restart services
sudo systemctl restart php8.1-fpm nginx
pm2 restart crm-frontend

# 6. Verify deployment
./scripts/health-check.sh --verbose
```

### CI/CD

The project includes GitHub Actions workflows for:

- **Tests**: Automated testing on every push/PR
- **Build**: Building artifacts and Docker images
- **Deploy**: Automated deployment to staging/production

See [.github/workflows/](.github/workflows/) for details.

## 🔒 Security

### Authentication

- JWT-based authentication
- Secure password hashing (bcrypt)
- Token refresh mechanism
- Automatic token expiration

### Authorization

- Role-based access control
- Permission-level granularity
- Scope-based restrictions
- Context-aware authorization

### Protection

- Rate limiting (configurable)
- CORS protection
- Security headers (CSP, X-Frame-Options, etc.)
- Input validation and sanitization
- SQL injection prevention (prepared statements)
- XSS protection

### Audit

- Comprehensive audit logging
- IP address tracking
- User agent logging
- Resource change tracking

## ⚡ Performance

### Optimizations

- **Database**
  - Indexed columns for common queries
  - Optimized JOIN operations
  - Query result caching
  - Connection pooling

- **Application**
  - Service layer architecture
  - Efficient hierarchy traversal
  - Response caching
  - Lazy loading

- **API**
  - Pagination support
  - Field filtering
  - Compression (gzip)
  - CDN-friendly headers

For performance tuning, see [database-indexing-strategy.md](backend/docs/database-indexing-strategy.md)

## 🤝 Contributing

We welcome contributions! Please see our Contributing Guide for details.

### Development Workflow

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Write/update tests
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### Code Standards

- **PHP**: PSR-12 coding standard
- **TypeScript**: ESLint + Prettier
- **Commits**: Conventional Commits format
- **Tests**: Required for new features

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🙏 Acknowledgments

- CodeIgniter 4 team for the excellent PHP framework
- Nuxt.js team for the powerful Vue framework
- All contributors and maintainers

## 📧 Support

- **Issues**: [GitHub Issues](https://github.com/yourusername/crm-rbac/issues)
- **Documentation**: [/docs](docs/)

## 🗺 Roadmap

- [ ] Multi-factor authentication (MFA)
- [ ] LDAP/Active Directory integration
- [ ] Advanced reporting dashboard
- [ ] Mobile application
- [ ] API versioning (v2)
- [ ] GraphQL support
- [ ] Real-time notifications
- [ ] Role templates and presets

---

**Built with ❤️ using CodeIgniter 4 and Nuxt 3**
