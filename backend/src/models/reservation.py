from src.models.user import db
from datetime import datetime
import uuid

class Reservation(db.Model):
    __tablename__ = 'reservations'
    
    reservation_id = db.Column(db.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    reservation_number = db.Column(db.String(20), unique=True, nullable=False)
    customer_id = db.Column(db.String(36), db.ForeignKey('customers.customer_id'), nullable=False)
    vehicle_category_id = db.Column(db.String(36), db.ForeignKey('vehicle_categories.category_id'), nullable=False)
    assigned_vehicle_id = db.Column(db.String(36), db.ForeignKey('vehicles.vehicle_id'))
    pickup_location_id = db.Column(db.String(36), db.ForeignKey('locations.location_id'), nullable=False)
    return_location_id = db.Column(db.String(36), db.ForeignKey('locations.location_id'), nullable=False)
    pickup_datetime = db.Column(db.DateTime, nullable=False)
    return_datetime = db.Column(db.DateTime, nullable=False)
    status = db.Column(db.String(20), nullable=False, default='pending')
    total_estimated_cost = db.Column(db.Numeric(10, 2))
    total_actual_cost = db.Column(db.Numeric(10, 2))
    deposit_amount = db.Column(db.Numeric(8, 2))
    special_requests = db.Column(db.Text)
    cancellation_reason = db.Column(db.Text)
    created_by = db.Column(db.String(36), db.ForeignKey('users.user_id'))
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    rental_agreement = db.relationship('RentalAgreement', backref='reservation', uselist=False)
    payments = db.relationship('Payment', backref='reservation', lazy=True)
    invoices = db.relationship('Invoice', backref='reservation', lazy=True)
    creator = db.relationship('User', backref='created_reservations')
    
    def __repr__(self):
        return f'<Reservation {self.reservation_number}>'
    
    def calculate_rental_duration_hours(self):
        """Calculate rental duration in hours"""
        if self.pickup_datetime and self.return_datetime:
            duration = self.return_datetime - self.pickup_datetime
            return duration.total_seconds() / 3600
        return 0
    
    def calculate_rental_duration_days(self):
        """Calculate rental duration in days"""
        hours = self.calculate_rental_duration_hours()
        return max(1, int(hours / 24))  # Minimum 1 day
    
    def to_dict(self):
        return {
            'reservation_id': self.reservation_id,
            'reservation_number': self.reservation_number,
            'customer_id': self.customer_id,
            'vehicle_category_id': self.vehicle_category_id,
            'assigned_vehicle_id': self.assigned_vehicle_id,
            'pickup_location_id': self.pickup_location_id,
            'return_location_id': self.return_location_id,
            'pickup_datetime': self.pickup_datetime.isoformat() if self.pickup_datetime else None,
            'return_datetime': self.return_datetime.isoformat() if self.return_datetime else None,
            'status': self.status,
            'total_estimated_cost': float(self.total_estimated_cost) if self.total_estimated_cost else None,
            'total_actual_cost': float(self.total_actual_cost) if self.total_actual_cost else None,
            'deposit_amount': float(self.deposit_amount) if self.deposit_amount else None,
            'special_requests': self.special_requests,
            'cancellation_reason': self.cancellation_reason,
            'created_by': self.created_by,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None,
            'rental_duration_hours': self.calculate_rental_duration_hours(),
            'rental_duration_days': self.calculate_rental_duration_days()
        }

class RentalAgreement(db.Model):
    __tablename__ = 'rental_agreements'
    
    agreement_id = db.Column(db.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    reservation_id = db.Column(db.String(36), db.ForeignKey('reservations.reservation_id'), nullable=False)
    agreement_number = db.Column(db.String(20), unique=True, nullable=False)
    customer_signature = db.Column(db.LargeBinary)  # Store signature as binary data
    employee_signature = db.Column(db.LargeBinary)
    signed_at = db.Column(db.DateTime)
    terms_and_conditions = db.Column(db.Text, nullable=False)
    pickup_mileage = db.Column(db.Integer)
    return_mileage = db.Column(db.Integer)
    fuel_level_pickup = db.Column(db.Numeric(3, 2))
    fuel_level_return = db.Column(db.Numeric(3, 2))
    pickup_condition_notes = db.Column(db.Text)
    return_condition_notes = db.Column(db.Text)
    additional_charges = db.Column(db.Numeric(8, 2), default=0.00)
    damage_charges = db.Column(db.Numeric(8, 2), default=0.00)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    def __repr__(self):
        return f'<RentalAgreement {self.agreement_number}>'
    
    def calculate_mileage_driven(self):
        """Calculate total mileage driven during rental"""
        if self.pickup_mileage and self.return_mileage:
            return self.return_mileage - self.pickup_mileage
        return 0
    
    def to_dict(self):
        return {
            'agreement_id': self.agreement_id,
            'reservation_id': self.reservation_id,
            'agreement_number': self.agreement_number,
            'signed_at': self.signed_at.isoformat() if self.signed_at else None,
            'terms_and_conditions': self.terms_and_conditions,
            'pickup_mileage': self.pickup_mileage,
            'return_mileage': self.return_mileage,
            'fuel_level_pickup': float(self.fuel_level_pickup) if self.fuel_level_pickup else None,
            'fuel_level_return': float(self.fuel_level_return) if self.fuel_level_return else None,
            'pickup_condition_notes': self.pickup_condition_notes,
            'return_condition_notes': self.return_condition_notes,
            'additional_charges': float(self.additional_charges) if self.additional_charges else 0.00,
            'damage_charges': float(self.damage_charges) if self.damage_charges else 0.00,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None,
            'mileage_driven': self.calculate_mileage_driven()
        }

