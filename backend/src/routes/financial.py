from flask import Blueprint, request, jsonify
from src.models.user import db
from src.models.financial import Payment, Invoice, PricingRule
from src.models.reservation import Reservation
from src.models.customer import Customer
from datetime import datetime, date, timedelta
from sqlalchemy import func
import uuid

financial_bp = Blueprint('financial', __name__)

@financial_bp.route('/payments', methods=['GET'])
def get_payments():
    """Get all payments with optional filtering"""
    try:
        page = request.args.get('page', 1, type=int)
        per_page = min(request.args.get('per_page', 20, type=int), 100)
        status = request.args.get('status', '')
        customer_id = request.args.get('customer_id', '')
        payment_type = request.args.get('payment_type', '')
        
        query = Payment.query
        
        if status:
            query = query.filter(Payment.status == status)
        
        if customer_id:
            query = query.filter(Payment.customer_id == customer_id)
        
        if payment_type:
            query = query.filter(Payment.payment_type == payment_type)
        
        query = query.order_by(Payment.created_at.desc())
        
        payments = query.paginate(
            page=page,
            per_page=per_page,
            error_out=False
        )
        
        payment_list = []
        for payment in payments.items:
            payment_data = payment.to_dict()
            payment_data['customer'] = payment.customer.to_dict()
            if payment.reservation:
                payment_data['reservation'] = payment.reservation.to_dict()
            payment_list.append(payment_data)
        
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

@financial_bp.route('/payments', methods=['POST'])
def create_payment():
    """Create new payment"""
    try:
        data = request.get_json()
        
        required_fields = ['customer_id', 'payment_type', 'payment_method', 'amount']
        for field in required_fields:
            if not data.get(field):
                return jsonify({'error': f'{field} is required'}), 400
        
        # Verify customer exists
        customer = Customer.query.get(data['customer_id'])
        if not customer:
            return jsonify({'error': 'Customer not found'}), 404
        
        # Verify reservation if provided
        if data.get('reservation_id'):
            reservation = Reservation.query.get(data['reservation_id'])
            if not reservation:
                return jsonify({'error': 'Reservation not found'}), 404
        
        payment = Payment(
            reservation_id=data.get('reservation_id'),
            customer_id=data['customer_id'],
            payment_type=data['payment_type'],
            payment_method=data['payment_method'],
            amount=data['amount'],
            currency=data.get('currency', 'USD'),
            transaction_id=data.get('transaction_id'),
            status=data.get('status', 'pending'),
            notes=data.get('notes')
        )
        
        if data.get('gateway_response'):
            payment.set_gateway_response(data['gateway_response'])
        
        if data.get('status') == 'completed':
            payment.processed_at = datetime.utcnow()
        
        db.session.add(payment)
        db.session.commit()
        
        return jsonify({
            'payment': payment.to_dict(),
            'message': 'Payment created successfully'
        }), 201
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@financial_bp.route('/payments/<payment_id>/refund', methods=['POST'])
def refund_payment(payment_id):
    """Process payment refund"""
    try:
        payment = Payment.query.get(payment_id)
        if not payment:
            return jsonify({'error': 'Payment not found'}), 404
        
        if payment.status != 'completed':
            return jsonify({'error': 'Only completed payments can be refunded'}), 400
        
        data = request.get_json()
        refund_amount = data.get('refund_amount', payment.amount)
        
        if float(refund_amount) > float(payment.amount):
            return jsonify({'error': 'Refund amount cannot exceed payment amount'}), 400
        
        payment.status = 'refunded'
        payment.refund_amount = refund_amount
        payment.refunded_at = datetime.utcnow()
        payment.updated_at = datetime.utcnow()
        
        db.session.commit()
        
        return jsonify({
            'payment': payment.to_dict(),
            'message': 'Payment refunded successfully'
        }), 200
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@financial_bp.route('/invoices', methods=['GET'])
def get_invoices():
    """Get all invoices with optional filtering"""
    try:
        page = request.args.get('page', 1, type=int)
        per_page = min(request.args.get('per_page', 20, type=int), 100)
        status = request.args.get('status', '')
        customer_id = request.args.get('customer_id', '')
        
        query = Invoice.query
        
        if status:
            query = query.filter(Invoice.status == status)
        
        if customer_id:
            query = query.filter(Invoice.customer_id == customer_id)
        
        query = query.order_by(Invoice.created_at.desc())
        
        invoices = query.paginate(
            page=page,
            per_page=per_page,
            error_out=False
        )
        
        invoice_list = []
        for invoice in invoices.items:
            invoice_data = invoice.to_dict()
            invoice_data['customer'] = invoice.customer.to_dict()
            invoice_data['reservation'] = invoice.reservation.to_dict()
            invoice_list.append(invoice_data)
        
        return jsonify({
            'invoices': invoice_list,
            'pagination': {
                'page': invoices.page,
                'pages': invoices.pages,
                'per_page': invoices.per_page,
                'total': invoices.total,
                'has_next': invoices.has_next,
                'has_prev': invoices.has_prev
            }
        }), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@financial_bp.route('/invoices', methods=['POST'])
def create_invoice():
    """Create new invoice"""
    try:
        data = request.get_json()
        
        required_fields = ['reservation_id', 'customer_id', 'due_date', 'subtotal', 'total_amount', 'line_items']
        for field in required_fields:
            if not data.get(field):
                return jsonify({'error': f'{field} is required'}), 400
        
        # Verify customer and reservation exist
        customer = Customer.query.get(data['customer_id'])
        reservation = Reservation.query.get(data['reservation_id'])
        
        if not customer or not reservation:
            return jsonify({'error': 'Customer or reservation not found'}), 404
        
        # Generate invoice number
        invoice_number = f'INV{str(uuid.uuid4())[:8].upper()}'
        
        invoice = Invoice(
            invoice_number=invoice_number,
            reservation_id=data['reservation_id'],
            customer_id=data['customer_id'],
            due_date=datetime.strptime(data['due_date'], '%Y-%m-%d').date(),
            subtotal=data['subtotal'],
            tax_amount=data.get('tax_amount', 0.00),
            total_amount=data['total_amount'],
            payment_terms=data.get('payment_terms'),
            notes=data.get('notes')
        )
        
        invoice.set_line_items(data['line_items'])
        
        if data.get('billing_address'):
            invoice.set_billing_address(data['billing_address'])
        
        db.session.add(invoice)
        db.session.commit()
        
        return jsonify({
            'invoice': invoice.to_dict(),
            'message': 'Invoice created successfully'
        }), 201
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

@financial_bp.route('/reports/revenue', methods=['GET'])
def revenue_report():
    """Generate revenue report"""
    try:
        start_date = request.args.get('start_date')
        end_date = request.args.get('end_date')
        
        if not start_date or not end_date:
            # Default to last 30 days
            end_date = date.today()
            start_date = end_date - timedelta(days=30)
        else:
            start_date = datetime.strptime(start_date, '%Y-%m-%d').date()
            end_date = datetime.strptime(end_date, '%Y-%m-%d').date()
        
        # Total revenue from completed payments
        total_revenue = db.session.query(func.sum(Payment.amount)).filter(
            Payment.status == 'completed',
            Payment.processed_at >= start_date,
            Payment.processed_at <= end_date
        ).scalar() or 0
        
        # Revenue by payment type
        revenue_by_type = db.session.query(
            Payment.payment_type,
            func.sum(Payment.amount).label('total')
        ).filter(
            Payment.status == 'completed',
            Payment.processed_at >= start_date,
            Payment.processed_at <= end_date
        ).group_by(Payment.payment_type).all()
        
        # Daily revenue
        daily_revenue = db.session.query(
            func.date(Payment.processed_at).label('date'),
            func.sum(Payment.amount).label('total')
        ).filter(
            Payment.status == 'completed',
            Payment.processed_at >= start_date,
            Payment.processed_at <= end_date
        ).group_by(func.date(Payment.processed_at)).all()
        
        # Total reservations
        total_reservations = Reservation.query.filter(
            Reservation.pickup_datetime >= start_date,
            Reservation.pickup_datetime <= end_date
        ).count()
        
        # Completed reservations
        completed_reservations = Reservation.query.filter(
            Reservation.pickup_datetime >= start_date,
            Reservation.pickup_datetime <= end_date,
            Reservation.status == 'completed'
        ).count()
        
        return jsonify({
            'period': {
                'start_date': start_date.isoformat(),
                'end_date': end_date.isoformat()
            },
            'total_revenue': float(total_revenue),
            'total_reservations': total_reservations,
            'completed_reservations': completed_reservations,
            'completion_rate': (completed_reservations / total_reservations * 100) if total_reservations > 0 else 0,
            'revenue_by_type': [{'type': r[0], 'total': float(r[1])} for r in revenue_by_type],
            'daily_revenue': [{'date': r[0].isoformat(), 'total': float(r[1])} for r in daily_revenue]
        }), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@financial_bp.route('/pricing-rules', methods=['GET'])
def get_pricing_rules():
    """Get all pricing rules"""
    try:
        rules = PricingRule.query.filter_by(is_active=True).order_by(PricingRule.priority.desc()).all()
        return jsonify({
            'pricing_rules': [rule.to_dict() for rule in rules]
        }), 200
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@financial_bp.route('/pricing-rules', methods=['POST'])
def create_pricing_rule():
    """Create new pricing rule"""
    try:
        data = request.get_json()
        
        required_fields = ['rule_name', 'rule_type']
        for field in required_fields:
            if not data.get(field):
                return jsonify({'error': f'{field} is required'}), 400
        
        rule = PricingRule(
            rule_name=data['rule_name'],
            rule_type=data['rule_type'],
            category_id=data.get('category_id'),
            location_id=data.get('location_id'),
            start_date=datetime.strptime(data['start_date'], '%Y-%m-%d').date() if data.get('start_date') else None,
            end_date=datetime.strptime(data['end_date'], '%Y-%m-%d').date() if data.get('end_date') else None,
            day_of_week=data.get('day_of_week'),
            multiplier=data.get('multiplier', 1.000),
            fixed_adjustment=data.get('fixed_adjustment', 0.00),
            minimum_rental_days=data.get('minimum_rental_days'),
            maximum_rental_days=data.get('maximum_rental_days'),
            priority=data.get('priority', 0)
        )
        
        db.session.add(rule)
        db.session.commit()
        
        return jsonify({
            'pricing_rule': rule.to_dict(),
            'message': 'Pricing rule created successfully'
        }), 201
        
    except Exception as e:
        db.session.rollback()
        return jsonify({'error': str(e)}), 500

