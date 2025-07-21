from src.models.user import db
from datetime import datetime, date
import uuid

class VehicleCategory(db.Model):
    __tablename__ = 'vehicle_categories'
    
    category_id = db.Column(db.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    category_name = db.Column(db.String(50), unique=True, nullable=False)
    category_code = db.Column(db.String(10), unique=True, nullable=False)
    description = db.Column(db.Text)
    base_daily_rate = db.Column(db.Numeric(8, 2), nullable=False)
    base_hourly_rate = db.Column(db.Numeric(8, 2))
    mileage_rate = db.Column(db.Numeric(6, 4))
    deposit_amount = db.Column(db.Numeric(8, 2), nullable=False)
    passenger_capacity = db.Column(db.Integer, nullable=False)
    luggage_capacity = db.Column(db.Integer)
    transmission_type = db.Column(db.String(20))
    fuel_type = db.Column(db.String(20))
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    vehicles = db.relationship('Vehicle', backref='category', lazy=True)
    reservations = db.relationship('Reservation', backref='vehicle_category', lazy=True)
    
    def __repr__(self):
        return f'<VehicleCategory {self.category_name}>'
    
    def to_dict(self):
        return {
            'category_id': self.category_id,
            'category_name': self.category_name,
            'category_code': self.category_code,
            'description': self.description,
            'base_daily_rate': float(self.base_daily_rate) if self.base_daily_rate else 0.00,
            'base_hourly_rate': float(self.base_hourly_rate) if self.base_hourly_rate else None,
            'mileage_rate': float(self.mileage_rate) if self.mileage_rate else None,
            'deposit_amount': float(self.deposit_amount) if self.deposit_amount else 0.00,
            'passenger_capacity': self.passenger_capacity,
            'luggage_capacity': self.luggage_capacity,
            'transmission_type': self.transmission_type,
            'fuel_type': self.fuel_type,
            'is_active': self.is_active,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None
        }

class Vehicle(db.Model):
    __tablename__ = 'vehicles'
    
    vehicle_id = db.Column(db.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    vehicle_number = db.Column(db.String(20), unique=True, nullable=False)
    license_plate = db.Column(db.String(20), unique=True, nullable=False)
    vin = db.Column(db.String(17), unique=True, nullable=False)
    category_id = db.Column(db.String(36), db.ForeignKey('vehicle_categories.category_id'), nullable=False)
    make = db.Column(db.String(50), nullable=False)
    model = db.Column(db.String(50), nullable=False)
    year = db.Column(db.Integer, nullable=False)
    color = db.Column(db.String(30))
    fuel_capacity = db.Column(db.Numeric(6, 2))
    current_mileage = db.Column(db.Integer, default=0)
    purchase_date = db.Column(db.Date)
    purchase_price = db.Column(db.Numeric(10, 2))
    current_location_id = db.Column(db.String(36), db.ForeignKey('locations.location_id'))
    status = db.Column(db.String(20), nullable=False, default='available')
    condition_rating = db.Column(db.Integer)
    last_service_date = db.Column(db.Date)
    next_service_due_mileage = db.Column(db.Integer)
    insurance_policy_number = db.Column(db.String(50))
    insurance_expiry = db.Column(db.Date)
    registration_expiry = db.Column(db.Date)
    gps_device_id = db.Column(db.String(50))
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    reservations = db.relationship('Reservation', backref='assigned_vehicle', lazy=True)
    maintenance_schedules = db.relationship('MaintenanceSchedule', backref='vehicle', lazy=True)
    damage_reports = db.relationship('DamageReport', backref='vehicle', lazy=True)
    feature_assignments = db.relationship('VehicleFeatureAssignment', backref='vehicle', lazy=True)
    
    def __repr__(self):
        return f'<Vehicle {self.vehicle_number}>'
    
    def to_dict(self):
        return {
            'vehicle_id': self.vehicle_id,
            'vehicle_number': self.vehicle_number,
            'license_plate': self.license_plate,
            'vin': self.vin,
            'category_id': self.category_id,
            'make': self.make,
            'model': self.model,
            'year': self.year,
            'color': self.color,
            'fuel_capacity': float(self.fuel_capacity) if self.fuel_capacity else None,
            'current_mileage': self.current_mileage,
            'purchase_date': self.purchase_date.isoformat() if self.purchase_date else None,
            'purchase_price': float(self.purchase_price) if self.purchase_price else None,
            'current_location_id': self.current_location_id,
            'status': self.status,
            'condition_rating': self.condition_rating,
            'last_service_date': self.last_service_date.isoformat() if self.last_service_date else None,
            'next_service_due_mileage': self.next_service_due_mileage,
            'insurance_policy_number': self.insurance_policy_number,
            'insurance_expiry': self.insurance_expiry.isoformat() if self.insurance_expiry else None,
            'registration_expiry': self.registration_expiry.isoformat() if self.registration_expiry else None,
            'gps_device_id': self.gps_device_id,
            'is_active': self.is_active,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None
        }

class VehicleFeature(db.Model):
    __tablename__ = 'vehicle_features'
    
    feature_id = db.Column(db.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    feature_name = db.Column(db.String(50), unique=True, nullable=False)
    feature_description = db.Column(db.Text)
    feature_category = db.Column(db.String(30))
    additional_daily_rate = db.Column(db.Numeric(6, 2), default=0.00)
    is_active = db.Column(db.Boolean, default=True)
    
    # Relationships
    assignments = db.relationship('VehicleFeatureAssignment', backref='feature', lazy=True)
    
    def __repr__(self):
        return f'<VehicleFeature {self.feature_name}>'
    
    def to_dict(self):
        return {
            'feature_id': self.feature_id,
            'feature_name': self.feature_name,
            'feature_description': self.feature_description,
            'feature_category': self.feature_category,
            'additional_daily_rate': float(self.additional_daily_rate) if self.additional_daily_rate else 0.00,
            'is_active': self.is_active
        }

class VehicleFeatureAssignment(db.Model):
    __tablename__ = 'vehicle_feature_assignments'
    
    vehicle_id = db.Column(db.String(36), db.ForeignKey('vehicles.vehicle_id'), primary_key=True)
    feature_id = db.Column(db.String(36), db.ForeignKey('vehicle_features.feature_id'), primary_key=True)
    assigned_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def __repr__(self):
        return f'<VehicleFeatureAssignment {self.vehicle_id} -> {self.feature_id}>'
    
    def to_dict(self):
        return {
            'vehicle_id': self.vehicle_id,
            'feature_id': self.feature_id,
            'assigned_at': self.assigned_at.isoformat() if self.assigned_at else None
        }

