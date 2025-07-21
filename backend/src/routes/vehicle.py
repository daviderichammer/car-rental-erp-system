from flask import Blueprint, request, jsonify
from src.models.user import db
from src.models.vehicle import VehicleCategory, Vehicle, VehicleFeature, VehicleFeatureAssignment
from src.models.location import Location
from src.models.reservation import Reservation
from datetime import datetime
from sqlalchemy import or_, and_

vehicle_bp = Blueprint('vehicle', __name__)

@vehicle_bp.route('/categories', methods=['GET'])
def get_vehicle_categories():
    """Get all vehicle categories"""
    try:
        categories = VehicleCategory.query.filter_by(is_active=True).all()
        return jsonify({
            'categories': [category.to_dict() for category in categories]
        }), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@vehicle_bp.route('/categories', methods=['POST'])
def create_vehicle_category():
    """Create new vehicle category"""
    try:
        data = request.get_json()
        
        # Validate required fields
        required_fields = ['category_name', 'category_code', 'base_daily_rate', 'deposit_amount', 'passenger_capacity']
        for field in required_fields:
            if not data.get(field):
                return jsonify({'error': f'{field} is required'}), 400
        
        # Check if category code already exists
        existing_category = VehicleCategory.query.filter_by(category_code=data['category_code']).first()
        if existing_category:
            return jsonify({'error': 'Category code already exists'}), 409
        
        category = VehicleCategory(
            category_name=data['category_name'],
            category_code=data['category_code'],
            description=data.get('description'),
            base_daily_rate=data['base_daily_rate'],
            base_hourly_rate=data.get('base_hourly_rate'),
            mileage_rate=data.get('mileage_rate'),
            deposit_amount=data['deposit_amount'],
            passenger_capacity=data['passenger_capacity'],
            luggage_capacity=data.get('luggage_capacity'),
            transmission_type=data.get('transmission_type'),
            fuel_type=data.get('fuel_type')
        )
        
        db.session.add(category)
        db.session.commit()
        
        return jsonify({
            'category': category.to_dict(),
            'message': 'Vehicle category created successfully'
        }), 201
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@vehicle_bp.route('/', methods=['GET'])
def get_vehicles():
    """Get all vehicles with optional filtering"""
    try:
        # Query parameters
        page = request.args.get('page', 1, type=int)
        per_page = min(request.args.get('per_page', 20, type=int), 100)
        search = request.args.get('search', '')
        category_id = request.args.get('category_id', '')
        status = request.args.get('status', '')
        location_id = request.args.get('location_id', '')
        
        # Build query
        query = Vehicle.query.join(VehicleCategory)
        
        # Apply filters
        if search:
            query = query.filter(
                or_(
                    Vehicle.vehicle_number.ilike(f'%{search}%'),
                    Vehicle.license_plate.ilike(f'%{search}%'),
                    Vehicle.make.ilike(f'%{search}%'),
                    Vehicle.model.ilike(f'%{search}%'),
                    Vehicle.vin.ilike(f'%{search}%')
                )
            )
        
        if category_id:
            query = query.filter(Vehicle.category_id == category_id)
        
        if status:
            query = query.filter(Vehicle.status == status)
        
        if location_id:
            query = query.filter(Vehicle.current_location_id == location_id)
        
        # Order by vehicle number
        query = query.order_by(Vehicle.vehicle_number)
        
        # Paginate
        vehicles = query.paginate(
            page=page,
            per_page=per_page,
            error_out=False
        )
        
        # Format response
        vehicle_list = []
        for vehicle in vehicles.items:
            vehicle_data = vehicle.to_dict()
            vehicle_data['category'] = vehicle.category.to_dict()
            if vehicle.current_location:
                vehicle_data['current_location'] = vehicle.current_location.to_dict()
            vehicle_list.append(vehicle_data)
        
        return jsonify({
            'vehicles': vehicle_list,
            'pagination': {
                'page': vehicles.page,
                'pages': vehicles.pages,
                'per_page': vehicles.per_page,
                'total': vehicles.total,
                'has_next': vehicles.has_next,
                'has_prev': vehicles.has_prev
            }
        }), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@vehicle_bp.route('/<vehicle_id>', methods=['GET'])
def get_vehicle(vehicle_id):
    """Get specific vehicle details"""
    try:
        vehicle = Vehicle.query.get(vehicle_id)
        if not vehicle:
            return jsonify({'error': 'Vehicle not found'}), 404
        
        vehicle_data = vehicle.to_dict()
        vehicle_data['category'] = vehicle.category.to_dict()
        
        if vehicle.current_location:
            vehicle_data['current_location'] = vehicle.current_location.to_dict()
        
        # Get vehicle features
        features = []
        for assignment in vehicle.feature_assignments:
            feature_data = assignment.feature.to_dict()
            feature_data['assigned_at'] = assignment.assigned_at.isoformat() if assignment.assigned_at else None
            features.append(feature_data)
        vehicle_data['features'] = features
        
        return jsonify(vehicle_data), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@vehicle_bp.route('/', methods=['POST'])
def create_vehicle():
    """Create new vehicle"""
    try:
        data = request.get_json()
        
        # Validate required fields
        required_fields = ['vehicle_number', 'license_plate', 'vin', 'category_id', 'make', 'model', 'year']
        for field in required_fields:
            if not data.get(field):
                return jsonify({'error': f'{field} is required'}), 400
        
        # Check if vehicle already exists
        existing_vehicle = Vehicle.query.filter(
            or_(
                Vehicle.vehicle_number == data['vehicle_number'],
                Vehicle.license_plate == data['license_plate'],
                Vehicle.vin == data['vin']
            )
        ).first()
        
        if existing_vehicle:
            return jsonify({'error': 'Vehicle with this number, license plate, or VIN already exists'}), 409
        
        # Verify category exists
        category = VehicleCategory.query.get(data['category_id'])
        if not category:
            return jsonify({'error': 'Vehicle category not found'}), 404
        
        vehicle = Vehicle(
            vehicle_number=data['vehicle_number'],
            license_plate=data['license_plate'],
            vin=data['vin'],
            category_id=data['category_id'],
            make=data['make'],
            model=data['model'],
            year=data['year'],
            color=data.get('color'),
            fuel_capacity=data.get('fuel_capacity'),
            current_mileage=data.get('current_mileage', 0),
            purchase_date=datetime.strptime(data['purchase_date'], '%Y-%m-%d').date() if data.get('purchase_date') else None,
            purchase_price=data.get('purchase_price'),
            current_location_id=data.get('current_location_id'),
            status=data.get('status', 'available'),
            condition_rating=data.get('condition_rating'),
            insurance_policy_number=data.get('insurance_policy_number'),
            insurance_expiry=datetime.strptime(data['insurance_expiry'], '%Y-%m-%d').date() if data.get('insurance_expiry') else None,
            registration_expiry=datetime.strptime(data['registration_expiry'], '%Y-%m-%d').date() if data.get('registration_expiry') else None,
            gps_device_id=data.get('gps_device_id')
        )
        
        db.session.add(vehicle)
        db.session.commit()
        
        vehicle_data = vehicle.to_dict()
        vehicle_data['category'] = category.to_dict()
        
        return jsonify({
            'vehicle': vehicle_data,
            'message': 'Vehicle created successfully'
        }), 201
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@vehicle_bp.route('/<vehicle_id>', methods=['PUT'])
def update_vehicle(vehicle_id):
    """Update vehicle information"""
    try:
        vehicle = Vehicle.query.get(vehicle_id)
        if not vehicle:
            return jsonify({'error': 'Vehicle not found'}), 404
        
        data = request.get_json()
        
        # Update vehicle fields
        if 'license_plate' in data:
            vehicle.license_plate = data['license_plate']
        if 'category_id' in data:
            vehicle.category_id = data['category_id']
        if 'color' in data:
            vehicle.color = data['color']
        if 'current_mileage' in data:
            vehicle.current_mileage = data['current_mileage']
        if 'current_location_id' in data:
            vehicle.current_location_id = data['current_location_id']
        if 'status' in data:
            vehicle.status = data['status']
        if 'condition_rating' in data:
            vehicle.condition_rating = data['condition_rating']
        if 'insurance_policy_number' in data:
            vehicle.insurance_policy_number = data['insurance_policy_number']
        if 'insurance_expiry' in data and data['insurance_expiry']:
            vehicle.insurance_expiry = datetime.strptime(data['insurance_expiry'], '%Y-%m-%d').date()
        if 'registration_expiry' in data and data['registration_expiry']:
            vehicle.registration_expiry = datetime.strptime(data['registration_expiry'], '%Y-%m-%d').date()
        if 'gps_device_id' in data:
            vehicle.gps_device_id = data['gps_device_id']
        
        vehicle.updated_at = datetime.utcnow()
        
        db.session.commit()
        
        vehicle_data = vehicle.to_dict()
        vehicle_data['category'] = vehicle.category.to_dict()
        
        return jsonify({
            'vehicle': vehicle_data,
            'message': 'Vehicle updated successfully'
        }), 200
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@vehicle_bp.route('/availability', methods=['GET'])
def check_availability():
    """Check vehicle availability for given dates and location"""
    try:
        # Query parameters
        pickup_datetime = request.args.get('pickup_datetime')
        return_datetime = request.args.get('return_datetime')
        pickup_location_id = request.args.get('pickup_location_id')
        category_id = request.args.get('category_id')
        
        if not all([pickup_datetime, return_datetime, pickup_location_id]):
            return jsonify({'error': 'pickup_datetime, return_datetime, and pickup_location_id are required'}), 400
        
        # Parse datetime strings
        pickup_dt = datetime.fromisoformat(pickup_datetime.replace('Z', '+00:00'))
        return_dt = datetime.fromisoformat(return_datetime.replace('Z', '+00:00'))
        
        # Build base query for available vehicles
        query = Vehicle.query.filter(
            Vehicle.status == 'available',
            Vehicle.is_active == True
        )
        
        if category_id:
            query = query.filter(Vehicle.category_id == category_id)
        
        # Find vehicles that are not reserved during the requested period
        conflicting_reservations = db.session.query(Reservation.assigned_vehicle_id).filter(
            and_(
                Reservation.status.in_(['confirmed', 'in_progress']),
                or_(
                    and_(Reservation.pickup_datetime <= pickup_dt, Reservation.return_datetime > pickup_dt),
                    and_(Reservation.pickup_datetime < return_dt, Reservation.return_datetime >= return_dt),
                    and_(Reservation.pickup_datetime >= pickup_dt, Reservation.return_datetime <= return_dt)
                )
            )
        ).subquery()
        
        available_vehicles = query.filter(
            ~Vehicle.vehicle_id.in_(conflicting_reservations)
        ).all()
        
        # Group by category
        availability_by_category = {}
        for vehicle in available_vehicles:
            category_id = vehicle.category_id
            if category_id not in availability_by_category:
                availability_by_category[category_id] = {
                    'category': vehicle.category.to_dict(),
                    'available_count': 0,
                    'vehicles': []
                }
            
            availability_by_category[category_id]['available_count'] += 1
            availability_by_category[category_id]['vehicles'].append(vehicle.to_dict())
        
        return jsonify({
            'availability': list(availability_by_category.values()),
            'pickup_datetime': pickup_datetime,
            'return_datetime': return_datetime,
            'pickup_location_id': pickup_location_id
        }), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@vehicle_bp.route('/features', methods=['GET'])
def get_vehicle_features():
    """Get all vehicle features"""
    try:
        features = VehicleFeature.query.filter_by(is_active=True).all()
        return jsonify({
            'features': [feature.to_dict() for feature in features]
        }), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@vehicle_bp.route('/features', methods=['POST'])
def create_vehicle_feature():
    """Create new vehicle feature"""
    try:
        data = request.get_json()
        
        if not data.get('feature_name'):
            return jsonify({'error': 'feature_name is required'}), 400
        
        # Check if feature already exists
        existing_feature = VehicleFeature.query.filter_by(feature_name=data['feature_name']).first()
        if existing_feature:
            return jsonify({'error': 'Feature with this name already exists'}), 409
        
        feature = VehicleFeature(
            feature_name=data['feature_name'],
            feature_description=data.get('feature_description'),
            feature_category=data.get('feature_category'),
            additional_daily_rate=data.get('additional_daily_rate', 0.00)
        )
        
        db.session.add(feature)
        db.session.commit()
        
        return jsonify({
            'feature': feature.to_dict(),
            'message': 'Vehicle feature created successfully'
        }), 201
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@vehicle_bp.route('/<vehicle_id>/features', methods=['POST'])
def assign_vehicle_feature(vehicle_id):
    """Assign feature to vehicle"""
    try:
        vehicle = Vehicle.query.get(vehicle_id)
        if not vehicle:
            return jsonify({'error': 'Vehicle not found'}), 404
        
        data = request.get_json()
        feature_id = data.get('feature_id')
        
        if not feature_id:
            return jsonify({'error': 'feature_id is required'}), 400
        
        feature = VehicleFeature.query.get(feature_id)
        if not feature:
            return jsonify({'error': 'Feature not found'}), 404
        
        # Check if already assigned
        existing_assignment = VehicleFeatureAssignment.query.filter_by(
            vehicle_id=vehicle_id,
            feature_id=feature_id
        ).first()
        
        if existing_assignment:
            return jsonify({'error': 'Feature already assigned to this vehicle'}), 409
        
        assignment = VehicleFeatureAssignment(
            vehicle_id=vehicle_id,
            feature_id=feature_id
        )
        
        db.session.add(assignment)
        db.session.commit()
        
        return jsonify({
            'assignment': assignment.to_dict(),
            'message': 'Feature assigned to vehicle successfully'
        }), 201
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

