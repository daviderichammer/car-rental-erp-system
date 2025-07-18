from src.models.user import db
from datetime import datetime, date
import uuid
import json

class Payment(db.Model):
    __tablename__ = 'payments'
    
    payment_id = db.Column(db.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    reservation_id = db.Column(db.String(36), db.ForeignKey('reservations.reservation_id'))
    customer_id = db.Column(db.String(36), db.ForeignKey('customers.customer_id'), nullable=False)
    payment_type = db.Column(db.String(20), nullable=False)
    payment_method = db.Column(db.String(20), nullable=False)
    amount = db.Column(db.Numeric(10, 2), nullable=False)
    currency = db.Column(db.String(3), nullable=False, default='USD')
    transaction_id = db.Column(db.String(100))
    gateway_response = db.Column(db.Text)  # JSON string for gateway response
    status = db.Column(db.String(20), nullable=False, default='pending')
    processed_at = db.Column(db.DateTime)
    refunded_at = db.Column(db.DateTime)
    refund_amount = db.Column(db.Numeric(10, 2))
    notes = db.Column(db.Text)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    def __repr__(self):
        return f'<Payment {self.payment_id} - {self.amount} {self.currency}>'
    
    def get_gateway_response(self):
        """Parse gateway response JSON string"""
        if self.gateway_response:
            try:
                return json.loads(self.gateway_response)
            except json.JSONDecodeError:
                return {}
        return {}
    
    def set_gateway_response(self, response_dict):
        """Set gateway response as JSON string"""
        self.gateway_response = json.dumps(response_dict)
    
    def to_dict(self):
        return {
            'payment_id': self.payment_id,
            'reservation_id': self.reservation_id,
            'customer_id': self.customer_id,
            'payment_type': self.payment_type,
            'payment_method': self.payment_method,
            'amount': float(self.amount) if self.amount else 0.00,
            'currency': self.currency,
            'transaction_id': self.transaction_id,
            'gateway_response': self.get_gateway_response(),
            'status': self.status,
            'processed_at': self.processed_at.isoformat() if self.processed_at else None,
            'refunded_at': self.refunded_at.isoformat() if self.refunded_at else None,
            'refund_amount': float(self.refund_amount) if self.refund_amount else None,
            'notes': self.notes,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None
        }

class Invoice(db.Model):
    __tablename__ = 'invoices'
    
    invoice_id = db.Column(db.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    invoice_number = db.Column(db.String(20), unique=True, nullable=False)
    reservation_id = db.Column(db.String(36), db.ForeignKey('reservations.reservation_id'), nullable=False)
    customer_id = db.Column(db.String(36), db.ForeignKey('customers.customer_id'), nullable=False)
    invoice_date = db.Column(db.Date, nullable=False, default=date.today)
    due_date = db.Column(db.Date, nullable=False)
    subtotal = db.Column(db.Numeric(10, 2), nullable=False)
    tax_amount = db.Column(db.Numeric(8, 2), nullable=False, default=0.00)
    total_amount = db.Column(db.Numeric(10, 2), nullable=False)
    paid_amount = db.Column(db.Numeric(10, 2), default=0.00)
    status = db.Column(db.String(20), nullable=False, default='draft')
    billing_address = db.Column(db.Text)  # JSON string for billing address
    line_items = db.Column(db.Text, nullable=False)  # JSON string for line items
    payment_terms = db.Column(db.Text)
    notes = db.Column(db.Text)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    def __repr__(self):
        return f'<Invoice {self.invoice_number}>'
    
    def get_billing_address(self):
        """Parse billing address JSON string"""
        if self.billing_address:
            try:
                return json.loads(self.billing_address)
            except json.JSONDecodeError:
                return {}
        return {}
    
    def set_billing_address(self, address_dict):
        """Set billing address as JSON string"""
        self.billing_address = json.dumps(address_dict)
    
    def get_line_items(self):
        """Parse line items JSON string"""
        if self.line_items:
            try:
                return json.loads(self.line_items)
            except json.JSONDecodeError:
                return []
        return []
    
    def set_line_items(self, items_list):
        """Set line items as JSON string"""
        self.line_items = json.dumps(items_list)
    
    def calculate_balance_due(self):
        """Calculate remaining balance due"""
        total = float(self.total_amount) if self.total_amount else 0.00
        paid = float(self.paid_amount) if self.paid_amount else 0.00
        return total - paid
    
    def to_dict(self):
        return {
            'invoice_id': self.invoice_id,
            'invoice_number': self.invoice_number,
            'reservation_id': self.reservation_id,
            'customer_id': self.customer_id,
            'invoice_date': self.invoice_date.isoformat() if self.invoice_date else None,
            'due_date': self.due_date.isoformat() if self.due_date else None,
            'subtotal': float(self.subtotal) if self.subtotal else 0.00,
            'tax_amount': float(self.tax_amount) if self.tax_amount else 0.00,
            'total_amount': float(self.total_amount) if self.total_amount else 0.00,
            'paid_amount': float(self.paid_amount) if self.paid_amount else 0.00,
            'balance_due': self.calculate_balance_due(),
            'status': self.status,
            'billing_address': self.get_billing_address(),
            'line_items': self.get_line_items(),
            'payment_terms': self.payment_terms,
            'notes': self.notes,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None
        }

class PricingRule(db.Model):
    __tablename__ = 'pricing_rules'
    
    rule_id = db.Column(db.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    rule_name = db.Column(db.String(100), nullable=False)
    rule_type = db.Column(db.String(20), nullable=False)
    category_id = db.Column(db.String(36), db.ForeignKey('vehicle_categories.category_id'))
    location_id = db.Column(db.String(36), db.ForeignKey('locations.location_id'))
    start_date = db.Column(db.Date)
    end_date = db.Column(db.Date)
    day_of_week = db.Column(db.Integer)  # 0-6 for Sunday-Saturday
    time_of_day = db.Column(db.Time)
    multiplier = db.Column(db.Numeric(4, 3), default=1.000)
    fixed_adjustment = db.Column(db.Numeric(8, 2), default=0.00)
    minimum_rental_days = db.Column(db.Integer)
    maximum_rental_days = db.Column(db.Integer)
    priority = db.Column(db.Integer, default=0)
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    category = db.relationship('VehicleCategory', backref='pricing_rules')
    location = db.relationship('Location', backref='pricing_rules')
    
    def __repr__(self):
        return f'<PricingRule {self.rule_name}>'
    
    def to_dict(self):
        return {
            'rule_id': self.rule_id,
            'rule_name': self.rule_name,
            'rule_type': self.rule_type,
            'category_id': self.category_id,
            'location_id': self.location_id,
            'start_date': self.start_date.isoformat() if self.start_date else None,
            'end_date': self.end_date.isoformat() if self.end_date else None,
            'day_of_week': self.day_of_week,
            'time_of_day': self.time_of_day.isoformat() if self.time_of_day else None,
            'multiplier': float(self.multiplier) if self.multiplier else 1.000,
            'fixed_adjustment': float(self.fixed_adjustment) if self.fixed_adjustment else 0.00,
            'minimum_rental_days': self.minimum_rental_days,
            'maximum_rental_days': self.maximum_rental_days,
            'priority': self.priority,
            'is_active': self.is_active,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None
        }

