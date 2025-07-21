from src.models.user import db
from datetime import datetime
import uuid
import json

class Location(db.Model):
    __tablename__ = 'locations'
    
    location_id = db.Column(db.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    location_code = db.Column(db.String(10), unique=True, nullable=False)
    location_name = db.Column(db.String(100), nullable=False)
    location_type = db.Column(db.String(20), nullable=False)
    street_address = db.Column(db.String(255), nullable=False)
    city = db.Column(db.String(100), nullable=False)
    state_province = db.Column(db.String(100))
    postal_code = db.Column(db.String(20))
    country = db.Column(db.String(3), nullable=False)
    latitude = db.Column(db.Numeric(10, 8))
    longitude = db.Column(db.Numeric(11, 8))
    phone_number = db.Column(db.String(20))
    operating_hours = db.Column(db.Text)  # JSON string for operating hours
    capacity = db.Column(db.Integer, default=0)
    is_pickup_location = db.Column(db.Boolean, default=True)
    is_return_location = db.Column(db.Boolean, default=True)
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    vehicles = db.relationship('Vehicle', backref='current_location', lazy=True)
    pickup_reservations = db.relationship('Reservation', foreign_keys='Reservation.pickup_location_id', backref='pickup_location', lazy=True)
    return_reservations = db.relationship('Reservation', foreign_keys='Reservation.return_location_id', backref='return_location', lazy=True)
    
    def __repr__(self):
        return f'<Location {self.location_code} - {self.location_name}>'
    
    def get_operating_hours(self):
        """Parse operating hours JSON string"""
        if self.operating_hours:
            try:
                return json.loads(self.operating_hours)
            except json.JSONDecodeError:
                return {}
        return {}
    
    def set_operating_hours(self, hours_dict):
        """Set operating hours as JSON string"""
        self.operating_hours = json.dumps(hours_dict)
    
    def to_dict(self):
        return {
            'location_id': self.location_id,
            'location_code': self.location_code,
            'location_name': self.location_name,
            'location_type': self.location_type,
            'street_address': self.street_address,
            'city': self.city,
            'state_province': self.state_province,
            'postal_code': self.postal_code,
            'country': self.country,
            'latitude': float(self.latitude) if self.latitude else None,
            'longitude': float(self.longitude) if self.longitude else None,
            'phone_number': self.phone_number,
            'operating_hours': self.get_operating_hours(),
            'capacity': self.capacity,
            'is_pickup_location': self.is_pickup_location,
            'is_return_location': self.is_return_location,
            'is_active': self.is_active,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None
        }

