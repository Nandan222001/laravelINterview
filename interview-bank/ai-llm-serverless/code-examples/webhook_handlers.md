# Payment Webhook Handlers (Razorpay & Stripe)

## 1. Razorpay Webhook Handler (Lambda)

```python
import json
import hmac
import hashlib
import os
import boto3
from datetime import datetime

dynamodb = boto3.resource('dynamodb')
sns_client = boto3.client('sns')

WEBHOOK_SECRET = os.environ['RAZORPAY_WEBHOOK_SECRET']
WEBHOOK_EVENTS_TABLE = os.environ['WEBHOOK_EVENTS_TABLE']
NOTIFICATION_TOPIC = os.environ['NOTIFICATION_TOPIC_ARN']

def lambda_handler(event, context):
    """
    Process Razorpay webhook events
    Implements signature verification and idempotent processing
    """
    
    try:
        # Extract signature from headers
        signature = event['headers'].get('x-razorpay-signature', '')
        
        # Get request body
        body = event['body']
        
        # Verify webhook signature
        if not verify_razorpay_signature(body, signature):
            return {
                'statusCode': 401,
                'body': json.dumps({'error': 'Invalid signature'})
            }
        
        # Parse webhook payload
        payload = json.loads(body)
        event_type = payload['event']
        event_id = payload['payload']['payment']['entity']['id']
        
        # Check for duplicate (idempotency)
        if is_duplicate_event(event_id, event_type):
            print(f"Duplicate event: {event_id}")
            return {
                'statusCode': 200,
                'body': json.dumps({'message': 'Already processed'})
            }
        
        # Store webhook event for audit
        store_webhook_event(event_id, event_type, payload)
        
        # Route to appropriate handler
        if event_type == 'payment.captured':
            handle_payment_captured(payload)
        elif event_type == 'payment.failed':
            handle_payment_failed(payload)
        elif event_type == 'refund.created':
            handle_refund_created(payload)
        elif event_type == 'subscription.charged':
            handle_subscription_charged(payload)
        else:
            print(f"Unhandled event type: {event_type}")
        
        # Return 200 immediately (async processing)
        return {
            'statusCode': 200,
            'body': json.dumps({'message': 'Webhook processed'})
        }
        
    except Exception as e:
        print(f"Error processing webhook: {str(e)}")
        # Return 200 to prevent Razorpay retries for application errors
        return {
            'statusCode': 200,
            'body': json.dumps({'error': str(e)})
        }


def verify_razorpay_signature(body: str, signature: str) -> bool:
    """
    Verify Razorpay webhook signature using HMAC SHA256
    """
    expected_signature = hmac.new(
        WEBHOOK_SECRET.encode('utf-8'),
        body.encode('utf-8'),
        hashlib.sha256
    ).hexdigest()
    
    return hmac.compare_digest(expected_signature, signature)


def is_duplicate_event(event_id: str, event_type: str) -> bool:
    """
    Check if event was already processed (idempotency check)
    """
    table = dynamodb.Table(WEBHOOK_EVENTS_TABLE)
    
    try:
        response = table.get_item(
            Key={
                'event_id': event_id,
                'event_type': event_type
            }
        )
        return 'Item' in response
    except Exception as e:
        print(f"Error checking duplicate: {str(e)}")
        return False


def store_webhook_event(event_id: str, event_type: str, payload: dict):
    """
    Store webhook event for audit trail and idempotency
    """
    table = dynamodb.Table(WEBHOOK_EVENTS_TABLE)
    
    table.put_item(
        Item={
            'event_id': event_id,
            'event_type': event_type,
            'payload': json.dumps(payload),
            'processed_at': datetime.utcnow().isoformat(),
            'ttl': int((datetime.utcnow().timestamp() + 90 * 24 * 3600))  # 90 days
        }
    )


def handle_payment_captured(payload: dict):
    """
    Handle successful payment capture
    """
    payment = payload['payload']['payment']['entity']
    
    # Extract payment details
    payment_id = payment['id']
    order_id = payment['order_id']
    amount = payment['amount'] / 100  # Convert paise to rupees
    currency = payment['currency']
    
    print(f"Payment captured: {payment_id}, Order: {order_id}, Amount: {amount} {currency}")
    
    # Update order status in database
    # Send confirmation email/SMS
    # Trigger fulfillment process
    
    # Publish to SNS for downstream processing
    sns_client.publish(
        TopicArn=NOTIFICATION_TOPIC,
        Message=json.dumps({
            'event': 'payment_captured',
            'payment_id': payment_id,
            'order_id': order_id,
            'amount': amount,
            'currency': currency
        }),
        Subject='Payment Captured'
    )


def handle_payment_failed(payload: dict):
    """
    Handle failed payment
    """
    payment = payload['payload']['payment']['entity']
    
    payment_id = payment['id']
    error_code = payment.get('error_code')
    error_description = payment.get('error_description')
    
    print(f"Payment failed: {payment_id}, Error: {error_code} - {error_description}")
    
    # Notify customer
    # Log for analytics
    # Update order status


def handle_refund_created(payload: dict):
    """
    Handle refund creation
    """
    refund = payload['payload']['refund']['entity']
    
    refund_id = refund['id']
    payment_id = refund['payment_id']
    amount = refund['amount'] / 100
    
    print(f"Refund created: {refund_id}, Payment: {payment_id}, Amount: {amount}")
    
    # Update order status
    # Notify customer
    # Adjust inventory if needed


def handle_subscription_charged(payload: dict):
    """
    Handle subscription charge
    """
    subscription = payload['payload']['subscription']['entity']
    
    subscription_id = subscription['id']
    status = subscription['status']
    
    print(f"Subscription charged: {subscription_id}, Status: {status}")
    
    # Update subscription status
    # Grant access to service
    # Send receipt
```

## 2. Stripe Webhook Handler (Lambda)

```python
import json
import stripe
import os
import boto3
from datetime import datetime

stripe.api_key = os.environ['STRIPE_SECRET_KEY']
WEBHOOK_SECRET = os.environ['STRIPE_WEBHOOK_SECRET']

dynamodb = boto3.resource('dynamodb')
sqs_client = boto3.client('sqs')

WEBHOOK_EVENTS_TABLE = os.environ['WEBHOOK_EVENTS_TABLE']
PROCESSING_QUEUE_URL = os.environ['PROCESSING_QUEUE_URL']

def lambda_handler(event, context):
    """
    Process Stripe webhook events
    Implements signature verification and async processing
    """
    
    try:
        # Get webhook signature
        sig_header = event['headers'].get('stripe-signature', '')
        body = event['body']
        
        # Verify webhook signature
        try:
            stripe_event = stripe.Webhook.construct_event(
                body, sig_header, WEBHOOK_SECRET
            )
        except stripe.error.SignatureVerificationError as e:
            print(f"Invalid signature: {str(e)}")
            return {
                'statusCode': 400,
                'body': json.dumps({'error': 'Invalid signature'})
            }
        
        # Extract event details
        event_type = stripe_event['type']
        event_id = stripe_event['id']
        
        # Check for duplicate
        if is_duplicate_event(event_id):
            print(f"Duplicate event: {event_id}")
            return {
                'statusCode': 200,
                'body': json.dumps({'message': 'Already processed'})
            }
        
        # Store webhook event
        store_webhook_event(event_id, event_type, stripe_event)
        
        # Return 200 immediately and process async
        send_to_processing_queue(event_id, event_type, stripe_event)
        
        return {
            'statusCode': 200,
            'body': json.dumps({'received': True})
        }
        
    except Exception as e:
        print(f"Error processing webhook: {str(e)}")
        return {
            'statusCode': 500,
            'body': json.dumps({'error': str(e)})
        }


def is_duplicate_event(event_id: str) -> bool:
    """Check if Stripe event was already processed"""
    table = dynamodb.Table(WEBHOOK_EVENTS_TABLE)
    
    try:
        response = table.get_item(Key={'event_id': event_id})
        return 'Item' in response
    except:
        return False


def store_webhook_event(event_id: str, event_type: str, stripe_event: dict):
    """Store webhook event for audit and idempotency"""
    table = dynamodb.Table(WEBHOOK_EVENTS_TABLE)
    
    table.put_item(
        Item={
            'event_id': event_id,
            'event_type': event_type,
            'payload': json.dumps(stripe_event),
            'processed_at': datetime.utcnow().isoformat(),
            'status': 'received'
        }
    )


def send_to_processing_queue(event_id: str, event_type: str, stripe_event: dict):
    """Send event to SQS for async processing"""
    sqs_client.send_message(
        QueueUrl=PROCESSING_QUEUE_URL,
        MessageBody=json.dumps({
            'event_id': event_id,
            'event_type': event_type,
            'event_data': stripe_event
        }),
        MessageAttributes={
            'event_type': {
                'StringValue': event_type,
                'DataType': 'String'
            }
        }
    )


# Async processor Lambda (processes SQS messages)
def process_stripe_event_handler(event, context):
    """
    Process Stripe events from SQS queue
    Separate Lambda for actual business logic
    """
    
    for record in event['Records']:
        message_body = json.loads(record['body'])
        event_type = message_body['event_type']
        event_data = message_body['event_data']
        
        try:
            # Route to appropriate handler
            if event_type == 'checkout.session.completed':
                handle_checkout_completed(event_data)
            elif event_type == 'payment_intent.succeeded':
                handle_payment_succeeded(event_data)
            elif event_type == 'payment_intent.payment_failed':
                handle_payment_failed(event_data)
            elif event_type == 'customer.subscription.created':
                handle_subscription_created(event_data)
            elif event_type == 'customer.subscription.updated':
                handle_subscription_updated(event_data)
            elif event_type == 'customer.subscription.deleted':
                handle_subscription_deleted(event_data)
            elif event_type == 'charge.refunded':
                handle_charge_refunded(event_data)
            elif event_type == 'charge.dispute.created':
                handle_dispute_created(event_data)
            else:
                print(f"Unhandled event type: {event_type}")
                
        except Exception as e:
            print(f"Error processing event {event_type}: {str(e)}")
            raise  # Let SQS retry


def handle_checkout_completed(event_data: dict):
    """Handle completed checkout session"""
    session = event_data['data']['object']
    
    session_id = session['id']
    customer_id = session.get('customer')
    payment_status = session['payment_status']
    amount_total = session['amount_total'] / 100  # Convert cents to dollars
    
    print(f"Checkout completed: {session_id}, Amount: ${amount_total}, Status: {payment_status}")
    
    if payment_status == 'paid':
        # Fulfill order
        # Send confirmation email
        # Update inventory
        pass


def handle_payment_succeeded(event_data: dict):
    """Handle successful payment"""
    payment_intent = event_data['data']['object']
    
    payment_id = payment_intent['id']
    amount = payment_intent['amount'] / 100
    currency = payment_intent['currency']
    customer_id = payment_intent.get('customer')
    
    print(f"Payment succeeded: {payment_id}, Amount: {amount} {currency}")
    
    # Update order status
    # Grant access to product/service
    # Send receipt


def handle_payment_failed(event_data: dict):
    """Handle failed payment"""
    payment_intent = event_data['data']['object']
    
    payment_id = payment_intent['id']
    last_payment_error = payment_intent.get('last_payment_error', {})
    error_message = last_payment_error.get('message', 'Unknown error')
    
    print(f"Payment failed: {payment_id}, Error: {error_message}")
    
    # Notify customer
    # Retry payment if appropriate
    # Update order status


def handle_subscription_created(event_data: dict):
    """Handle new subscription"""
    subscription = event_data['data']['object']
    
    subscription_id = subscription['id']
    customer_id = subscription['customer']
    status = subscription['status']
    
    print(f"Subscription created: {subscription_id}, Customer: {customer_id}, Status: {status}")
    
    # Create subscription record
    # Grant access
    # Send welcome email


def handle_subscription_updated(event_data: dict):
    """Handle subscription update"""
    subscription = event_data['data']['object']
    previous_attributes = event_data.get('data', {}).get('previous_attributes', {})
    
    subscription_id = subscription['id']
    status = subscription['status']
    
    print(f"Subscription updated: {subscription_id}, New status: {status}")
    
    # Check what changed
    if 'status' in previous_attributes:
        old_status = previous_attributes['status']
        if old_status == 'active' and status == 'past_due':
            # Payment failed
            handle_payment_retry(subscription)
        elif status == 'canceled':
            # Subscription canceled
            revoke_access(subscription)


def handle_subscription_deleted(event_data: dict):
    """Handle subscription deletion"""
    subscription = event_data['data']['object']
    
    subscription_id = subscription['id']
    customer_id = subscription['customer']
    
    print(f"Subscription deleted: {subscription_id}")
    
    # Revoke access
    # Send cancellation confirmation
    # Gather feedback


def handle_charge_refunded(event_data: dict):
    """Handle refund"""
    charge = event_data['data']['object']
    
    charge_id = charge['id']
    amount_refunded = charge['amount_refunded'] / 100
    refunded = charge['refunded']
    
    print(f"Charge refunded: {charge_id}, Amount: ${amount_refunded}, Fully refunded: {refunded}")
    
    # Update order status
    # Restore inventory
    # Notify customer


def handle_dispute_created(event_data: dict):
    """Handle payment dispute"""
    dispute = event_data['data']['object']
    
    dispute_id = dispute['id']
    charge_id = dispute['charge']
    amount = dispute['amount'] / 100
    reason = dispute['reason']
    
    print(f"Dispute created: {dispute_id}, Charge: {charge_id}, Amount: ${amount}, Reason: {reason}")
    
    # Alert admin
    # Gather evidence
    # Respond to dispute
```

## 3. Webhook Security Best Practices

```python
import hmac
import hashlib
import time
from functools import wraps

def rate_limit_webhook(max_requests=100, window_seconds=60):
    """
    Rate limiting decorator for webhook endpoints
    """
    requests = {}
    
    def decorator(func):
        @wraps(func)
        def wrapper(event, context):
            # Get IP address
            ip = event['requestContext']['identity']['sourceIp']
            current_time = time.time()
            
            # Clean old entries
            requests[ip] = [
                req_time for req_time in requests.get(ip, [])
                if current_time - req_time < window_seconds
            ]
            
            # Check rate limit
            if len(requests.get(ip, [])) >= max_requests:
                return {
                    'statusCode': 429,
                    'body': json.dumps({'error': 'Rate limit exceeded'})
                }
            
            # Add current request
            requests.setdefault(ip, []).append(current_time)
            
            return func(event, context)
        
        return wrapper
    return decorator


def verify_webhook_timestamp(timestamp: str, tolerance: int = 300) -> bool:
    """
    Verify webhook timestamp to prevent replay attacks
    
    Args:
        timestamp: Unix timestamp from webhook header
        tolerance: Maximum age in seconds (default 5 minutes)
    """
    try:
        webhook_time = int(timestamp)
        current_time = int(time.time())
        
        # Check if timestamp is within tolerance
        if abs(current_time - webhook_time) > tolerance:
            return False
        
        return True
    except:
        return False


class WebhookValidator:
    """Centralized webhook validation"""
    
    @staticmethod
    def validate_razorpay(body: str, signature: str, secret: str) -> bool:
        """Validate Razorpay webhook"""
        expected = hmac.new(
            secret.encode('utf-8'),
            body.encode('utf-8'),
            hashlib.sha256
        ).hexdigest()
        return hmac.compare_digest(expected, signature)
    
    @staticmethod
    def validate_stripe(body: str, signature: str, secret: str) -> bool:
        """Validate Stripe webhook using official SDK"""
        try:
            stripe.Webhook.construct_event(body, signature, secret)
            return True
        except:
            return False
    
    @staticmethod
    def validate_paypal(body: dict, headers: dict, webhook_id: str) -> bool:
        """Validate PayPal webhook"""
        # PayPal webhook validation using transmission_id and transmission_sig
        # Implementation depends on PayPal SDK
        pass
```

## 4. Webhook Testing Utilities

```python
import requests
import json
import hmac
import hashlib

class WebhookTester:
    """Testing utility for webhook handlers"""
    
    @staticmethod
    def generate_razorpay_signature(payload: dict, secret: str) -> str:
        """Generate Razorpay webhook signature for testing"""
        body = json.dumps(payload)
        signature = hmac.new(
            secret.encode('utf-8'),
            body.encode('utf-8'),
            hashlib.sha256
        ).hexdigest()
        return signature
    
    @staticmethod
    def test_razorpay_webhook(url: str, payload: dict, secret: str):
        """Test Razorpay webhook endpoint"""
        body = json.dumps(payload)
        signature = WebhookTester.generate_razorpay_signature(payload, secret)
        
        headers = {
            'Content-Type': 'application/json',
            'x-razorpay-signature': signature
        }
        
        response = requests.post(url, data=body, headers=headers)
        print(f"Status: {response.status_code}")
        print(f"Response: {response.text}")
        return response
    
    @staticmethod
    def create_test_payment_event() -> dict:
        """Create test payment.captured event"""
        return {
            'entity': 'event',
            'account_id': 'acc_test123',
            'event': 'payment.captured',
            'contains': ['payment'],
            'payload': {
                'payment': {
                    'entity': {
                        'id': 'pay_test123',
                        'entity': 'payment',
                        'amount': 50000,
                        'currency': 'INR',
                        'status': 'captured',
                        'order_id': 'order_test123',
                        'method': 'card',
                        'captured': True,
                        'email': 'test@example.com',
                        'contact': '+919876543210'
                    }
                }
            },
            'created_at': int(time.time())
        }

# Usage
if __name__ == '__main__':
    tester = WebhookTester()
    
    # Test Razorpay webhook
    payload = tester.create_test_payment_event()
    tester.test_razorpay_webhook(
        url='https://your-api-gateway-url/webhooks/razorpay',
        payload=payload,
        secret='your_webhook_secret'
    )
```
