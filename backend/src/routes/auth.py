from flask import Blueprint, request, jsonify
from src.models.user import db, User, UserRole, UserRoleAssignment
from src.models.customer import Customer
from datetime import datetime, timedelta
import jwt
import os

auth_bp = Blueprint('auth', __name__)

def generate_token(user):
    """Generate JWT token for user"""
    payload = {
        'user_id': user.user_id,
        'email': user.email,
        'user_type': user.user_type,
        'exp': datetime.utcnow() + timedelta(hours=24)
    }
    return jwt.encode(payload, os.environ.get('SECRET_KEY', 'car_rental_erp_secret_key_2025'), algorithm='HS256')

def verify_token(token):
    """Verify JWT token and return user"""
    try:
        payload = jwt.decode(token, os.environ.get('SECRET_KEY', 'car_rental_erp_secret_key_2025'), algorithms=['HS256'])
        user = User.query.get(payload['user_id'])
        return user
    except jwt.ExpiredSignatureError:
        return None
    except jwt.InvalidTokenError:
        return None

@auth_bp.route('/login', methods=['POST'])
def login():
    """User login endpoint"""
    try:
        data = request.get_json()
        email = data.get('email')
        password = data.get('password')
        
        if not email or not password:
            return jsonify({'error': 'Email and password are required'}), 400
        
        user = User.query.filter_by(email=email).first()
        
        if not user or not user.check_password(password):
            return jsonify({'error': 'Invalid email or password'}), 401
        
        if user.status != 'active':
            return jsonify({'error': 'Account is not active'}), 401
        
        # Update last login time
        user.last_login_at = datetime.utcnow()
        db.session.commit()
        
        # Generate token
        token = generate_token(user)
        
        # Get user roles
        roles = []
        for assignment in user.role_assignments:
            if assignment.is_active:
                roles.append({
                    'role_id': assignment.role.role_id,
                    'role_name': assignment.role.role_name
                })
        
        return jsonify({
            'token': token,
            'user': user.to_dict(),
            'roles': roles
        }), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@auth_bp.route('/register', methods=['POST'])
def register():
    """User registration endpoint"""
    try:
        data = request.get_json()
        
        # Validate required fields
        required_fields = ['email', 'password', 'first_name', 'last_name']
        for field in required_fields:
            if not data.get(field):
                return jsonify({'error': f'{field} is required'}), 400
        
        # Check if user already exists
        existing_user = User.query.filter_by(email=data['email']).first()
        if existing_user:
            return jsonify({'error': 'User with this email already exists'}), 409
        
        # Create new user
        user = User(
            email=data['email'],
            first_name=data['first_name'],
            last_name=data['last_name'],
            phone_number=data.get('phone_number'),
            date_of_birth=datetime.strptime(data['date_of_birth'], '%Y-%m-%d').date() if data.get('date_of_birth') else None,
            user_type=data.get('user_type', 'customer')
        )
        user.set_password(data['password'])
        
        db.session.add(user)
        db.session.flush()  # Get user_id
        
        # If registering as customer, create customer record
        if user.user_type == 'customer':
            customer = Customer(
                customer_id=user.user_id,
                customer_number=f'CUST{user.user_id[:8].upper()}',
                driver_license_number=data.get('driver_license_number'),
                driver_license_state=data.get('driver_license_state'),
                driver_license_country=data.get('driver_license_country', 'USA'),
                preferred_language=data.get('preferred_language', 'en'),
                marketing_opt_in=data.get('marketing_opt_in', False)
            )
            db.session.add(customer)
        
        db.session.commit()
        
        # Generate token
        token = generate_token(user)
        
        return jsonify({
            'token': token,
            'user': user.to_dict(),
            'message': 'User registered successfully'
        }), 201
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@auth_bp.route('/profile', methods=['GET'])
def get_profile():
    """Get current user profile"""
    try:
        token = request.headers.get('Authorization')
        if not token:
            return jsonify({'error': 'Authorization token required'}), 401
        
        # Remove 'Bearer ' prefix if present
        if token.startswith('Bearer '):
            token = token[7:]
        
        user = verify_token(token)
        if not user:
            return jsonify({'error': 'Invalid or expired token'}), 401
        
        # Get user roles
        roles = []
        for assignment in user.role_assignments:
            if assignment.is_active:
                roles.append({
                    'role_id': assignment.role.role_id,
                    'role_name': assignment.role.role_name
                })
        
        profile_data = user.to_dict()
        profile_data['roles'] = roles
        
        # Add customer data if user is a customer
        if user.customer:
            profile_data['customer'] = user.customer.to_dict()
        
        return jsonify(profile_data), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@auth_bp.route('/profile', methods=['PUT'])
def update_profile():
    """Update current user profile"""
    try:
        token = request.headers.get('Authorization')
        if not token:
            return jsonify({'error': 'Authorization token required'}), 401
        
        # Remove 'Bearer ' prefix if present
        if token.startswith('Bearer '):
            token = token[7:]
        
        user = verify_token(token)
        if not user:
            return jsonify({'error': 'Invalid or expired token'}), 401
        
        data = request.get_json()
        
        # Update user fields
        if 'first_name' in data:
            user.first_name = data['first_name']
        if 'last_name' in data:
            user.last_name = data['last_name']
        if 'phone_number' in data:
            user.phone_number = data['phone_number']
        if 'date_of_birth' in data and data['date_of_birth']:
            user.date_of_birth = datetime.strptime(data['date_of_birth'], '%Y-%m-%d').date()
        
        user.updated_at = datetime.utcnow()
        user.updated_by = user.user_id
        
        # Update customer fields if user is a customer
        if user.customer and 'customer' in data:
            customer_data = data['customer']
            if 'driver_license_number' in customer_data:
                user.customer.driver_license_number = customer_data['driver_license_number']
            if 'driver_license_state' in customer_data:
                user.customer.driver_license_state = customer_data['driver_license_state']
            if 'preferred_language' in customer_data:
                user.customer.preferred_language = customer_data['preferred_language']
            if 'marketing_opt_in' in customer_data:
                user.customer.marketing_opt_in = customer_data['marketing_opt_in']
            
            user.customer.updated_at = datetime.utcnow()
        
        db.session.commit()
        
        return jsonify({
            'user': user.to_dict(),
            'message': 'Profile updated successfully'
        }), 200
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@auth_bp.route('/change-password', methods=['POST'])
def change_password():
    """Change user password"""
    try:
        token = request.headers.get('Authorization')
        if not token:
            return jsonify({'error': 'Authorization token required'}), 401
        
        # Remove 'Bearer ' prefix if present
        if token.startswith('Bearer '):
            token = token[7:]
        
        user = verify_token(token)
        if not user:
            return jsonify({'error': 'Invalid or expired token'}), 401
        
        data = request.get_json()
        current_password = data.get('current_password')
        new_password = data.get('new_password')
        
        if not current_password or not new_password:
            return jsonify({'error': 'Current password and new password are required'}), 400
        
        if not user.check_password(current_password):
            return jsonify({'error': 'Current password is incorrect'}), 401
        
        if len(new_password) < 6:
            return jsonify({'error': 'New password must be at least 6 characters long'}), 400
        
        user.set_password(new_password)
        user.updated_at = datetime.utcnow()
        user.updated_by = user.user_id
        
        db.session.commit()
        
        return jsonify({'message': 'Password changed successfully'}), 200
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@auth_bp.route('/verify-token', methods=['POST'])
def verify_user_token():
    """Verify if token is valid"""
    try:
        data = request.get_json()
        token = data.get('token')
        
        if not token:
            return jsonify({'error': 'Token is required'}), 400
        
        user = verify_token(token)
        if not user:
            return jsonify({'valid': False}), 200
        
        return jsonify({
            'valid': True,
            'user': user.to_dict()
        }), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

