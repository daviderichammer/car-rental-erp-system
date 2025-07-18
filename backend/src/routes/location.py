from flask import Blueprint, request, jsonify
from src.models.user import db
from src.models.location import Location
from datetime import datetime
from sqlalchemy import or_

location_bp = Blueprint('location', __name__)

@location_bp.route('/', methods=['GET'])
def get_locations():
    """Get all locations"""
    try:
        search = request.args.get('search', '')
        location_type = request.args.get('type', '')
        is_pickup = request.args.get('is_pickup', '')
        is_return = request.args.get('is_return', '')
        
        query = Location.query.filter_by(is_active=True)
        
        if search:
            query = query.filter(
                or_(
                    Location.location_name.ilike(f'%{search}%'),
                    Location.location_code.ilike(f'%{search}%'),
                    Location.city.ilike(f'%{search}%')
                )
            )
        
        if location_type:
            query = query.filter(Location.location_type == location_type)
        
        if is_pickup:
            query = query.filter(Location.is_pickup_location == (is_pickup.lower() == 'true'))
        
        if is_return:
            query = query.filter(Location.is_return_location == (is_return.lower() == 'true'))
        
        locations = query.order_by(Location.location_name).all()
        
        return jsonify({
            'locations': [location.to_dict() for location in locations]
        }), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@location_bp.route('/<location_id>', methods=['GET'])
def get_location(location_id):
    """Get specific location details"""
    try:
        location = Location.query.get(location_id)
        if not location:
            return jsonify({'error': 'Location not found'}), 404
        
        return jsonify(location.to_dict()), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@location_bp.route('/', methods=['POST'])
def create_location():
    """Create new location"""
    try:
        data = request.get_json()
        
        required_fields = ['location_code', 'location_name', 'location_type', 'street_address', 'city', 'country']
        for field in required_fields:
            if not data.get(field):
                return jsonify({'error': f'{field} is required'}), 400
        
        existing_location = Location.query.filter_by(location_code=data['location_code']).first()
        if existing_location:
            return jsonify({'error': 'Location code already exists'}), 409
        
        location = Location(
            location_code=data['location_code'],
            location_name=data['location_name'],
            location_type=data['location_type'],
            street_address=data['street_address'],
            city=data['city'],
            state_province=data.get('state_province'),
            postal_code=data.get('postal_code'),
            country=data['country'],
            latitude=data.get('latitude'),
            longitude=data.get('longitude'),
            phone_number=data.get('phone_number'),
            capacity=data.get('capacity', 0),
            is_pickup_location=data.get('is_pickup_location', True),
            is_return_location=data.get('is_return_location', True)
        )
        
        if data.get('operating_hours'):
            location.set_operating_hours(data['operating_hours'])
        
        db.session.add(location)
        db.session.commit()
        
        return jsonify({
            'location': location.to_dict(),
            'message': 'Location created successfully'
        }), 201
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@location_bp.route('/<location_id>', methods=['PUT'])
def update_location(location_id):
    """Update location information"""
    try:
        location = Location.query.get(location_id)
        if not location:
            return jsonify({'error': 'Location not found'}), 404
        
        data = request.get_json()
        
        if 'location_name' in data:
            location.location_name = data['location_name']
        if 'location_type' in data:
            location.location_type = data['location_type']
        if 'street_address' in data:
            location.street_address = data['street_address']
        if 'city' in data:
            location.city = data['city']
        if 'state_province' in data:
            location.state_province = data['state_province']
        if 'postal_code' in data:
            location.postal_code = data['postal_code']
        if 'phone_number' in data:
            location.phone_number = data['phone_number']
        if 'capacity' in data:
            location.capacity = data['capacity']
        if 'is_pickup_location' in data:
            location.is_pickup_location = data['is_pickup_location']
        if 'is_return_location' in data:
            location.is_return_location = data['is_return_location']
        if 'operating_hours' in data:
            location.set_operating_hours(data['operating_hours'])
        
        location.updated_at = datetime.utcnow()
        db.session.commit()
        
        return jsonify({
            'location': location.to_dict(),
            'message': 'Location updated successfully'
        }), 200
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

