# Car Rental ERP System

A comprehensive, multi-user, multi-role, mobile-friendly Enterprise Resource Planning system designed specifically for car rental businesses.

## 🚀 Quick Start

### Demo Access
- **Frontend**: http://localhost:5174
- **Backend API**: http://localhost:5001
- **Login**: admin@carrental.com / admin123

### Local Development

#### Backend Setup
```bash
cd backend
python3 -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
pip install -r requirements.txt
python src/main.py
```

#### Frontend Setup
```bash
cd frontend
pnpm install
pnpm run dev --host
```

## 📋 System Overview

### Technology Stack
- **Frontend**: React 18, Tailwind CSS, shadcn/ui, Vite
- **Backend**: Flask, SQLAlchemy, JWT Authentication
- **Database**: SQLite (production-ready for small to medium businesses)

### Key Features
- ✅ Multi-user, multi-role architecture (10+ user types)
- ✅ Mobile-first responsive design
- ✅ Real-time dashboard and analytics
- ✅ Complete car rental business functionality
- ✅ Secure JWT authentication
- ✅ Role-based access control
- ✅ RESTful API architecture

## 👥 User Roles

- **System Administrator**: Complete system access
- **Business Manager**: Strategic oversight and reporting
- **Operations Manager**: Day-to-day operations
- **Fleet Manager**: Vehicle and maintenance management
- **Rental Agent**: Customer service and bookings
- **Customer Service Rep**: Customer support
- **Financial Analyst**: Financial operations
- **Maintenance Coordinator**: Vehicle service
- **Marketing Specialist**: Promotions and engagement
- **Audit & Compliance Officer**: Regulatory compliance

## 🏗️ System Architecture

### Backend Structure
```
backend/
├── src/
│   ├── main.py              # Flask application entry point
│   ├── models/              # Database models
│   │   ├── user.py          # User management
│   │   ├── customer.py      # Customer management
│   │   ├── vehicle.py       # Fleet management
│   │   ├── reservation.py   # Booking system
│   │   ├── financial.py     # Payment and invoicing
│   │   ├── maintenance.py   # Vehicle maintenance
│   │   └── location.py      # Location management
│   └── routes/              # API endpoints
│       ├── auth.py          # Authentication
│       ├── user.py          # User management
│       ├── customer.py      # Customer operations
│       ├── vehicle.py       # Fleet operations
│       ├── reservation.py   # Booking operations
│       ├── financial.py     # Financial operations
│       ├── maintenance.py   # Maintenance operations
│       └── location.py      # Location operations
├── requirements.txt         # Python dependencies
└── venv/                   # Virtual environment
```

### Frontend Structure
```
frontend/
├── src/
│   ├── App.jsx             # Main application component
│   ├── App.css             # Design system styles
│   ├── contexts/           # React contexts
│   │   └── AuthContext.jsx # Authentication state
│   └── components/         # React components
│       ├── Layout.jsx      # Main layout
│       ├── auth/           # Authentication components
│       ├── dashboard/      # Dashboard components
│       ├── navigation/     # Navigation components
│       ├── reservations/   # Booking components
│       ├── fleet/          # Fleet management
│       ├── customers/      # Customer management
│       ├── reports/        # Reporting components
│       └── settings/       # Settings components
├── package.json            # Node.js dependencies
└── dist/                  # Built application
```

## 🔧 API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/auth/profile` - Get user profile

### User Management
- `GET /api/users` - List all users
- `POST /api/users` - Create new user
- `GET /api/users/{id}` - Get user details
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user

### Customer Management
- `GET /api/customers` - List customers
- `POST /api/customers` - Create customer
- `GET /api/customers/{id}` - Get customer details
- `PUT /api/customers/{id}` - Update customer

### Fleet Management
- `GET /api/vehicles` - List vehicles
- `POST /api/vehicles` - Add vehicle
- `GET /api/vehicles/{id}` - Get vehicle details
- `PUT /api/vehicles/{id}` - Update vehicle
- `GET /api/vehicles/available` - Check availability

### Reservation Management
- `GET /api/reservations` - List reservations
- `POST /api/reservations` - Create reservation
- `GET /api/reservations/{id}` - Get reservation details
- `PUT /api/reservations/{id}` - Update reservation
- `POST /api/reservations/{id}/checkin` - Check-in
- `POST /api/reservations/{id}/checkout` - Check-out

### Financial Management
- `GET /api/financial/payments` - List payments
- `POST /api/financial/payments` - Process payment
- `GET /api/financial/invoices` - List invoices
- `POST /api/financial/invoices` - Generate invoice

### Maintenance Management
- `GET /api/maintenance/schedules` - List schedules
- `POST /api/maintenance/schedules` - Create schedule
- `GET /api/maintenance/reports` - List reports
- `POST /api/maintenance/reports` - Create report

### Location Management
- `GET /api/locations` - List locations
- `POST /api/locations` - Create location
- `GET /api/locations/{id}` - Get location details

## 🔒 Security Features

- **JWT Authentication**: Secure token-based authentication
- **Role-based Access Control**: Granular permission system
- **Input Validation**: Comprehensive data validation
- **CORS Configuration**: Secure cross-origin requests
- **Password Security**: Secure password hashing
- **Audit Trails**: User action logging

## 📱 Mobile Features

- **Responsive Design**: Works on all screen sizes
- **Touch Optimization**: Mobile-friendly interactions
- **Bottom Navigation**: Mobile-specific navigation
- **Progressive Web App**: Native app-like experience
- **Offline Support**: Basic offline functionality

## 🚀 Deployment

See `deployment_guide.md` for comprehensive deployment instructions including:
- Local development setup
- Production deployment with nginx
- Docker containerization
- Cloud platform deployment
- Security configuration
- Performance optimization

## 📚 Documentation

- `deployment_guide.md` - Complete deployment instructions
- `user_manual.md` - Comprehensive user guide
- `project_summary.md` - Project overview and achievements
- `database_design.md` - Database schema and architecture
- `ui_ux_design_concept.md` - Design system and guidelines
- `wireframes_and_user_flows.md` - Interface design documentation

## 🛠️ Development

### Prerequisites
- Node.js 20.18.0 or higher
- Python 3.11 or higher
- pnpm package manager
- Git

### Environment Setup
1. Clone the repository
2. Set up backend virtual environment
3. Install backend dependencies
4. Set up frontend dependencies
5. Start development servers

### Contributing
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📊 Business Modules

### 1. Dashboard & Analytics
- Real-time business metrics
- Key performance indicators
- Interactive charts and graphs
- Role-based dashboard views

### 2. Fleet Management
- Vehicle inventory management
- Maintenance scheduling
- Availability tracking
- Performance analytics

### 3. Reservation System
- Complete booking lifecycle
- Customer check-in/check-out
- Conflict resolution
- Dynamic pricing

### 4. Customer Management
- Customer profiles and history
- Document management
- Communication preferences
- Loyalty programs

### 5. Financial Management
- Payment processing
- Invoice generation
- Revenue tracking
- Financial reporting

### 6. Location Management
- Multi-location support
- Operating hours
- Location-based operations
- Geographic analytics

### 7. Maintenance System
- Preventive maintenance
- Damage reporting
- Service tracking
- Cost analysis

### 8. User Management
- Role-based access control
- User administration
- Permission management
- Audit trails

## 🎯 Future Enhancements

- Native mobile applications (iOS/Android)
- Advanced analytics and AI insights
- GPS tracking and IoT integration
- Payment gateway integrations
- Multi-language support
- Advanced reporting and BI

## 📞 Support

For technical support, feature requests, or bug reports:
- Review the documentation in this repository
- Check the troubleshooting section in the user manual
- Contact your system administrator

## 📄 License

This project is proprietary software developed for car rental business operations.

## 🏆 Project Status

**Status**: ✅ Complete and Production Ready
**Version**: 1.0.0
**Last Updated**: July 2025

This Car Rental ERP system represents a complete, enterprise-grade solution ready for immediate deployment and business use.

