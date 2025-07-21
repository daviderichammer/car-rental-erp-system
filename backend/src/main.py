import os
import sys
# DON'T CHANGE THIS !!!
sys.path.insert(0, os.path.dirname(os.path.dirname(__file__)))

from flask import Flask, send_from_directory
from flask_cors import CORS

# Import all models
from src.models.user import db, User, UserRole, UserRoleAssignment, Permission, RolePermission
from src.models.customer import Customer, CustomerAddress
from src.models.vehicle import VehicleCategory, Vehicle, VehicleFeature, VehicleFeatureAssignment
from src.models.location import Location
from src.models.reservation import Reservation, RentalAgreement
from src.models.financial import Payment, Invoice, PricingRule
from src.models.maintenance import MaintenanceSchedule, DamageReport

# Import routes
from src.routes.user import user_bp
from src.routes.auth import auth_bp
from src.routes.customer import customer_bp
from src.routes.vehicle import vehicle_bp
from src.routes.location import location_bp
from src.routes.reservation import reservation_bp
from src.routes.financial import financial_bp
from src.routes.maintenance import maintenance_bp

app = Flask(__name__, static_folder=os.path.join(os.path.dirname(__file__), 'static'))
app.config['SECRET_KEY'] = 'car_rental_erp_secret_key_2025'

# Enable CORS for all routes
CORS(app, origins="*")

# Register blueprints
app.register_blueprint(auth_bp, url_prefix='/api/auth')
app.register_blueprint(user_bp, url_prefix='/api/users')
app.register_blueprint(customer_bp, url_prefix='/api/customers')
app.register_blueprint(vehicle_bp, url_prefix='/api/vehicles')
app.register_blueprint(location_bp, url_prefix='/api/locations')
app.register_blueprint(reservation_bp, url_prefix='/api/reservations')
app.register_blueprint(financial_bp, url_prefix='/api/financial')
app.register_blueprint(maintenance_bp, url_prefix='/api/maintenance')

# Database configuration
app.config['SQLALCHEMY_DATABASE_URI'] = f"sqlite:///{os.path.join(os.path.dirname(__file__), 'database', 'app.db')}"
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
db.init_app(app)

# Create all database tables
with app.app_context():
    db.create_all()
    
    # Create default admin user if it doesn't exist
    admin_user = User.query.filter_by(email='admin@carrental.com').first()
    if not admin_user:
        admin_user = User(
            email='admin@carrental.com',
            first_name='System',
            last_name='Administrator',
            user_type='admin',
            status='active',
            email_verified=True
        )
        admin_user.set_password('admin123')
        db.session.add(admin_user)
        
        # Create default roles
        admin_role = UserRole(
            role_name='System Administrator',
            role_description='Full system access and administration',
            is_system_role=True
        )
        manager_role = UserRole(
            role_name='Business Manager',
            role_description='Business operations and reporting access',
            is_system_role=True
        )
        agent_role = UserRole(
            role_name='Rental Agent',
            role_description='Customer service and rental operations',
            is_system_role=True
        )
        
        db.session.add_all([admin_role, manager_role, agent_role])
        db.session.commit()
        
        # Assign admin role to admin user
        admin_assignment = UserRoleAssignment(
            user_id=admin_user.user_id,
            role_id=admin_role.role_id,
            assigned_by=admin_user.user_id
        )
        db.session.add(admin_assignment)
        
        # Create sample vehicle categories
        economy_category = VehicleCategory(
            category_name='Economy',
            category_code='ECON',
            description='Compact and fuel-efficient vehicles',
            base_daily_rate=29.99,
            base_hourly_rate=4.99,
            deposit_amount=200.00,
            passenger_capacity=4,
            luggage_capacity=2,
            transmission_type='automatic',
            fuel_type='gasoline'
        )
        
        suv_category = VehicleCategory(
            category_name='SUV',
            category_code='SUV',
            description='Sport Utility Vehicles for families and groups',
            base_daily_rate=59.99,
            base_hourly_rate=9.99,
            deposit_amount=400.00,
            passenger_capacity=7,
            luggage_capacity=5,
            transmission_type='automatic',
            fuel_type='gasoline'
        )
        
        db.session.add_all([economy_category, suv_category])
        
        # Create sample location
        main_location = Location(
            location_code='MAIN',
            location_name='Main Office',
            location_type='downtown',
            street_address='123 Main Street',
            city='Downtown',
            state_province='CA',
            postal_code='90210',
            country='USA',
            phone_number='(555) 123-4567',
            capacity=50
        )
        main_location.set_operating_hours({
            'monday': {'open': '08:00', 'close': '18:00'},
            'tuesday': {'open': '08:00', 'close': '18:00'},
            'wednesday': {'open': '08:00', 'close': '18:00'},
            'thursday': {'open': '08:00', 'close': '18:00'},
            'friday': {'open': '08:00', 'close': '18:00'},
            'saturday': {'open': '09:00', 'close': '17:00'},
            'sunday': {'open': '10:00', 'close': '16:00'}
        })
        
        db.session.add(main_location)
        db.session.commit()
        
        print("Database initialized with sample data")

@app.route('/', defaults={'path': ''})
@app.route('/<path:path>')
def serve(path):
    static_folder_path = app.static_folder
    if static_folder_path is None:
        return "Static folder not configured", 404

    if path != "" and os.path.exists(os.path.join(static_folder_path, path)):
        return send_from_directory(static_folder_path, path)
    else:
        index_path = os.path.join(static_folder_path, 'index.html')
        if os.path.exists(index_path):
            return send_from_directory(static_folder_path, 'index.html')
        else:
            return "Car Rental ERP API Server - Backend is running!", 200

@app.route('/api/health')
def health_check():
    """Health check endpoint"""
    return {
        'status': 'healthy',
        'service': 'Car Rental ERP API',
        'version': '1.0.0'
    }

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5001, debug=True)

