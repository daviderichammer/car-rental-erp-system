from src.models.user import db
from datetime import datetime, date
import uuid
import json

class MaintenanceSchedule(db.Model):
    __tablename__ = 'maintenance_schedules'
    
    schedule_id = db.Column(db.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    vehicle_id = db.Column(db.String(36), db.ForeignKey('vehicles.vehicle_id'), nullable=False)
    service_type = db.Column(db.String(50), nullable=False)
    scheduled_date = db.Column(db.Date, nullable=False)
    scheduled_mileage = db.Column(db.Integer)
    vendor_id = db.Column(db.String(36))  # Reference to vendor (not implemented in this phase)
    estimated_cost = db.Column(db.Numeric(8, 2))
    actual_cost = db.Column(db.Numeric(8, 2))
    status = db.Column(db.String(20), nullable=False, default='scheduled')
    completion_date = db.Column(db.Date)
    completion_mileage = db.Column(db.Integer)
    service_notes = db.Column(db.Text)
    next_service_date = db.Column(db.Date)
    next_service_mileage = db.Column(db.Integer)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    def __repr__(self):
        return f'<MaintenanceSchedule {self.service_type} for {self.vehicle_id}>'
    
    def is_overdue(self):
        """Check if maintenance is overdue"""
        if self.status in ['completed', 'cancelled']:
            return False
        
        today = date.today()
        if self.scheduled_date and self.scheduled_date < today:
            return True
        
        # Check mileage-based overdue (would need current vehicle mileage)
        return False
    
    def calculate_cost_variance(self):
        """Calculate variance between estimated and actual cost"""
        if self.estimated_cost and self.actual_cost:
            return float(self.actual_cost) - float(self.estimated_cost)
        return None
    
    def to_dict(self):
        return {
            'schedule_id': self.schedule_id,
            'vehicle_id': self.vehicle_id,
            'service_type': self.service_type,
            'scheduled_date': self.scheduled_date.isoformat() if self.scheduled_date else None,
            'scheduled_mileage': self.scheduled_mileage,
            'vendor_id': self.vendor_id,
            'estimated_cost': float(self.estimated_cost) if self.estimated_cost else None,
            'actual_cost': float(self.actual_cost) if self.actual_cost else None,
            'cost_variance': self.calculate_cost_variance(),
            'status': self.status,
            'completion_date': self.completion_date.isoformat() if self.completion_date else None,
            'completion_mileage': self.completion_mileage,
            'service_notes': self.service_notes,
            'next_service_date': self.next_service_date.isoformat() if self.next_service_date else None,
            'next_service_mileage': self.next_service_mileage,
            'is_overdue': self.is_overdue(),
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None
        }

class DamageReport(db.Model):
    __tablename__ = 'damage_reports'
    
    report_id = db.Column(db.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    vehicle_id = db.Column(db.String(36), db.ForeignKey('vehicles.vehicle_id'), nullable=False)
    reservation_id = db.Column(db.String(36), db.ForeignKey('reservations.reservation_id'))
    reported_by = db.Column(db.String(36), db.ForeignKey('users.user_id'), nullable=False)
    incident_date = db.Column(db.DateTime, nullable=False)
    damage_type = db.Column(db.String(30), nullable=False)
    damage_severity = db.Column(db.String(20), nullable=False)
    damage_description = db.Column(db.Text, nullable=False)
    estimated_repair_cost = db.Column(db.Numeric(8, 2))
    actual_repair_cost = db.Column(db.Numeric(8, 2))
    insurance_claim_number = db.Column(db.String(50))
    photos = db.Column(db.Text)  # JSON string for photo URLs/paths
    status = db.Column(db.String(20), nullable=False, default='reported')
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    reporter = db.relationship('User', backref='damage_reports')
    reservation = db.relationship('Reservation', backref='damage_reports')
    
    def __repr__(self):
        return f'<DamageReport {self.report_id} - {self.damage_type}>'
    
    def get_photos(self):
        """Parse photos JSON string"""
        if self.photos:
            try:
                return json.loads(self.photos)
            except json.JSONDecodeError:
                return []
        return []
    
    def set_photos(self, photos_list):
        """Set photos as JSON string"""
        self.photos = json.dumps(photos_list)
    
    def add_photo(self, photo_url):
        """Add a photo to the damage report"""
        photos = self.get_photos()
        photos.append(photo_url)
        self.set_photos(photos)
    
    def calculate_cost_variance(self):
        """Calculate variance between estimated and actual repair cost"""
        if self.estimated_repair_cost and self.actual_repair_cost:
            return float(self.actual_repair_cost) - float(self.estimated_repair_cost)
        return None
    
    def to_dict(self):
        return {
            'report_id': self.report_id,
            'vehicle_id': self.vehicle_id,
            'reservation_id': self.reservation_id,
            'reported_by': self.reported_by,
            'incident_date': self.incident_date.isoformat() if self.incident_date else None,
            'damage_type': self.damage_type,
            'damage_severity': self.damage_severity,
            'damage_description': self.damage_description,
            'estimated_repair_cost': float(self.estimated_repair_cost) if self.estimated_repair_cost else None,
            'actual_repair_cost': float(self.actual_repair_cost) if self.actual_repair_cost else None,
            'cost_variance': self.calculate_cost_variance(),
            'insurance_claim_number': self.insurance_claim_number,
            'photos': self.get_photos(),
            'status': self.status,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None
        }

