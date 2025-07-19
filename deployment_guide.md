# Car Rental ERP - Deployment Guide

## System Overview

The Car Rental ERP is a comprehensive, multi-user, multi-role, mobile-friendly enterprise resource planning system designed specifically for car rental businesses. The system provides complete functionality for managing vehicles, customers, bookings, and business operations.

## Architecture

### Technology Stack

**Frontend**:
- React 18 with modern hooks and context
- Tailwind CSS for responsive styling
- shadcn/ui component library
- Lucide React for iconography
- React Router for client-side routing
- Vite for build tooling

**Backend**:
- Flask web framework
- SQLAlchemy ORM for database operations
- Flask-CORS for cross-origin requests
- JWT for authentication
- SQLite database (production-ready for small to medium businesses)

**Key Features**:
- Mobile-first responsive design
- Role-based access control
- JWT authentication
- RESTful API architecture
- Real-time dashboard
- Comprehensive business modules

## System Requirements

### Development Environment
- Node.js 20.18.0 or higher
- Python 3.11 or higher
- pnpm package manager
- Git for version control

### Production Environment
- Linux server (Ubuntu 22.04 recommended)
- 2GB RAM minimum (4GB recommended)
- 10GB disk space minimum
- SSL certificate for HTTPS
- Domain name or static IP

## Local Development Setup

### Backend Setup

1. **Navigate to backend directory**:
   ```bash
   cd car_rental_erp
   ```

2. **Create and activate virtual environment**:
   ```bash
   python3 -m venv venv
   source venv/bin/activate  # On Windows: venv\Scripts\activate
   ```

3. **Install dependencies**:
   ```bash
   pip install -r requirements.txt
   ```

4. **Start the backend server**:
   ```bash
   python src/main.py
   ```

   The backend will be available at `http://localhost:5001`

### Frontend Setup

1. **Navigate to frontend directory**:
   ```bash
   cd car-rental-erp-frontend
   ```

2. **Install dependencies**:
   ```bash
   pnpm install
   ```

3. **Start the development server**:
   ```bash
   pnpm run dev --host
   ```

   The frontend will be available at `http://localhost:5174`

### Default Login Credentials

- **Email**: admin@carrental.com
- **Password**: admin123
- **Role**: System Administrator

## Database Schema

The system uses a comprehensive database schema with the following core entities:

### User Management
- **users**: Core user information and authentication
- **user_roles**: Role definitions (admin, manager, agent, etc.)
- **user_role_assignments**: User-role mappings
- **permissions**: Granular permission system
- **role_permissions**: Role-permission mappings

### Customer Management
- **customers**: Customer profiles and information
- **customer_addresses**: Multiple addresses per customer
- **customer_documents**: Document storage and verification

### Fleet Management
- **vehicle_categories**: Vehicle classification system
- **vehicle_features**: Available vehicle features
- **vehicles**: Complete vehicle inventory
- **vehicle_category_features**: Feature-category mappings

### Location Management
- **locations**: Pickup and return locations
- **location_operating_hours**: Operating schedules

### Reservation System
- **reservations**: Booking and rental agreements
- **rental_agreements**: Detailed rental contracts
- **reservation_addons**: Additional services

### Financial Management
- **payments**: Payment processing and tracking
- **invoices**: Invoice generation and management
- **pricing_rules**: Dynamic pricing system

### Maintenance System
- **maintenance_schedules**: Preventive maintenance
- **damage_reports**: Vehicle damage tracking

## API Documentation

### Authentication Endpoints

#### POST /api/auth/login
Login user and receive JWT token.

**Request Body**:
```json
{
  "email": "admin@carrental.com",
  "password": "admin123"
}
```

**Response**:
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "user_id": "uuid",
    "email": "admin@carrental.com",
    "first_name": "System",
    "last_name": "Administrator",
    "user_type": "admin",
    "status": "active"
  },
  "roles": [
    {
      "role_id": "uuid",
      "role_name": "System Administrator"
    }
  ]
}
```

#### POST /api/auth/logout
Logout user (client-side token removal).

#### GET /api/auth/profile
Get current user profile (requires authentication).

### User Management Endpoints

#### GET /api/users
Get all users (admin only).

#### POST /api/users
Create new user (admin only).

#### GET /api/users/{user_id}
Get specific user details.

#### PUT /api/users/{user_id}
Update user information.

#### DELETE /api/users/{user_id}
Delete user (admin only).

### Customer Management Endpoints

#### GET /api/customers
Get all customers with pagination and filtering.

#### POST /api/customers
Create new customer.

#### GET /api/customers/{customer_id}
Get specific customer details.

#### PUT /api/customers/{customer_id}
Update customer information.

### Vehicle Management Endpoints

#### GET /api/vehicles
Get all vehicles with availability status.

#### POST /api/vehicles
Add new vehicle to fleet.

#### GET /api/vehicles/{vehicle_id}
Get specific vehicle details.

#### PUT /api/vehicles/{vehicle_id}
Update vehicle information.

#### GET /api/vehicles/categories
Get all vehicle categories.

#### GET /api/vehicles/available
Check vehicle availability for date range.

### Reservation Management Endpoints

#### GET /api/reservations
Get all reservations with filtering.

#### POST /api/reservations
Create new reservation.

#### GET /api/reservations/{reservation_id}
Get specific reservation details.

#### PUT /api/reservations/{reservation_id}
Update reservation.

#### POST /api/reservations/{reservation_id}/checkin
Process customer check-in.

#### POST /api/reservations/{reservation_id}/checkout
Process customer check-out.

### Location Management Endpoints

#### GET /api/locations
Get all locations.

#### POST /api/locations
Create new location.

#### GET /api/locations/{location_id}
Get specific location details.

### Financial Management Endpoints

#### GET /api/financial/payments
Get all payments.

#### POST /api/financial/payments
Process new payment.

#### GET /api/financial/invoices
Get all invoices.

#### POST /api/financial/invoices
Generate new invoice.

### Maintenance Management Endpoints

#### GET /api/maintenance/schedules
Get maintenance schedules.

#### POST /api/maintenance/schedules
Create maintenance schedule.

#### GET /api/maintenance/reports
Get damage reports.

#### POST /api/maintenance/reports
Create damage report.

## Security Features

### Authentication & Authorization
- JWT-based authentication with secure token generation
- Role-based access control with granular permissions
- Password hashing using secure algorithms
- Session management with token expiration
- Protected API endpoints with middleware validation

### Data Security
- Input validation and sanitization
- SQL injection prevention through ORM
- XSS protection through proper data handling
- CORS configuration for secure cross-origin requests
- Secure headers and middleware

### User Management
- Multi-role system (admin, manager, agent, customer service, etc.)
- Granular permission system
- User status management (active, inactive, suspended)
- Audit trails for user actions
- Two-factor authentication support (framework ready)

## Performance Optimization

### Frontend Optimization
- Code splitting for efficient loading
- Lazy loading of components
- Image optimization and responsive images
- Efficient state management
- Caching strategies for API responses

### Backend Optimization
- Database indexing for query performance
- Connection pooling for database efficiency
- API response caching
- Pagination for large datasets
- Optimized database queries

### Mobile Performance
- Mobile-first responsive design
- Touch-optimized interfaces
- Progressive Web App features
- Offline functionality support
- Optimized for Core Web Vitals

## Monitoring & Maintenance

### Health Checks
- API health endpoint: `GET /health`
- Database connectivity monitoring
- System resource monitoring
- Error logging and tracking

### Backup Strategy
- Regular database backups
- Configuration file backups
- User-uploaded file backups
- Automated backup scheduling
- Backup verification procedures

### Updates & Maintenance
- Rolling updates for zero downtime
- Database migration procedures
- Security patch management
- Performance monitoring
- User feedback collection

## Troubleshooting

### Common Issues

#### Frontend Issues
1. **Build Errors**: Check Node.js version and dependencies
2. **API Connection**: Verify backend URL in AuthContext
3. **Styling Issues**: Ensure Tailwind CSS is properly configured
4. **Routing Problems**: Check React Router configuration

#### Backend Issues
1. **Database Errors**: Verify SQLite file permissions
2. **CORS Issues**: Check Flask-CORS configuration
3. **Authentication Failures**: Verify JWT secret key
4. **Port Conflicts**: Use different port if 5001 is occupied

#### Integration Issues
1. **Login Failures**: Check API endpoint URLs
2. **Token Expiration**: Verify JWT expiration settings
3. **Role Access**: Check user role assignments
4. **Data Loading**: Verify API response formats

### Log Files
- Backend logs: Console output during development
- Frontend logs: Browser developer console
- Database logs: SQLite query logs (if enabled)
- Error logs: Application-specific error tracking

## Support & Documentation

### Additional Resources
- API documentation: Available through backend endpoints
- Component documentation: React component library
- Database schema: Complete ERD available
- User guides: Role-specific user manuals
- Developer documentation: Code comments and README files

### Contact Information
- Technical Support: Available through system administrator
- Documentation Updates: Version-controlled with system releases
- Feature Requests: Submit through issue tracking system
- Bug Reports: Include system logs and reproduction steps

This deployment guide provides comprehensive information for setting up, configuring, and maintaining the Car Rental ERP system in both development and production environments.

