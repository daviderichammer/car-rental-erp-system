from src.models.user import db
from datetime import datetime, date
import uuid

class Customer(db.Model):
    __tablename__ = 'customers'
    
    customer_id = db.Column(db.String(36), db.ForeignKey('users.user_id'), primary_key=True)
    customer_number = db.Column(db.String(20), unique=True, nullable=False)
    driver_license_number = db.Column(db.String(50))
    driver_license_state = db.Column(db.String(10))
    driver_license_country = db.Column(db.String(3))
    driver_license_expiry = db.Column(db.Date)
    credit_score = db.Column(db.Integer)
    preferred_language = db.Column(db.String(10), default='en')
    marketing_opt_in = db.Column(db.Boolean, default=False)
    loyalty_program_member = db.Column(db.Boolean, default=False)
    loyalty_points = db.Column(db.Integer, default=0)
    customer_since = db.Column(db.Date, default=date.today)
    total_rentals = db.Column(db.Integer, default=0)
    total_spent = db.Column(db.Numeric(10, 2), default=0.00)
    risk_level = db.Column(db.String(20), default='low')
    notes = db.Column(db.Text)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    addresses = db.relationship('CustomerAddress', backref='customer', lazy=True)
    reservations = db.relationship('Reservation', backref='customer', lazy=True)
    payments = db.relationship('Payment', backref='customer', lazy=True)
    invoices = db.relationship('Invoice', backref='customer', lazy=True)
    
    def __repr__(self):
        return f'<Customer {self.customer_number}>'
    
    def to_dict(self):
        return {
            'customer_id': self.customer_id,
            'customer_number': self.customer_number,
            'driver_license_number': self.driver_license_number,
            'driver_license_state': self.driver_license_state,
            'driver_license_country': self.driver_license_country,
            'driver_license_expiry': self.driver_license_expiry.isoformat() if self.driver_license_expiry else None,
            'credit_score': self.credit_score,
            'preferred_language': self.preferred_language,
            'marketing_opt_in': self.marketing_opt_in,
            'loyalty_program_member': self.loyalty_program_member,
            'loyalty_points': self.loyalty_points,
            'customer_since': self.customer_since.isoformat() if self.customer_since else None,
            'total_rentals': self.total_rentals,
            'total_spent': float(self.total_spent) if self.total_spent else 0.00,
            'risk_level': self.risk_level,
            'notes': self.notes,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None
        }

class CustomerAddress(db.Model):
    __tablename__ = 'customer_addresses'
    
    address_id = db.Column(db.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    customer_id = db.Column(db.String(36), db.ForeignKey('customers.customer_id'), nullable=False)
    address_type = db.Column(db.String(20), nullable=False)
    street_address_1 = db.Column(db.String(255), nullable=False)
    street_address_2 = db.Column(db.String(255))
    city = db.Column(db.String(100), nullable=False)
    state_province = db.Column(db.String(100))
    postal_code = db.Column(db.String(20))
    country = db.Column(db.String(3), nullable=False)
    is_primary = db.Column(db.Boolean, default=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    def __repr__(self):
        return f'<CustomerAddress {self.address_type} for {self.customer_id}>'
    
    def to_dict(self):
        return {
            'address_id': self.address_id,
            'customer_id': self.customer_id,
            'address_type': self.address_type,
            'street_address_1': self.street_address_1,
            'street_address_2': self.street_address_2,
            'city': self.city,
            'state_province': self.state_province,
            'postal_code': self.postal_code,
            'country': self.country,
            'is_primary': self.is_primary,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None
        }

