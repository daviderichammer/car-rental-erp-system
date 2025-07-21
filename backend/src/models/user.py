from flask_sqlalchemy import SQLAlchemy
from datetime import datetime
from werkzeug.security import generate_password_hash, check_password_hash
import uuid

db = SQLAlchemy()

class User(db.Model):
    __tablename__ = 'users'
    
    user_id = db.Column(db.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    email = db.Column(db.String(255), unique=True, nullable=False)
    password_hash = db.Column(db.String(255), nullable=False)
    first_name = db.Column(db.String(100), nullable=False)
    last_name = db.Column(db.String(100), nullable=False)
    phone_number = db.Column(db.String(20))
    date_of_birth = db.Column(db.Date)
    user_type = db.Column(db.String(20), nullable=False, default='customer')
    status = db.Column(db.String(20), nullable=False, default='active')
    email_verified = db.Column(db.Boolean, default=False)
    phone_verified = db.Column(db.Boolean, default=False)
    two_factor_enabled = db.Column(db.Boolean, default=False)
    last_login_at = db.Column(db.DateTime)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    created_by = db.Column(db.String(36), db.ForeignKey('users.user_id'))
    updated_by = db.Column(db.String(36), db.ForeignKey('users.user_id'))
    
    # Relationships
    customer = db.relationship('Customer', backref='user', uselist=False)
    
    def set_password(self, password):
        """Set password hash"""
        self.password_hash = generate_password_hash(password)
    
    def check_password(self, password):
        """Check password against hash"""
        return check_password_hash(self.password_hash, password)
    
    def __repr__(self):
        return f'<User {self.email}>'
    
    def to_dict(self):
        return {
            'user_id': self.user_id,
            'email': self.email,
            'first_name': self.first_name,
            'last_name': self.last_name,
            'phone_number': self.phone_number,
            'date_of_birth': self.date_of_birth.isoformat() if self.date_of_birth else None,
            'user_type': self.user_type,
            'status': self.status,
            'email_verified': self.email_verified,
            'phone_verified': self.phone_verified,
            'two_factor_enabled': self.two_factor_enabled,
            'last_login_at': self.last_login_at.isoformat() if self.last_login_at else None,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None
        }

class UserRole(db.Model):
    __tablename__ = 'user_roles'
    
    role_id = db.Column(db.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    role_name = db.Column(db.String(50), unique=True, nullable=False)
    role_description = db.Column(db.Text)
    parent_role_id = db.Column(db.String(36), db.ForeignKey('user_roles.role_id'))
    is_system_role = db.Column(db.Boolean, default=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Self-referential relationship for role hierarchy
    children = db.relationship('UserRole', backref=db.backref('parent', remote_side='UserRole.role_id'))
    
    def __repr__(self):
        return f'<UserRole {self.role_name}>'
    
    def to_dict(self):
        return {
            'role_id': self.role_id,
            'role_name': self.role_name,
            'role_description': self.role_description,
            'parent_role_id': self.parent_role_id,
            'is_system_role': self.is_system_role,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None
        }

class UserRoleAssignment(db.Model):
    __tablename__ = 'user_role_assignments'
    
    assignment_id = db.Column(db.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    user_id = db.Column(db.String(36), db.ForeignKey('users.user_id'), nullable=False)
    role_id = db.Column(db.String(36), db.ForeignKey('user_roles.role_id'), nullable=False)
    assigned_by = db.Column(db.String(36), db.ForeignKey('users.user_id'), nullable=False)
    assigned_at = db.Column(db.DateTime, default=datetime.utcnow)
    expires_at = db.Column(db.DateTime)
    is_active = db.Column(db.Boolean, default=True)
    
    # Relationships
    user = db.relationship('User', foreign_keys=[user_id], backref='role_assignments')
    role = db.relationship('UserRole', backref='assignments')
    assigner = db.relationship('User', foreign_keys=[assigned_by])
    
    __table_args__ = (db.UniqueConstraint('user_id', 'role_id', name='unique_user_role'),)
    
    def __repr__(self):
        return f'<UserRoleAssignment {self.user_id} -> {self.role_id}>'
    
    def to_dict(self):
        return {
            'assignment_id': self.assignment_id,
            'user_id': self.user_id,
            'role_id': self.role_id,
            'assigned_by': self.assigned_by,
            'assigned_at': self.assigned_at.isoformat() if self.assigned_at else None,
            'expires_at': self.expires_at.isoformat() if self.expires_at else None,
            'is_active': self.is_active
        }

class Permission(db.Model):
    __tablename__ = 'permissions'
    
    permission_id = db.Column(db.String(36), primary_key=True, default=lambda: str(uuid.uuid4()))
    permission_name = db.Column(db.String(100), unique=True, nullable=False)
    permission_description = db.Column(db.Text)
    resource_type = db.Column(db.String(50), nullable=False)
    action_type = db.Column(db.String(20), nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def __repr__(self):
        return f'<Permission {self.permission_name}>'
    
    def to_dict(self):
        return {
            'permission_id': self.permission_id,
            'permission_name': self.permission_name,
            'permission_description': self.permission_description,
            'resource_type': self.resource_type,
            'action_type': self.action_type,
            'created_at': self.created_at.isoformat() if self.created_at else None
        }

class RolePermission(db.Model):
    __tablename__ = 'role_permissions'
    
    role_id = db.Column(db.String(36), db.ForeignKey('user_roles.role_id'), primary_key=True)
    permission_id = db.Column(db.String(36), db.ForeignKey('permissions.permission_id'), primary_key=True)
    granted_by = db.Column(db.String(36), db.ForeignKey('users.user_id'), nullable=False)
    granted_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # Relationships
    role = db.relationship('UserRole', backref='permissions')
    permission = db.relationship('Permission', backref='roles')
    granter = db.relationship('User')
    
    def __repr__(self):
        return f'<RolePermission {self.role_id} -> {self.permission_id}>'
    
    def to_dict(self):
        return {
            'role_id': self.role_id,
            'permission_id': self.permission_id,
            'granted_by': self.granted_by,
            'granted_at': self.granted_at.isoformat() if self.granted_at else None
        }

