# Laravel Microservices with Authentication

This project demonstrates a complete microservices architecture using Laravel with authentication, featuring scalable design and consistency maintenance.

## 🏗️ Architecture Overview

### Services
- **API Gateway** (Port 8003): Entry point for all client requests, handles routing and load balancing
- **Auth Service** (Port 8002): Handles user authentication, registration, and JWT token management
- **User Service** (Port 8000): Manages user profiles and data
- **Order Service** (Port 8001): Handles order management with user validation

### Key Features
- **JWT Authentication**: Stateless token-based authentication across all services
- **Service Isolation**: Each service has its own database and can scale independently
- **Inter-Service Communication**: HTTP-based communication with fallback mechanisms
- **Consistency Patterns**: Database transactions and Saga pattern for data consistency
- **Security**: Authentication middleware and token validation

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- Database (SQLite by default)

### Installation & Setup

1. **Setup All Services**:
   ```bash
   # Auth Service
   cd auth-service
   cp .env.example .env
   composer install
   php artisan key:generate
   php artisan migrate

   # User Service
   cd ../user-service
   cp .env.example .env
   composer install
   php artisan key:generate
   php artisan migrate

   # Order Service
   cd ../order-service
   cp .env.example .env
   composer install
   php artisan key:generate
   php artisan migrate

   # API Gateway
   cd ../api-gateway
   cp .env.example .env
   composer install
   php artisan key:generate
   ```

2. **Start All Services**:
   ```bash
   # Terminal 1 - Auth Service
   cd auth-service && php artisan serve --port=8000

   # Terminal 2 - User Service
   cd user-service && php artisan serve --port=8001

   # Terminal 3 - Order Service
   cd order-service && php artisan serve --port=8002

   # Terminal 4 - API Gateway
   cd api-gateway && php artisan serve --port=8003
   ```

## 📋 Complete User Flow

### 1. User Registration
```bash
curl -X POST http://localhost:8003/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
  }'
```

### 2. User Login
```bash
curl -X POST http://localhost:8003/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

**Response includes JWT token:**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

### 3. Create Order (Authenticated)
```bash
curl -X POST http://localhost:8003/api/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "product": "Laptop",
    "quantity": 1,
    "total": 999.99
  }'
```

### 4. Get User Orders
```bash
curl -X GET http://localhost:8003/api/orders \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## 🔐 Authentication System

### JWT Token Flow
1. **Registration/Login** → Auth Service generates JWT token
2. **API Requests** → Include `Authorization: Bearer <token>` header
3. **Token Validation** → Each service validates token independently
4. **User Context** → Token payload provides user ID and email

### Security Features
- **Token Expiration**: 1-hour token validity
- **Stateless Authentication**: No server-side session storage
- **Service-Level Validation**: Each service validates tokens independently
- **User Isolation**: Users can only access their own data

## 🗄️ Database Architecture

### Auth Service Database
- **users**: Authentication data (id, name, email, password_hash)

### User Service Database
- **users**: User profiles (id, name, email, created_at, updated_at)

### Order Service Database
- **orders**: Order data (id, user_id, product, quantity, total, timestamps)

### Data Consistency
- **Saga Pattern**: Two-phase commit across services
- **Transaction Management**: Database transactions with rollback
- **Fallback Mechanisms**: Graceful degradation when services are unavailable

## 🔄 Inter-Service Communication

### Synchronous Communication
- **HTTP Calls**: RESTful API communication between services
- **Timeout Handling**: 5-second timeout with fallback
- **Error Propagation**: Proper error codes and messages

### Service Dependencies
```
Auth Service → User Service (sync user creation)
Order Service → User Service (validate user existence)
API Gateway → All Services (proxy requests)
```

## 📊 API Endpoints

### Authentication (Port 8002)
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `POST /api/validate-token` - Token validation
- `GET /api/profile` - Get user profile

### User Management (Port 8000)
- `GET /api/users` - List users (authenticated)
- `GET /api/users/{id}` - Get user (authenticated)
- `PUT /api/users/{id}` - Update user (authenticated)
- `DELETE /api/users/{id}` - Delete user (authenticated)

### Order Management (Port 8001)
- `GET /api/orders` - List user orders (authenticated)
- `POST /api/orders` - Create order (authenticated)
- `GET /api/orders/{id}` - Get order (authenticated)
- `PUT /api/orders/{id}` - Update order (authenticated)
- `DELETE /api/orders/{id}` - Delete order (authenticated)
- `GET /api/orders/statistics` - Order statistics (authenticated)

### API Gateway (Port 8003)
- `GET /api/health` - Health check for all services
- All endpoints above are available through the gateway

## 🧪 Testing the System

### Health Check
```bash
curl http://localhost:8003/api/health
```

### Complete Flow Test
```bash
# 1. Register user
curl -X POST http://localhost:8003/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123"}'

# 2. Login (get token)
TOKEN=$(curl -X POST http://localhost:8003/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}' \
  | jq -r '.token')

# 3. Create order
curl -X POST http://localhost:8003/api/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"product":"Test Product","quantity":2,"total":199.99}'

# 4. Get orders
curl -X GET http://localhost:8003/api/orders \
  -H "Authorization: Bearer $TOKEN"
```

## 🔧 Configuration

### Environment Variables
Each service has its own `.env` file:

```env
# Common settings
APP_NAME="Service Name"
APP_ENV=local
APP_KEY=base64:your-app-key
APP_DEBUG=true

# Database
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Service-specific ports
APP_PORT=8000  # Adjust per service
```

### JWT Configuration
Update the secret key in all services:
```php
private static $secretKey = 'your-production-secret-key';
```

## 🚀 Scaling & Production

### Horizontal Scaling
- **Stateless Services**: All services are stateless and can be scaled horizontally
- **Load Balancing**: Use API Gateway with load balancer
- **Database Scaling**: Separate read/write databases per service

### Monitoring
- **Health Checks**: `/api/health` endpoint for service monitoring
- **Logging**: Comprehensive logging for debugging
- **Metrics**: Track API usage and performance

### Production Deployment
1. **Containerization**: Use Docker for each service
2. **Orchestration**: Kubernetes for service management
3. **API Gateway**: Nginx or Traefik for production routing
4. **Database**: Managed database services (RDS, Cloud SQL)

## 🛡️ Security Considerations

- **HTTPS**: Use HTTPS in production
- **Token Rotation**: Implement token refresh mechanism
- **Rate Limiting**: Add rate limiting to prevent abuse
- **Input Validation**: Comprehensive validation on all endpoints
- **CORS**: Configure CORS policies appropriately

## 📝 Development Guidelines

- **Service Boundaries**: Keep services focused on single responsibility
- **API Contracts**: Maintain consistent API responses
- **Error Handling**: Implement proper error responses
- **Testing**: Write unit and integration tests for each service
- **Documentation**: Keep API documentation updated

This architecture provides a solid foundation for scalable microservices with proper authentication, security, and maintainability.
