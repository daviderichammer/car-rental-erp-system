from flask import Blueprint, request, jsonify
from src.models.user import db, User
from src.models.customer import Customer, CustomerAddress
from src.models.reservation import Reservation
from src.models.financial import Payment, Invoice
from datetime import datetime
from sqlalchemy import or_

customer_bp = Blueprint('customer', __name__)

@customer_bp.route('/', methods=['GET'])
def get_customers():
    """Get all customers with optional filtering"""
    try:
        # Query parameters
        page = request.args.get('page', 1, type=int)
        per_page = min(request.args.get('per_page', 20, type=int), 100)
        search = request.args.get('search', '')
        status = request.args.get('status', '')
        risk_level = request.args.get('risk_level', '')
        
        # Build query
        query = db.session.query(Customer).join(User)
        
        # Apply filters
        if search:
            query = query.filter(
                or_(
                    User.first_name.ilike(f'%{search}%'),
                    User.last_name.ilike(f'%{search}%'),
                    User.email.ilike(f'%{search}%'),
                    Customer.customer_number.ilike(f'%{search}%')
                )
            )
        
        if status:
            query = query.filter(User.status == status)
        
        if risk_level:
            query = query.filter(Customer.risk_level == risk_level)
        
        # Order by creation date
        query = query.order_by(Customer.created_at.desc())
        
        # Paginate
        customers = query.paginate(
            page=page, 
            per_page=per_page, 
            error_out=False
        )
        
        # Format response
        customer_list = []
        for customer in customers.items:
            customer_data = customer.to_dict()
            customer_data['user'] = customer.user.to_dict()
            customer_list.append(customer_data)
        
        return jsonify({
            'customers': customer_list,
            'pagination': {
                'page': customers.page,
                'pages': customers.pages,
                'per_page': customers.per_page,
                'total': customers.total,
                'has_next': customers.has_next,
                'has_prev': customers.has_prev
            }
        }), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@customer_bp.route('/<customer_id>', methods=['GET'])
def get_customer(customer_id):
    """Get specific customer details"""
    try:
        customer = Customer.query.get(customer_id)
        if not customer:
            return jsonify({'error': 'Customer not found'}), 404
        
        customer_data = customer.to_dict()
        customer_data['user'] = customer.user.to_dict()
        customer_data['addresses'] = [addr.to_dict() for addr in customer.addresses]
        
        # Get recent reservations
        recent_reservations = Reservation.query.filter_by(customer_id=customer_id)\
            .order_by(Reservation.created_at.desc()).limit(5).all()
        customer_data['recent_reservations'] = [res.to_dict() for res in recent_reservations]
        
        return jsonify(customer_data), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@customer_bp.route('/', methods=['POST'])
def create_customer():
    """Create new customer"""
    try:
        data = request.get_json()
        
        # Validate required fields
        required_fields = ['email', 'first_name', 'last_name']
        for field in required_fields:
            if not data.get(field):
                return jsonify({'error': f'{field} is required'}), 400
        
        # Check if user already exists
        existing_user = User.query.filter_by(email=data['email']).first()
        if existing_user:
            return jsonify({'error': 'User with this email already exists'}), 409
        
        # Create user
        user = User(
            email=data['email'],
            first_name=data['first_name'],
            last_name=data['last_name'],
            phone_number=data.get('phone_number'),
            date_of_birth=datetime.strptime(data['date_of_birth'], '%Y-%m-%d').date() if data.get('date_of_birth') else None,
            user_type='customer',
            status=data.get('status', 'active')
        )
        
        if data.get('password'):
            user.set_password(data['password'])
        else:
            # Generate temporary password
            user.set_password('temp123')
        
        db.session.add(user)
        db.session.flush()  # Get user_id
        
        # Create customer
        customer = Customer(
            customer_id=user.user_id,
            customer_number=f'CUST{user.user_id[:8].upper()}',
            driver_license_number=data.get('driver_license_number'),
            driver_license_state=data.get('driver_license_state'),
            driver_license_country=data.get('driver_license_country', 'USA'),
            credit_score=data.get('credit_score'),
            preferred_language=data.get('preferred_language', 'en'),
            marketing_opt_in=data.get('marketing_opt_in', False),
            risk_level=data.get('risk_level', 'low'),
            notes=data.get('notes')
        )
        
        db.session.add(customer)
        db.session.commit()
        
        customer_data = customer.to_dict()
        customer_data['user'] = user.to_dict()
        
        return jsonify({
            'customer': customer_data,
            'message': 'Customer created successfully'
        }), 201
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@customer_bp.route('/<customer_id>', methods=['PUT'])
def update_customer(customer_id):
    """Update customer information"""
    try:
        customer = Customer.query.get(customer_id)
        if not customer:
            return jsonify({'error': 'Customer not found'}), 404
        
        data = request.get_json()
        
        # Update user fields
        user = customer.user
        if 'first_name' in data:
            user.first_name = data['first_name']
        if 'last_name' in data:
            user.last_name = data['last_name']
        if 'phone_number' in data:
            user.phone_number = data['phone_number']
        if 'date_of_birth' in data and data['date_of_birth']:
            user.date_of_birth = datetime.strptime(data['date_of_birth'], '%Y-%m-%d').date()
        if 'status' in data:
            user.status = data['status']
        
        user.updated_at = datetime.utcnow()
        
        # Update customer fields
        if 'driver_license_number' in data:
            customer.driver_license_number = data['driver_license_number']
        if 'driver_license_state' in data:
            customer.driver_license_state = data['driver_license_state']
        if 'driver_license_country' in data:
            customer.driver_license_country = data['driver_license_country']
        if 'credit_score' in data:
            customer.credit_score = data['credit_score']
        if 'preferred_language' in data:
            customer.preferred_language = data['preferred_language']
        if 'marketing_opt_in' in data:
            customer.marketing_opt_in = data['marketing_opt_in']
        if 'risk_level' in data:
            customer.risk_level = data['risk_level']
        if 'notes' in data:
            customer.notes = data['notes']
        
        customer.updated_at = datetime.utcnow()
        
        db.session.commit()
        
        customer_data = customer.to_dict()
        customer_data['user'] = user.to_dict()
        
        return jsonify({
            'customer': customer_data,
            'message': 'Customer updated successfully'
        }), 200
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@customer_bp.route('/<customer_id>/addresses', methods=['GET'])
def get_customer_addresses(customer_id):
    """Get customer addresses"""
    try:
        customer = Customer.query.get(customer_id)
        if not customer:
            return jsonify({'error': 'Customer not found'}), 404
        
        addresses = [addr.to_dict() for addr in customer.addresses]
        return jsonify({'addresses': addresses}), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@customer_bp.route('/<customer_id>/addresses', methods=['POST'])
def add_customer_address(customer_id):
    """Add new address for customer"""
    try:
        customer = Customer.query.get(customer_id)
        if not customer:
            return jsonify({'error': 'Customer not found'}), 404
        
        data = request.get_json()
        
        # Validate required fields
        required_fields = ['address_type', 'street_address_1', 'city', 'country']
        for field in required_fields:
            if not data.get(field):
                return jsonify({'error': f'{field} is required'}), 400
        
        # If this is set as primary, unset other primary addresses of same type
        if data.get('is_primary', False):
            CustomerAddress.query.filter_by(
                customer_id=customer_id,
                address_type=data['address_type'],
                is_primary=True
            ).update({'is_primary': False})
        
        address = CustomerAddress(
            customer_id=customer_id,
            address_type=data['address_type'],
            street_address_1=data['street_address_1'],
            street_address_2=data.get('street_address_2'),
            city=data['city'],
            state_province=data.get('state_province'),
            postal_code=data.get('postal_code'),
            country=data['country'],
            is_primary=data.get('is_primary', False)
        )
        
        db.session.add(address)
        db.session.commit()
        
        return jsonify({
            'address': address.to_dict(),
            'message': 'Address added successfully'
        }), 201
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@customer_bp.route('/<customer_id>/reservations', methods=['GET'])
def get_customer_reservations(customer_id):
    """Get customer reservation history"""
    try:
        customer = Customer.query.get(customer_id)
        if not customer:
            return jsonify({'error': 'Customer not found'}), 404
        
        page = request.args.get('page', 1, type=int)
        per_page = min(request.args.get('per_page', 20, type=int), 100)
        status = request.args.get('status', '')
        
        query = Reservation.query.filter_by(customer_id=customer_id)
        
        if status:
            query = query.filter(Reservation.status == status)
        
        query = query.order_by(Reservation.created_at.desc())
        
        reservations = query.paginate(
            page=page,
            per_page=per_page,
            error_out=False
        )
        
        reservation_list = []
        for reservation in reservations.items:
            res_data = reservation.to_dict()
            if reservation.assigned_vehicle:
                res_data['vehicle'] = reservation.assigned_vehicle.to_dict()
            if reservation.vehicle_category:
                res_data['category'] = reservation.vehicle_category.to_dict()
            reservation_list.append(res_data)
        
        return jsonify({
            'reservations': reservation_list,
            'pagination': {
                'page': reservations.page,
                'pages': reservations.pages,
                'per_page': reservations.per_page,
                'total': reservations.total,
                'has_next': reservations.has_next,
                'has_prev': reservations.has_prev
            }
        }), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@customer_bp.route('/<customer_id>/payments', methods=['GET'])
def get_customer_payments(customer_id):
    """Get customer payment history"""
    try:
        customer = Customer.query.get(customer_id)
        if not customer:
            return jsonify({'error': 'Customer not found'}), 404
        
        page = request.args.get('page', 1, type=int)
        per_page = min(request.args.get('per_page', 20, type=int), 100)
        
        payments = Payment.query.filter_by(customer_id=customer_id)\
            .order_by(Payment.created_at.desc())\
            .paginate(page=page, per_page=per_page, error_out=False)
        
        payment_list = [payment.to_dict() for payment in payments.items]
        
        return jsonify({
            'payments': payment_list,
            'pagination': {
                'page': payments.page,
                'pages': payments.pages,
                'per_page': payments.per_page,
                'total': payments.total,
                'has_next': payments.has_next,
                'has_prev': payments.has_prev
            }
        }), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

