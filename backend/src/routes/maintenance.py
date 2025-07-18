from flask import Blueprint, request, jsonify
from src.models.user import db
from src.models.maintenance import MaintenanceSchedule, DamageReport
from src.models.vehicle import Vehicle
from src.models.reservation import Reservation
from datetime import datetime, date
from sqlalchemy import or_

maintenance_bp = Blueprint('maintenance', __name__)

@maintenance_bp.route('/schedules', methods=['GET'])
def get_maintenance_schedules():
    """Get all maintenance schedules with optional filtering"""
    try:
        page = request.args.get('page', 1, type=int)
        per_page = min(request.args.get('per_page', 20, type=int), 100)
        vehicle_id = request.args.get('vehicle_id', '')
        status = request.args.get('status', '')
        service_type = request.args.get('service_type', '')
        overdue_only = request.args.get('overdue_only', '').lower() == 'true'
        
        query = MaintenanceSchedule.query
        
        if vehicle_id:
            query = query.filter(MaintenanceSchedule.vehicle_id == vehicle_id)
        
        if status:
            query = query.filter(MaintenanceSchedule.status == status)
        
        if service_type:
            query = query.filter(MaintenanceSchedule.service_type.ilike(f'%{service_type}%'))
        
        if overdue_only:
            today = date.today()
            query = query.filter(
                MaintenanceSchedule.scheduled_date < today,
                MaintenanceSchedule.status.in_(['scheduled', 'in_progress'])
            )
        
        query = query.order_by(MaintenanceSchedule.scheduled_date)
        
        schedules = query.paginate(
            page=page,
            per_page=per_page,
            error_out=False
        )
        
        schedule_list = []
        for schedule in schedules.items:
            schedule_data = schedule.to_dict()
            schedule_data['vehicle'] = schedule.vehicle.to_dict()
            schedule_list.append(schedule_data)
        
        return jsonify({
            'schedules': schedule_list,
            'pagination': {
                'page': schedules.page,
                'pages': schedules.pages,
                'per_page': schedules.per_page,
                'total': schedules.total,
                'has_next': schedules.has_next,
                'has_prev': schedules.has_prev
            }
        }), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@maintenance_bp.route('/schedules', methods=['POST'])
def create_maintenance_schedule():
    """Create new maintenance schedule"""
    try:
        data = request.get_json()
        
        required_fields = ['vehicle_id', 'service_type', 'scheduled_date']
        for field in required_fields:
            if not data.get(field):
                return jsonify({'error': f'{field} is required'}), 400
        
        # Verify vehicle exists
        vehicle = Vehicle.query.get(data['vehicle_id'])
        if not vehicle:
            return jsonify({'error': 'Vehicle not found'}), 404
        
        schedule = MaintenanceSchedule(
            vehicle_id=data['vehicle_id'],
            service_type=data['service_type'],
            scheduled_date=datetime.strptime(data['scheduled_date'], '%Y-%m-%d').date(),
            scheduled_mileage=data.get('scheduled_mileage'),
            vendor_id=data.get('vendor_id'),
            estimated_cost=data.get('estimated_cost'),
            service_notes=data.get('service_notes')
        )
        
        db.session.add(schedule)
        db.session.commit()
        
        schedule_data = schedule.to_dict()
        schedule_data['vehicle'] = vehicle.to_dict()
        
        return jsonify({
            'schedule': schedule_data,
            'message': 'Maintenance schedule created successfully'
        }), 201
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@maintenance_bp.route('/schedules/<schedule_id>', methods=['PUT'])
def update_maintenance_schedule(schedule_id):
    """Update maintenance schedule"""
    try:
        schedule = MaintenanceSchedule.query.get(schedule_id)
        if not schedule:
            return jsonify({'error': 'Maintenance schedule not found'}), 404
        
        data = request.get_json()
        
        if 'scheduled_date' in data:
            schedule.scheduled_date = datetime.strptime(data['scheduled_date'], '%Y-%m-%d').date()
        if 'scheduled_mileage' in data:
            schedule.scheduled_mileage = data['scheduled_mileage']
        if 'estimated_cost' in data:
            schedule.estimated_cost = data['estimated_cost']
        if 'actual_cost' in data:
            schedule.actual_cost = data['actual_cost']
        if 'status' in data:
            schedule.status = data['status']
        if 'completion_date' in data and data['completion_date']:
            schedule.completion_date = datetime.strptime(data['completion_date'], '%Y-%m-%d').date()
        if 'completion_mileage' in data:
            schedule.completion_mileage = data['completion_mileage']
        if 'service_notes' in data:
            schedule.service_notes = data['service_notes']
        if 'next_service_date' in data and data['next_service_date']:
            schedule.next_service_date = datetime.strptime(data['next_service_date'], '%Y-%m-%d').date()
        if 'next_service_mileage' in data:
            schedule.next_service_mileage = data['next_service_mileage']
        
        schedule.updated_at = datetime.utcnow()
        
        # If marking as completed, update vehicle's last service date
        if data.get('status') == 'completed' and schedule.completion_date:
            schedule.vehicle.last_service_date = schedule.completion_date
            if schedule.next_service_mileage:
                schedule.vehicle.next_service_due_mileage = schedule.next_service_mileage
        
        db.session.commit()
        
        return jsonify({
            'schedule': schedule.to_dict(),
            'message': 'Maintenance schedule updated successfully'
        }), 200
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@maintenance_bp.route('/damage-reports', methods=['GET'])
def get_damage_reports():
    """Get all damage reports with optional filtering"""
    try:
        page = request.args.get('page', 1, type=int)
        per_page = min(request.args.get('per_page', 20, type=int), 100)
        vehicle_id = request.args.get('vehicle_id', '')
        status = request.args.get('status', '')
        severity = request.args.get('severity', '')
        
        query = DamageReport.query
        
        if vehicle_id:
            query = query.filter(DamageReport.vehicle_id == vehicle_id)
        
        if status:
            query = query.filter(DamageReport.status == status)
        
        if severity:
            query = query.filter(DamageReport.damage_severity == severity)
        
        query = query.order_by(DamageReport.incident_date.desc())
        
        reports = query.paginate(
            page=page,
            per_page=per_page,
            error_out=False
        )
        
        report_list = []
        for report in reports.items:
            report_data = report.to_dict()
            report_data['vehicle'] = report.vehicle.to_dict()
            report_data['reporter'] = report.reporter.to_dict()
            if report.reservation:
                report_data['reservation'] = report.reservation.to_dict()
            report_list.append(report_data)
        
        return jsonify({
            'damage_reports': report_list,
            'pagination': {
                'page': reports.page,
                'pages': reports.pages,
                'per_page': reports.per_page,
                'total': reports.total,
                'has_next': reports.has_next,
                'has_prev': reports.has_prev
            }
        }), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@maintenance_bp.route('/damage-reports', methods=['POST'])
def create_damage_report():
    """Create new damage report"""
    try:
        data = request.get_json()
        
        required_fields = ['vehicle_id', 'reported_by', 'incident_date', 'damage_type', 'damage_severity', 'damage_description']
        for field in required_fields:
            if not data.get(field):
                return jsonify({'error': f'{field} is required'}), 400
        
        # Verify vehicle exists
        vehicle = Vehicle.query.get(data['vehicle_id'])
        if not vehicle:
            return jsonify({'error': 'Vehicle not found'}), 404
        
        # Verify reservation if provided
        if data.get('reservation_id'):
            reservation = Reservation.query.get(data['reservation_id'])
            if not reservation:
                return jsonify({'error': 'Reservation not found'}), 404
        
        report = DamageReport(
            vehicle_id=data['vehicle_id'],
            reservation_id=data.get('reservation_id'),
            reported_by=data['reported_by'],
            incident_date=datetime.fromisoformat(data['incident_date'].replace('Z', '+00:00')),
            damage_type=data['damage_type'],
            damage_severity=data['damage_severity'],
            damage_description=data['damage_description'],
            estimated_repair_cost=data.get('estimated_repair_cost'),
            insurance_claim_number=data.get('insurance_claim_number')
        )
        
        if data.get('photos'):
            report.set_photos(data['photos'])
        
        db.session.add(report)
        
        # Update vehicle status if damage is severe
        if data['damage_severity'] in ['major', 'total_loss']:
            vehicle.status = 'out_of_service'
            vehicle.updated_at = datetime.utcnow()
        
        db.session.commit()
        
        report_data = report.to_dict()
        report_data['vehicle'] = vehicle.to_dict()
        
        return jsonify({
            'damage_report': report_data,
            'message': 'Damage report created successfully'
        }), 201
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@maintenance_bp.route('/damage-reports/<report_id>', methods=['PUT'])
def update_damage_report(report_id):
    """Update damage report"""
    try:
        report = DamageReport.query.get(report_id)
        if not report:
            return jsonify({'error': 'Damage report not found'}), 404
        
        data = request.get_json()
        
        if 'damage_description' in data:
            report.damage_description = data['damage_description']
        if 'estimated_repair_cost' in data:
            report.estimated_repair_cost = data['estimated_repair_cost']
        if 'actual_repair_cost' in data:
            report.actual_repair_cost = data['actual_repair_cost']
        if 'insurance_claim_number' in data:
            report.insurance_claim_number = data['insurance_claim_number']
        if 'status' in data:
            report.status = data['status']
        if 'photos' in data:
            report.set_photos(data['photos'])
        
        report.updated_at = datetime.utcnow()
        
        # If repair is completed, update vehicle status
        if data.get('status') == 'completed':
            report.vehicle.status = 'available'
            report.vehicle.updated_at = datetime.utcnow()
        
        db.session.commit()
        
        return jsonify({
            'damage_report': report.to_dict(),
            'message': 'Damage report updated successfully'
        }), 200
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@maintenance_bp.route('/dashboard', methods=['GET'])
def maintenance_dashboard():
    """Get maintenance dashboard data"""
    try:
        today = date.today()
        
        # Overdue maintenance
        overdue_count = MaintenanceSchedule.query.filter(
            MaintenanceSchedule.scheduled_date < today,
            MaintenanceSchedule.status.in_(['scheduled', 'in_progress'])
        ).count()
        
        # Upcoming maintenance (next 7 days)
        upcoming_count = MaintenanceSchedule.query.filter(
            MaintenanceSchedule.scheduled_date >= today,
            MaintenanceSchedule.scheduled_date <= today.replace(day=today.day + 7),
            MaintenanceSchedule.status == 'scheduled'
        ).count()
        
        # Vehicles out of service
        out_of_service_count = Vehicle.query.filter(
            Vehicle.status.in_(['maintenance', 'out_of_service'])
        ).count()
        
        # Open damage reports
        open_damage_reports = DamageReport.query.filter(
            DamageReport.status.in_(['reported', 'assessed', 'approved', 'in_repair'])
        ).count()
        
        # Recent damage reports
        recent_damage_reports = DamageReport.query.order_by(
            DamageReport.created_at.desc()
        ).limit(5).all()
        
        return jsonify({
            'overdue_maintenance': overdue_count,
            'upcoming_maintenance': upcoming_count,
            'vehicles_out_of_service': out_of_service_count,
            'open_damage_reports': open_damage_reports,
            'recent_damage_reports': [report.to_dict() for report in recent_damage_reports]
        }), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

