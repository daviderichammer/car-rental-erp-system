from flask import Blueprint, request, jsonify
from src.models.user import db
from src.models.reservation import Reservation, RentalAgreement
from src.models.customer import Customer
from src.models.vehicle import Vehicle, VehicleCategory
from src.models.location import Location
from datetime import datetime
from sqlalchemy import or_, and_
import uuid

reservation_bp = Blueprint('reservation', __name__)

@reservation_bp.route('/', methods=['GET'])
def get_reservations():
    """Get all reservations with optional filtering"""
    try:
        page = request.args.get('page', 1, type=int)
        per_page = min(request.args.get('per_page', 20, type=int), 100)
        status = request.args.get('status', '')
        customer_id = request.args.get('customer_id', '')
        pickup_date = request.args.get('pickup_date', '')
        
        query = Reservation.query
        
        if status:
            query = query.filter(Reservation.status == status)
        
        if customer_id:
            query = query.filter(Reservation.customer_id == customer_id)
        
        if pickup_date:
            pickup_dt = datetime.strptime(pickup_date, '%Y-%m-%d')
            query = query.filter(
                and_(
                    Reservation.pickup_datetime >= pickup_dt,
                    Reservation.pickup_datetime < pickup_dt.replace(hour=23, minute=59, second=59)
                )
            )
        
        query = query.order_by(Reservation.pickup_datetime.desc())
        
        reservations = query.paginate(
            page=page,
            per_page=per_page,
            error_out=False
        )
        
        reservation_list = []
        for reservation in reservations.items:
            res_data = reservation.to_dict()
            res_data['customer'] = reservation.customer.to_dict()
            res_data['customer']['user'] = reservation.customer.user.to_dict()
            res_data['vehicle_category'] = reservation.vehicle_category.to_dict()
            res_data['pickup_location'] = reservation.pickup_location.to_dict()
            res_data['return_location'] = reservation.return_location.to_dict()
            
            if reservation.assigned_vehicle:
                res_data['assigned_vehicle'] = reservation.assigned_vehicle.to_dict()
            
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

@reservation_bp.route('/<reservation_id>', methods=['GET'])
def get_reservation(reservation_id):
    """Get specific reservation details"""
    try:
        reservation = Reservation.query.get(reservation_id)
        if not reservation:
            return jsonify({'error': 'Reservation not found'}), 404
        
        res_data = reservation.to_dict()
        res_data['customer'] = reservation.customer.to_dict()
        res_data['customer']['user'] = reservation.customer.user.to_dict()
        res_data['vehicle_category'] = reservation.vehicle_category.to_dict()
        res_data['pickup_location'] = reservation.pickup_location.to_dict()
        res_data['return_location'] = reservation.return_location.to_dict()
        
        if reservation.assigned_vehicle:
            res_data['assigned_vehicle'] = reservation.assigned_vehicle.to_dict()
        
        if reservation.rental_agreement:
            res_data['rental_agreement'] = reservation.rental_agreement.to_dict()
        
        return jsonify(res_data), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@reservation_bp.route('/', methods=['POST'])
def create_reservation():
    """Create new reservation"""
    try:
        data = request.get_json()
        
        required_fields = ['customer_id', 'vehicle_category_id', 'pickup_location_id', 'return_location_id', 'pickup_datetime', 'return_datetime']
        for field in required_fields:
            if not data.get(field):
                return jsonify({'error': f'{field} is required'}), 400
        
        # Verify customer exists
        customer = Customer.query.get(data['customer_id'])
        if not customer:
            return jsonify({'error': 'Customer not found'}), 404
        
        # Verify category exists
        category = VehicleCategory.query.get(data['vehicle_category_id'])
        if not category:
            return jsonify({'error': 'Vehicle category not found'}), 404
        
        # Verify locations exist
        pickup_location = Location.query.get(data['pickup_location_id'])
        return_location = Location.query.get(data['return_location_id'])
        if not pickup_location or not return_location:
            return jsonify({'error': 'Pickup or return location not found'}), 404
        
        # Parse datetime strings
        pickup_dt = datetime.fromisoformat(data['pickup_datetime'].replace('Z', '+00:00'))
        return_dt = datetime.fromisoformat(data['return_datetime'].replace('Z', '+00:00'))
        
        if pickup_dt >= return_dt:
            return jsonify({'error': 'Return datetime must be after pickup datetime'}), 400
        
        # Generate reservation number
        reservation_number = f'RES{str(uuid.uuid4())[:8].upper()}'
        
        # Calculate estimated cost
        rental_days = max(1, (return_dt - pickup_dt).days)
        estimated_cost = float(category.base_daily_rate) * rental_days
        
        reservation = Reservation(
            reservation_number=reservation_number,
            customer_id=data['customer_id'],
            vehicle_category_id=data['vehicle_category_id'],
            pickup_location_id=data['pickup_location_id'],
            return_location_id=data['return_location_id'],
            pickup_datetime=pickup_dt,
            return_datetime=return_dt,
            total_estimated_cost=estimated_cost,
            deposit_amount=category.deposit_amount,
            special_requests=data.get('special_requests'),
            status='pending'
        )
        
        db.session.add(reservation)
        db.session.commit()
        
        res_data = reservation.to_dict()
        res_data['customer'] = customer.to_dict()
        res_data['vehicle_category'] = category.to_dict()
        res_data['pickup_location'] = pickup_location.to_dict()
        res_data['return_location'] = return_location.to_dict()
        
        return jsonify({
            'reservation': res_data,
            'message': 'Reservation created successfully'
        }), 201
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@reservation_bp.route('/<reservation_id>/confirm', methods=['POST'])
def confirm_reservation(reservation_id):
    """Confirm reservation and assign vehicle"""
    try:
        reservation = Reservation.query.get(reservation_id)
        if not reservation:
            return jsonify({'error': 'Reservation not found'}), 404
        
        if reservation.status != 'pending':
            return jsonify({'error': 'Only pending reservations can be confirmed'}), 400
        
        data = request.get_json()
        assigned_vehicle_id = data.get('assigned_vehicle_id')
        
        if assigned_vehicle_id:
            # Verify vehicle exists and is available
            vehicle = Vehicle.query.get(assigned_vehicle_id)
            if not vehicle:
                return jsonify({'error': 'Vehicle not found'}), 404
            
            if vehicle.status != 'available':
                return jsonify({'error': 'Vehicle is not available'}), 400
            
            # Check if vehicle is already reserved for this period
            conflicting_reservation = Reservation.query.filter(
                and_(
                    Reservation.assigned_vehicle_id == assigned_vehicle_id,
                    Reservation.status.in_(['confirmed', 'in_progress']),
                    or_(
                        and_(Reservation.pickup_datetime <= reservation.pickup_datetime, Reservation.return_datetime > reservation.pickup_datetime),
                        and_(Reservation.pickup_datetime < reservation.return_datetime, Reservation.return_datetime >= reservation.return_datetime),
                        and_(Reservation.pickup_datetime >= reservation.pickup_datetime, Reservation.return_datetime <= reservation.return_datetime)
                    )
                )
            ).first()
            
            if conflicting_reservation:
                return jsonify({'error': 'Vehicle is already reserved for this period'}), 409
            
            reservation.assigned_vehicle_id = assigned_vehicle_id
        
        reservation.status = 'confirmed'
        reservation.updated_at = datetime.utcnow()
        
        db.session.commit()
        
        return jsonify({
            'reservation': reservation.to_dict(),
            'message': 'Reservation confirmed successfully'
        }), 200
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@reservation_bp.route('/<reservation_id>/cancel', methods=['POST'])
def cancel_reservation(reservation_id):
    """Cancel reservation"""
    try:
        reservation = Reservation.query.get(reservation_id)
        if not reservation:
            return jsonify({'error': 'Reservation not found'}), 404
        
        if reservation.status in ['completed', 'cancelled']:
            return jsonify({'error': 'Reservation cannot be cancelled'}), 400
        
        data = request.get_json()
        cancellation_reason = data.get('cancellation_reason', '')
        
        reservation.status = 'cancelled'
        reservation.cancellation_reason = cancellation_reason
        reservation.updated_at = datetime.utcnow()
        
        db.session.commit()
        
        return jsonify({
            'reservation': reservation.to_dict(),
            'message': 'Reservation cancelled successfully'
        }), 200
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@reservation_bp.route('/<reservation_id>/checkin', methods=['POST'])
def checkin_reservation(reservation_id):
    """Check in customer for rental"""
    try:
        reservation = Reservation.query.get(reservation_id)
        if not reservation:
            return jsonify({'error': 'Reservation not found'}), 404
        
        if reservation.status != 'confirmed':
            return jsonify({'error': 'Only confirmed reservations can be checked in'}), 400
        
        data = request.get_json()
        
        # Create rental agreement
        agreement_number = f'AGR{str(uuid.uuid4())[:8].upper()}'
        
        rental_agreement = RentalAgreement(
            reservation_id=reservation_id,
            agreement_number=agreement_number,
            terms_and_conditions=data.get('terms_and_conditions', 'Standard rental terms and conditions apply.'),
            pickup_mileage=data.get('pickup_mileage'),
            fuel_level_pickup=data.get('fuel_level_pickup'),
            pickup_condition_notes=data.get('pickup_condition_notes')
        )
        
        reservation.status = 'in_progress'
        reservation.updated_at = datetime.utcnow()
        
        # Update vehicle status
        if reservation.assigned_vehicle:
            reservation.assigned_vehicle.status = 'rented'
            reservation.assigned_vehicle.updated_at = datetime.utcnow()
        
        db.session.add(rental_agreement)
        db.session.commit()
        
        return jsonify({
            'reservation': reservation.to_dict(),
            'rental_agreement': rental_agreement.to_dict(),
            'message': 'Customer checked in successfully'
        }), 200
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@reservation_bp.route('/<reservation_id>/checkout', methods=['POST'])
def checkout_reservation(reservation_id):
    """Check out customer and complete rental"""
    try:
        reservation = Reservation.query.get(reservation_id)
        if not reservation:
            return jsonify({'error': 'Reservation not found'}), 404
        
        if reservation.status != 'in_progress':
            return jsonify({'error': 'Only in-progress reservations can be checked out'}), 400
        
        data = request.get_json()
        
        # Update rental agreement
        if reservation.rental_agreement:
            agreement = reservation.rental_agreement
            agreement.return_mileage = data.get('return_mileage')
            agreement.fuel_level_return = data.get('fuel_level_return')
            agreement.return_condition_notes = data.get('return_condition_notes')
            agreement.additional_charges = data.get('additional_charges', 0.00)
            agreement.damage_charges = data.get('damage_charges', 0.00)
            agreement.updated_at = datetime.utcnow()
        
        # Calculate final cost
        base_cost = float(reservation.total_estimated_cost) if reservation.total_estimated_cost else 0.00
        additional_charges = float(data.get('additional_charges', 0.00))
        damage_charges = float(data.get('damage_charges', 0.00))
        
        reservation.total_actual_cost = base_cost + additional_charges + damage_charges
        reservation.status = 'completed'
        reservation.updated_at = datetime.utcnow()
        
        # Update vehicle status and mileage
        if reservation.assigned_vehicle:
            reservation.assigned_vehicle.status = 'available'
            if data.get('return_mileage'):
                reservation.assigned_vehicle.current_mileage = data['return_mileage']
            reservation.assigned_vehicle.updated_at = datetime.utcnow()
        
        # Update customer statistics
        customer = reservation.customer
        customer.total_rentals += 1
        customer.total_spent += reservation.total_actual_cost
        customer.updated_at = datetime.utcnow()
        
        db.session.commit()
        
        return jsonify({
            'reservation': reservation.to_dict(),
            'message': 'Customer checked out successfully'
        }), 200
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

