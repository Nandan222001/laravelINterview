# AWS Lambda Code Examples

## 1. Lambda Function with S3 Event Processing

```python
import json
import boto3
import os
from PIL import Image
from io import BytesIO

s3_client = boto3.client('s3')

def lambda_handler(event, context):
    """
    Process S3 object creation events and generate thumbnails
    Demonstrates cold start optimization with global clients
    """
    
    for record in event['Records']:
        # Extract S3 bucket and key from event
        bucket = record['s3']['bucket']['name']
        key = record['s3']['object']['key']
        
        # Skip if already a thumbnail
        if 'thumbnails/' in key:
            continue
        
        try:
            # Download image from S3
            response = s3_client.get_object(Bucket=bucket, Key=key)
            image_data = response['Body'].read()
            
            # Generate thumbnail
            img = Image.open(BytesIO(image_data))
            img.thumbnail((200, 200))
            
            # Save thumbnail to buffer
            buffer = BytesIO()
            img.save(buffer, format=img.format)
            buffer.seek(0)
            
            # Upload thumbnail to S3
            thumbnail_key = f"thumbnails/{key}"
            s3_client.put_object(
                Bucket=bucket,
                Key=thumbnail_key,
                Body=buffer,
                ContentType=response['ContentType']
            )
            
            print(f"Generated thumbnail: {thumbnail_key}")
            
        except Exception as e:
            print(f"Error processing {key}: {str(e)}")
            raise
    
    return {
        'statusCode': 200,
        'body': json.dumps('Thumbnails generated successfully')
    }
```

## 2. Lambda with DynamoDB Stream Processing

```python
import json
import boto3
from decimal import Decimal

# Initialize clients globally for connection reuse
dynamodb = boto3.resource('dynamodb')
sns_client = boto3.client('sns')

NOTIFICATION_TOPIC_ARN = os.environ['NOTIFICATION_TOPIC_ARN']

def lambda_handler(event, context):
    """
    Process DynamoDB Stream events for real-time data replication
    Demonstrates event filtering and partial batch failure handling
    """
    
    batch_item_failures = []
    
    for record in event['Records']:
        try:
            if record['eventName'] == 'INSERT':
                process_new_record(record)
            elif record['eventName'] == 'MODIFY':
                process_updated_record(record)
            elif record['eventName'] == 'REMOVE':
                process_deleted_record(record)
        except Exception as e:
            print(f"Error processing record: {str(e)}")
            # Add to batch item failures for retry
            batch_item_failures.append({
                'itemIdentifier': record['dynamodb']['SequenceNumber']
            })
    
    # Return partial batch failure response
    return {
        'batchItemFailures': batch_item_failures
    }

def process_new_record(record):
    """Process newly inserted record"""
    new_image = record['dynamodb']['NewImage']
    
    # Send notification
    sns_client.publish(
        TopicArn=NOTIFICATION_TOPIC_ARN,
        Message=json.dumps(new_image),
        Subject='New Record Created'
    )

def process_updated_record(record):
    """Process updated record with change tracking"""
    old_image = record['dynamodb'].get('OldImage', {})
    new_image = record['dynamodb']['NewImage']
    
    # Detect significant changes
    if old_image.get('status') != new_image.get('status'):
        print(f"Status changed from {old_image.get('status')} to {new_image.get('status')}")

def process_deleted_record(record):
    """Process deleted record"""
    old_image = record['dynamodb']['OldImage']
    print(f"Record deleted: {old_image}")
```

## 3. Lambda with SQS Batch Processing

```python
import json
import boto3
from typing import List, Dict

sqs_client = boto3.client('sqs')

def lambda_handler(event, context):
    """
    Process SQS messages in batches with partial batch failure handling
    Demonstrates idempotent processing and error handling
    """
    
    batch_item_failures = []
    processed_ids = set()
    
    for record in event['Records']:
        message_id = record['messageId']
        
        try:
            # Parse message body
            body = json.loads(record['body'])
            
            # Implement idempotency check
            if is_already_processed(body['transaction_id']):
                print(f"Skipping already processed transaction: {body['transaction_id']}")
                continue
            
            # Process the message
            process_transaction(body)
            
            # Mark as processed
            processed_ids.add(body['transaction_id'])
            
        except Exception as e:
            print(f"Error processing message {message_id}: {str(e)}")
            batch_item_failures.append({
                'itemIdentifier': message_id
            })
    
    return {
        'batchItemFailures': batch_item_failures
    }

def is_already_processed(transaction_id: str) -> bool:
    """Check if transaction was already processed (idempotency)"""
    # Implementation using DynamoDB or Redis
    return False

def process_transaction(transaction: Dict):
    """Process transaction with business logic"""
    print(f"Processing transaction: {transaction['transaction_id']}")
    # Business logic here
```

## 4. Lambda Cold Start Optimization

```python
import json
import os
import time
from functools import lru_cache

# Initialize heavy resources outside handler (initialization phase)
# These will be reused across warm invocations

import boto3
from sqlalchemy import create_engine, pool

# Database connection pool (reused across invocations)
db_engine = create_engine(
    os.environ['DATABASE_URL'],
    poolclass=pool.NullPool,  # Important for Lambda
    pool_pre_ping=True,
    connect_args={'connect_timeout': 5}
)

# AWS clients (reused across invocations)
s3_client = boto3.client('s3')
dynamodb = boto3.resource('dynamodb')

# Cache expensive operations
@lru_cache(maxsize=128)
def get_config(config_key: str):
    """Cached configuration retrieval"""
    table = dynamodb.Table(os.environ['CONFIG_TABLE'])
    response = table.get_item(Key={'key': config_key})
    return response.get('Item', {}).get('value')

def lambda_handler(event, context):
    """
    Optimized Lambda handler demonstrating cold start mitigation
    """
    start_time = time.time()
    
    # Check if this is a cold start
    is_cold_start = not hasattr(lambda_handler, 'invocation_count')
    if is_cold_start:
        lambda_handler.invocation_count = 0
        print("COLD START detected")
    
    lambda_handler.invocation_count += 1
    
    # Use cached configuration
    api_key = get_config('api_key')
    
    # Use connection pool
    with db_engine.connect() as conn:
        result = conn.execute("SELECT COUNT(*) FROM users")
        user_count = result.scalar()
    
    execution_time = (time.time() - start_time) * 1000
    
    return {
        'statusCode': 200,
        'body': json.dumps({
            'message': 'Success',
            'cold_start': is_cold_start,
            'invocation_count': lambda_handler.invocation_count,
            'execution_time_ms': execution_time,
            'user_count': user_count
        })
    }
```

## 5. Lambda Layer Structure

```python
# layer/python/database_utils.py
"""
Lambda layer for shared database utilities
Package as: layer/python/database_utils.py
"""

import os
from contextlib import contextmanager
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker, scoped_session

class DatabaseConnection:
    """Singleton database connection for Lambda"""
    
    _instance = None
    _engine = None
    _session_factory = None
    
    def __new__(cls):
        if cls._instance is None:
            cls._instance = super().__new__(cls)
            cls._engine = create_engine(
                os.environ['DATABASE_URL'],
                pool_pre_ping=True,
                pool_recycle=3600,
                pool_size=1,  # Single connection for Lambda
                max_overflow=0
            )
            cls._session_factory = scoped_session(
                sessionmaker(bind=cls._engine)
            )
        return cls._instance
    
    @contextmanager
    def get_session(self):
        """Context manager for database sessions"""
        session = self._session_factory()
        try:
            yield session
            session.commit()
        except Exception:
            session.rollback()
            raise
        finally:
            session.close()

# layer/python/auth_utils.py
"""
Lambda layer for JWT authentication
"""

import jwt
import os
from datetime import datetime, timedelta
from functools import wraps

def verify_jwt_token(token: str) -> dict:
    """Verify JWT token and return payload"""
    try:
        secret_key = os.environ['JWT_SECRET_KEY']
        payload = jwt.decode(token, secret_key, algorithms=['HS256'])
        return payload
    except jwt.ExpiredSignatureError:
        raise Exception('Token has expired')
    except jwt.InvalidTokenError:
        raise Exception('Invalid token')

def require_auth(func):
    """Decorator for Lambda functions requiring authentication"""
    @wraps(func)
    def wrapper(event, context):
        # Extract token from Authorization header
        token = event.get('headers', {}).get('Authorization', '').replace('Bearer ', '')
        
        if not token:
            return {
                'statusCode': 401,
                'body': json.dumps({'error': 'No authorization token provided'})
            }
        
        try:
            # Verify token and add user info to event
            user_info = verify_jwt_token(token)
            event['user'] = user_info
            
            # Call the original function
            return func(event, context)
        except Exception as e:
            return {
                'statusCode': 401,
                'body': json.dumps({'error': str(e)})
            }
    
    return wrapper
```

## 6. API Gateway Lambda Authorizer

```python
import json
import jwt
import os
from typing import Dict

def lambda_handler(event, context):
    """
    Custom Lambda authorizer for API Gateway
    Validates JWT tokens and returns IAM policy
    """
    
    token = event['authorizationToken'].replace('Bearer ', '')
    method_arn = event['methodArn']
    
    try:
        # Verify JWT token
        secret_key = os.environ['JWT_SECRET_KEY']
        payload = jwt.decode(token, secret_key, algorithms=['HS256'])
        
        # Extract user information
        user_id = payload['user_id']
        user_role = payload.get('role', 'user')
        
        # Generate IAM policy
        policy = generate_policy(user_id, 'Allow', method_arn, {
            'user_id': user_id,
            'role': user_role
        })
        
        return policy
        
    except jwt.ExpiredSignatureError:
        raise Exception('Unauthorized: Token expired')
    except jwt.InvalidTokenError:
        raise Exception('Unauthorized: Invalid token')
    except Exception as e:
        print(f"Authorization error: {str(e)}")
        raise Exception('Unauthorized')

def generate_policy(principal_id: str, effect: str, resource: str, context: Dict = None):
    """
    Generate IAM policy for API Gateway
    """
    policy = {
        'principalId': principal_id,
        'policyDocument': {
            'Version': '2012-10-17',
            'Statement': [
                {
                    'Action': 'execute-api:Invoke',
                    'Effect': effect,
                    'Resource': resource
                }
            ]
        }
    }
    
    # Add context (available in downstream Lambda via event['requestContext']['authorizer'])
    if context:
        policy['context'] = context
    
    return policy
```

## 7. Lambda with Provisioned Concurrency

```python
import json
import time

# Initialization code runs when provisioned concurrency starts
print("Lambda initialization - runs once for provisioned instances")

# Pre-warm connections and load resources
import boto3
db_client = boto3.client('rds-data')

# Pre-load configuration or ML models
config = load_configuration()

def lambda_handler(event, context):
    """
    Lambda with provisioned concurrency - no cold starts
    """
    
    # Check if using provisioned concurrency
    is_provisioned = context.memory_limit_in_mb > 0 and \
                    hasattr(context, 'invoked_function_arn')
    
    # Process request immediately with warm resources
    result = process_request(event)
    
    return {
        'statusCode': 200,
        'body': json.dumps({
            'result': result,
            'provisioned_concurrency': is_provisioned
        })
    }

def load_configuration():
    """Load configuration during initialization"""
    # This runs once when container starts
    return {'api_endpoint': 'https://api.example.com'}

def process_request(event):
    """Process request using pre-warmed resources"""
    return {'processed': True}
```

## CloudFormation Template for Lambda with Layers

```yaml
AWSTemplateFormatVersion: '2010-09-09'
Transform: AWS::Serverless-2016-10-31

Resources:
  # Lambda Layer
  CommonUtilitiesLayer:
    Type: AWS::Serverless::LayerVersion
    Properties:
      LayerName: common-utilities
      Description: Shared utilities for Lambda functions
      ContentUri: ./layers/common-utilities/
      CompatibleRuntimes:
        - python3.11
      RetentionPolicy: Retain

  # Lambda Function with Layer
  ProcessingFunction:
    Type: AWS::Serverless::Function
    Properties:
      FunctionName: processing-function
      Handler: index.lambda_handler
      Runtime: python3.11
      CodeUri: ./src/
      MemorySize: 512
      Timeout: 30
      Layers:
        - !Ref CommonUtilitiesLayer
      Environment:
        Variables:
          DATABASE_URL: !Ref DatabaseUrl
          JWT_SECRET_KEY: !Ref JwtSecretKey
      Events:
        ApiEvent:
          Type: Api
          Properties:
            Path: /process
            Method: post

  # Lambda with Provisioned Concurrency
  HighPerformanceFunction:
    Type: AWS::Serverless::Function
    Properties:
      FunctionName: high-performance-function
      Handler: index.lambda_handler
      Runtime: python3.11
      CodeUri: ./src/
      MemorySize: 1024
      Timeout: 30
      ProvisionedConcurrencyConfig:
        ProvisionedConcurrentExecutions: 5
```
