# DevOps, Cloud & Kubernetes Interview Questions

## Table of Contents
- [Kubernetes Architecture](#kubernetes-architecture)
- [CI/CD Pipelines](#cicd-pipelines)
- [Infrastructure as Code](#infrastructure-as-code)
- [Monitoring & Observability](#monitoring--observability)
- [Container Security](#container-security)

---

## Kubernetes Architecture

### Question 1: Designing a Production-Ready Kubernetes Deployment

**Difficulty**: ⭐⭐⭐⭐ Expert  
**Topic**: Kubernetes, Production Best Practices  
**Tags**: `kubernetes`, `deployment`, `high-availability`, `production`

#### Question

Design and implement a production-ready Kubernetes deployment for a stateless web application with:
- High availability across multiple zones
- Auto-scaling based on CPU and custom metrics
- Zero-downtime deployments
- Health checks and readiness probes
- Resource limits and requests
- Pod disruption budgets
- Network policies for security

#### Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Kubernetes Cluster                        │
│                                                               │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │   Zone A    │  │   Zone B    │  │   Zone C    │         │
│  │             │  │             │  │             │         │
│  │  ┌───────┐  │  │  ┌───────┐  │  │  ┌───────┐  │         │
│  │  │ Pod 1 │  │  │  │ Pod 2 │  │  │  │ Pod 3 │  │         │
│  │  └───────┘  │  │  └───────┘  │  │  └───────┘  │         │
│  └─────────────┘  └─────────────┘  └─────────────┘         │
│         │                │                │                  │
│         └────────────────┼────────────────┘                  │
│                          │                                   │
│                  ┌───────────────┐                          │
│                  │    Service    │                          │
│                  │ Load Balancer │                          │
│                  └───────────────┘                          │
│                          │                                   │
│                  ┌───────────────┐                          │
│                  │    Ingress    │                          │
│                  │  Controller   │                          │
│                  └───────────────┘                          │
└─────────────────────────────────────────────────────────────┘
```

#### Solution

```yaml
# deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: web-app
  namespace: production
  labels:
    app: web-app
    version: v1.0.0
spec:
  replicas: 3
  
  # Rolling update strategy for zero-downtime
  strategy:
    type: RollingUpdate
    rollingUpdate:
      maxSurge: 1
      maxUnavailable: 0
  
  selector:
    matchLabels:
      app: web-app
  
  template:
    metadata:
      labels:
        app: web-app
        version: v1.0.0
      annotations:
        prometheus.io/scrape: "true"
        prometheus.io/port: "9090"
        prometheus.io/path: "/metrics"
    
    spec:
      # Spread pods across zones
      topologySpreadConstraints:
        - maxSkew: 1
          topologyKey: topology.kubernetes.io/zone
          whenUnsatisfiable: DoNotSchedule
          labelSelector:
            matchLabels:
              app: web-app
      
      # Anti-affinity to avoid same node
      affinity:
        podAntiAffinity:
          preferredDuringSchedulingIgnoredDuringExecution:
            - weight: 100
              podAffinityTerm:
                labelSelector:
                  matchExpressions:
                    - key: app
                      operator: In
                      values:
                        - web-app
                topologyKey: kubernetes.io/hostname
      
      # Security context
      securityContext:
        runAsNonRoot: true
        runAsUser: 1000
        fsGroup: 1000
        seccompProfile:
          type: RuntimeDefault
      
      containers:
        - name: app
          image: myregistry.io/web-app:v1.0.0
          imagePullPolicy: IfNotPresent
          
          # Resource limits and requests
          resources:
            requests:
              cpu: 100m
              memory: 128Mi
            limits:
              cpu: 500m
              memory: 512Mi
          
          # Environment variables
          env:
            - name: NODE_ENV
              value: production
            - name: PORT
              value: "8080"
            - name: DB_HOST
              valueFrom:
                secretKeyRef:
                  name: db-credentials
                  key: host
            - name: DB_PASSWORD
              valueFrom:
                secretKeyRef:
                  name: db-credentials
                  key: password
          
          # Ports
          ports:
            - name: http
              containerPort: 8080
              protocol: TCP
            - name: metrics
              containerPort: 9090
              protocol: TCP
          
          # Liveness probe - restart if unhealthy
          livenessProbe:
            httpGet:
              path: /health/live
              port: http
            initialDelaySeconds: 30
            periodSeconds: 10
            timeoutSeconds: 5
            failureThreshold: 3
            successThreshold: 1
          
          # Readiness probe - remove from service if not ready
          readinessProbe:
            httpGet:
              path: /health/ready
              port: http
            initialDelaySeconds: 10
            periodSeconds: 5
            timeoutSeconds: 3
            failureThreshold: 3
            successThreshold: 1
          
          # Startup probe - for slow-starting containers
          startupProbe:
            httpGet:
              path: /health/startup
              port: http
            initialDelaySeconds: 0
            periodSeconds: 10
            timeoutSeconds: 3
            failureThreshold: 30
          
          # Security context for container
          securityContext:
            allowPrivilegeEscalation: false
            readOnlyRootFilesystem: true
            capabilities:
              drop:
                - ALL
          
          # Volume mounts
          volumeMounts:
            - name: tmp
              mountPath: /tmp
            - name: cache
              mountPath: /app/cache
      
      # Volumes
      volumes:
        - name: tmp
          emptyDir: {}
        - name: cache
          emptyDir: {}
      
      # Image pull secrets
      imagePullSecrets:
        - name: registry-credentials

---
# service.yaml
apiVersion: v1
kind: Service
metadata:
  name: web-app
  namespace: production
  labels:
    app: web-app
spec:
  type: ClusterIP
  sessionAffinity: ClientIP
  sessionAffinityConfig:
    clientIP:
      timeoutSeconds: 10800
  ports:
    - name: http
      port: 80
      targetPort: http
      protocol: TCP
  selector:
    app: web-app

---
# hpa.yaml - Horizontal Pod Autoscaler
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: web-app
  namespace: production
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: web-app
  
  minReplicas: 3
  maxReplicas: 10
  
  metrics:
    # CPU-based scaling
    - type: Resource
      resource:
        name: cpu
        target:
          type: Utilization
          averageUtilization: 70
    
    # Memory-based scaling
    - type: Resource
      resource:
        name: memory
        target:
          type: Utilization
          averageUtilization: 80
    
    # Custom metric - requests per second
    - type: Pods
      pods:
        metric:
          name: http_requests_per_second
        target:
          type: AverageValue
          averageValue: "1000"
  
  behavior:
    scaleDown:
      stabilizationWindowSeconds: 300
      policies:
        - type: Percent
          value: 50
          periodSeconds: 60
    scaleUp:
      stabilizationWindowSeconds: 0
      policies:
        - type: Percent
          value: 100
          periodSeconds: 15
        - type: Pods
          value: 2
          periodSeconds: 15
      selectPolicy: Max

---
# pdb.yaml - Pod Disruption Budget
apiVersion: policy/v1
kind: PodDisruptionBudget
metadata:
  name: web-app
  namespace: production
spec:
  minAvailable: 2
  selector:
    matchLabels:
      app: web-app

---
# network-policy.yaml
apiVersion: networking.k8s.io/v1
kind: NetworkPolicy
metadata:
  name: web-app
  namespace: production
spec:
  podSelector:
    matchLabels:
      app: web-app
  
  policyTypes:
    - Ingress
    - Egress
  
  ingress:
    # Allow from ingress controller
    - from:
        - namespaceSelector:
            matchLabels:
              name: ingress-nginx
        - podSelector:
            matchLabels:
              app: ingress-nginx
      ports:
        - protocol: TCP
          port: 8080
    
    # Allow from monitoring
    - from:
        - namespaceSelector:
            matchLabels:
              name: monitoring
      ports:
        - protocol: TCP
          port: 9090
  
  egress:
    # Allow DNS
    - to:
        - namespaceSelector:
            matchLabels:
              name: kube-system
      ports:
        - protocol: UDP
          port: 53
    
    # Allow database
    - to:
        - podSelector:
            matchLabels:
              app: postgresql
      ports:
        - protocol: TCP
          port: 5432
    
    # Allow external HTTPS
    - to:
        - namespaceSelector: {}
      ports:
        - protocol: TCP
          port: 443
```

---

## CI/CD Pipelines

### Question 2: GitHub Actions Production Pipeline

**Difficulty**: ⭐⭐⭐ Advanced  
**Topic**: CI/CD, GitHub Actions  
**Tags**: `github-actions`, `cicd`, `automation`, `deployment`

#### Question

Implement a production-grade CI/CD pipeline using GitHub Actions with:
- Multi-stage builds
- Security scanning
- Automated testing
- Docker image building and pushing
- Kubernetes deployment
- Rollback capabilities

#### Solution

```yaml
# .github/workflows/deploy.yml
name: CI/CD Pipeline

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]
  workflow_dispatch:

env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}

jobs:
  # Job 1: Lint and Test
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        node-version: [18.x, 20.x]
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup Node.js ${{ matrix.node-version }}
        uses: actions/setup-node@v4
        with:
          node-version: ${{ matrix.node-version }}
          cache: 'npm'
      
      - name: Install dependencies
        run: npm ci
      
      - name: Run linter
        run: npm run lint
      
      - name: Run type check
        run: npm run type-check
      
      - name: Run unit tests
        run: npm run test:unit -- --coverage
      
      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage/coverage-final.json
          flags: unittests
          name: codecov-umbrella
      
      - name: Run integration tests
        run: npm run test:integration
        env:
          DATABASE_URL: postgresql://test:test@localhost:5432/test
      
      - name: Run e2e tests
        run: npm run test:e2e
  
  # Job 2: Security Scanning
  security:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Run Trivy vulnerability scanner
        uses: aquasecurity/trivy-action@master
        with:
          scan-type: 'fs'
          scan-ref: '.'
          format: 'sarif'
          output: 'trivy-results.sarif'
      
      - name: Upload Trivy results to GitHub Security
        uses: github/codeql-action/upload-sarif@v2
        with:
          sarif_file: 'trivy-results.sarif'
      
      - name: OWASP Dependency Check
        uses: dependency-check/Dependency-Check_Action@main
        with:
          project: 'web-app'
          path: '.'
          format: 'HTML'
      
      - name: SonarCloud Scan
        uses: SonarSource/sonarcloud-github-action@master
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
          SONAR_TOKEN: ${{ secrets.SONAR_TOKEN }}
  
  # Job 3: Build and Push Docker Image
  build:
    needs: [test, security]
    runs-on: ubuntu-latest
    
    permissions:
      contents: read
      packages: write
    
    outputs:
      image-tag: ${{ steps.meta.outputs.tags }}
      image-digest: ${{ steps.build.outputs.digest }}
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3
      
      - name: Log in to Container Registry
        uses: docker/login-action@v3
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}
      
      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}
          tags: |
            type=ref,event=branch
            type=ref,event=pr
            type=semver,pattern={{version}}
            type=semver,pattern={{major}}.{{minor}}
            type=sha,prefix={{branch}}-
            type=raw,value=latest,enable={{is_default_branch}}
      
      - name: Build and push Docker image
        id: build
        uses: docker/build-push-action@v5
        with:
          context: .
          push: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
          build-args: |
            BUILD_DATE=${{ github.event.head_commit.timestamp }}
            VCS_REF=${{ github.sha }}
            VERSION=${{ steps.meta.outputs.version }}
      
      - name: Scan image with Trivy
        uses: aquasecurity/trivy-action@master
        with:
          image-ref: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:${{ steps.meta.outputs.version }}
          format: 'sarif'
          output: 'trivy-image-results.sarif'
      
      - name: Sign image with cosign
        run: |
          cosign sign --yes ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}@${{ steps.build.outputs.digest }}
        env:
          COSIGN_EXPERIMENTAL: 1
  
  # Job 4: Deploy to Staging
  deploy-staging:
    needs: build
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/develop'
    
    environment:
      name: staging
      url: https://staging.example.com
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup kubectl
        uses: azure/setup-kubectl@v3
        with:
          version: 'v1.28.0'
      
      - name: Configure kubectl
        run: |
          echo "${{ secrets.KUBECONFIG_STAGING }}" | base64 -d > kubeconfig
          export KUBECONFIG=kubeconfig
      
      - name: Deploy to Kubernetes
        run: |
          kubectl set image deployment/web-app \
            app=${{ needs.build.outputs.image-tag }} \
            --namespace=staging \
            --record
          
          kubectl rollout status deployment/web-app \
            --namespace=staging \
            --timeout=5m
      
      - name: Run smoke tests
        run: |
          npm run test:smoke -- --base-url=https://staging.example.com
      
      - name: Notify deployment
        uses: 8398a7/action-slack@v3
        with:
          status: ${{ job.status }}
          text: 'Staging deployment completed'
          webhook_url: ${{ secrets.SLACK_WEBHOOK }}
  
  # Job 5: Deploy to Production
  deploy-production:
    needs: build
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    
    environment:
      name: production
      url: https://example.com
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup kubectl
        uses: azure/setup-kubectl@v3
      
      - name: Configure kubectl
        run: |
          echo "${{ secrets.KUBECONFIG_PROD }}" | base64 -d > kubeconfig
          export KUBECONFIG=kubeconfig
      
      - name: Blue-Green Deployment
        run: |
          # Deploy to green environment
          kubectl set image deployment/web-app-green \
            app=${{ needs.build.outputs.image-tag }} \
            --namespace=production \
            --record
          
          # Wait for rollout
          kubectl rollout status deployment/web-app-green \
            --namespace=production \
            --timeout=10m
          
          # Run health checks
          ./scripts/health-check.sh https://green.example.com
          
          # Switch traffic
          kubectl patch service web-app \
            -p '{"spec":{"selector":{"version":"green"}}}' \
            --namespace=production
      
      - name: Smoke tests
        run: npm run test:smoke -- --base-url=https://example.com
      
      - name: Rollback on failure
        if: failure()
        run: |
          kubectl patch service web-app \
            -p '{"spec":{"selector":{"version":"blue"}}}' \
            --namespace=production
          
          kubectl rollout undo deployment/web-app-green \
            --namespace=production
      
      - name: Create release
        uses: actions/create-release@v1
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        with:
          tag_name: v${{ github.run_number }}
          release_name: Release v${{ github.run_number }}
          body: |
            Automated release from commit ${{ github.sha }}
            Image: ${{ needs.build.outputs.image-tag }}
          draft: false
          prerelease: false
```

---

## Infrastructure as Code

### Question 3: Terraform AWS Infrastructure

**Difficulty**: ⭐⭐⭐ Advanced  
**Topic**: Terraform, AWS, IaC  
**Tags**: `terraform`, `aws`, `infrastructure`, `automation`

#### Question

Design and implement Terraform modules for a production AWS infrastructure including:
- Multi-AZ VPC with public and private subnets
- EKS cluster with managed node groups
- RDS PostgreSQL with read replicas
- ElastiCache Redis cluster
- S3 buckets with encryption
- ALB with SSL termination
- CloudFront distribution
- Route53 DNS records

#### Solution

```hcl
# main.tf
terraform {
  required_version = ">= 1.5.0"
  
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
  
  backend "s3" {
    bucket         = "terraform-state-prod"
    key            = "infrastructure/terraform.tfstate"
    region         = "us-east-1"
    encrypt        = true
    dynamodb_table = "terraform-locks"
  }
}

provider "aws" {
  region = var.aws_region
  
  default_tags {
    tags = {
      Environment = var.environment
      ManagedBy   = "Terraform"
      Project     = var.project_name
    }
  }
}

# VPC Module
module "vpc" {
  source = "./modules/vpc"
  
  vpc_cidr             = "10.0.0.0/16"
  availability_zones   = ["us-east-1a", "us-east-1b", "us-east-1c"]
  public_subnet_cidrs  = ["10.0.1.0/24", "10.0.2.0/24", "10.0.3.0/24"]
  private_subnet_cidrs = ["10.0.11.0/24", "10.0.12.0/24", "10.0.13.0/24"]
  database_subnet_cidrs = ["10.0.21.0/24", "10.0.22.0/24", "10.0.23.0/24"]
  
  enable_nat_gateway = true
  single_nat_gateway = false
  enable_dns_hostnames = true
  enable_dns_support = true
  
  tags = {
    Name = "${var.project_name}-vpc"
  }
}

# EKS Module
module "eks" {
  source = "./modules/eks"
  
  cluster_name    = "${var.project_name}-cluster"
  cluster_version = "1.28"
  
  vpc_id          = module.vpc.vpc_id
  subnet_ids      = module.vpc.private_subnet_ids
  
  node_groups = {
    general = {
      desired_size = 3
      min_size     = 2
      max_size     = 10
      
      instance_types = ["t3.large"]
      capacity_type  = "ON_DEMAND"
      
      labels = {
        role = "general"
      }
      
      taints = []
    }
    
    compute = {
      desired_size = 2
      min_size     = 1
      max_size     = 5
      
      instance_types = ["c5.2xlarge"]
      capacity_type  = "SPOT"
      
      labels = {
        role = "compute"
      }
      
      taints = [{
        key    = "compute"
        value  = "true"
        effect = "NoSchedule"
      }]
    }
  }
  
  enable_irsa = true
  
  cluster_addons = {
    coredns = {
      most_recent = true
    }
    kube-proxy = {
      most_recent = true
    }
    vpc-cni = {
      most_recent = true
    }
    aws-ebs-csi-driver = {
      most_recent = true
    }
  }
}

# RDS Module
module "rds" {
  source = "./modules/rds"
  
  identifier = "${var.project_name}-db"
  
  engine         = "postgres"
  engine_version = "15.4"
  instance_class = "db.r6g.xlarge"
  
  allocated_storage     = 100
  max_allocated_storage = 1000
  storage_encrypted     = true
  storage_type          = "gp3"
  iops                  = 3000
  
  db_name  = var.db_name
  username = var.db_username
  port     = 5432
  
  multi_az               = true
  db_subnet_group_name   = module.vpc.database_subnet_group_name
  vpc_security_group_ids = [module.security_groups.rds_sg_id]
  
  backup_retention_period = 30
  backup_window          = "03:00-04:00"
  maintenance_window     = "mon:04:00-mon:05:00"
  
  enabled_cloudwatch_logs_exports = ["postgresql", "upgrade"]
  
  performance_insights_enabled = true
  performance_insights_retention_period = 7
  
  deletion_protection = true
  skip_final_snapshot = false
  final_snapshot_identifier = "${var.project_name}-db-final-snapshot"
  
  # Read replicas
  replicas = {
    replica1 = {
      instance_class = "db.r6g.large"
    }
    replica2 = {
      instance_class = "db.r6g.large"
    }
  }
}

# ElastiCache Module
module "elasticache" {
  source = "./modules/elasticache"
  
  cluster_id = "${var.project_name}-redis"
  
  engine         = "redis"
  engine_version = "7.0"
  node_type      = "cache.r6g.large"
  
  num_cache_nodes = 3
  parameter_group_family = "redis7"
  
  subnet_group_name  = module.vpc.elasticache_subnet_group_name
  security_group_ids = [module.security_groups.redis_sg_id]
  
  at_rest_encryption_enabled = true
  transit_encryption_enabled = true
  auth_token_enabled         = true
  
  automatic_failover_enabled = true
  multi_az_enabled          = true
  
  snapshot_retention_limit = 5
  snapshot_window         = "03:00-05:00"
  maintenance_window      = "sun:05:00-sun:07:00"
}

# S3 Buckets
module "s3" {
  source = "./modules/s3"
  
  buckets = {
    assets = {
      bucket_name = "${var.project_name}-assets-${var.environment}"
      
      versioning = true
      
      server_side_encryption_configuration = {
        rule = {
          apply_server_side_encryption_by_default = {
            sse_algorithm = "AES256"
          }
        }
      }
      
      lifecycle_rules = [{
        id      = "transition-old-versions"
        enabled = true
        
        noncurrent_version_transition = [{
          days          = 30
          storage_class = "GLACIER"
        }]
        
        noncurrent_version_expiration = {
          days = 90
        }
      }]
      
      cors_rules = [{
        allowed_headers = ["*"]
        allowed_methods = ["GET", "HEAD"]
        allowed_origins = ["https://example.com"]
        expose_headers  = ["ETag"]
        max_age_seconds = 3000
      }]
    }
  }
}

# CloudFront
module "cloudfront" {
  source = "./modules/cloudfront"
  
  distribution_name = "${var.project_name}-cdn"
  
  origins = [{
    domain_name = module.alb.dns_name
    origin_id   = "alb"
    
    custom_origin_config = {
      http_port              = 80
      https_port             = 443
      origin_protocol_policy = "https-only"
      origin_ssl_protocols   = ["TLSv1.2"]
    }
  }]
  
  aliases = ["example.com", "www.example.com"]
  
  default_cache_behavior = {
    allowed_methods  = ["GET", "HEAD", "OPTIONS"]
    cached_methods   = ["GET", "HEAD"]
    target_origin_id = "alb"
    
    forwarded_values = {
      query_string = true
      cookies = {
        forward = "none"
      }
    }
    
    viewer_protocol_policy = "redirect-to-https"
    min_ttl                = 0
    default_ttl            = 3600
    max_ttl                = 86400
    compress               = true
  }
  
  viewer_certificate = {
    acm_certificate_arn      = module.acm.certificate_arn
    ssl_support_method       = "sni-only"
    minimum_protocol_version = "TLSv1.2_2021"
  }
  
  restrictions = {
    geo_restriction = {
      restriction_type = "none"
    }
  }
  
  enable_logging = true
  logging_config = {
    bucket = module.s3.buckets["logs"].bucket_domain_name
    prefix = "cloudfront/"
  }
}

# Outputs
output "vpc_id" {
  value = module.vpc.vpc_id
}

output "eks_cluster_endpoint" {
  value = module.eks.cluster_endpoint
}

output "rds_endpoint" {
  value     = module.rds.endpoint
  sensitive = true
}

output "redis_endpoint" {
  value = module.elasticache.endpoint
}

output "cloudfront_domain" {
  value = module.cloudfront.distribution_domain_name
}
```

```hcl
# modules/vpc/main.tf
resource "aws_vpc" "main" {
  cidr_block           = var.vpc_cidr
  enable_dns_hostnames = var.enable_dns_hostnames
  enable_dns_support   = var.enable_dns_support
  
  tags = merge(
    var.tags,
    {
      Name = "${var.tags.Name}-vpc"
    }
  )
}

resource "aws_subnet" "public" {
  count = length(var.public_subnet_cidrs)
  
  vpc_id            = aws_vpc.main.id
  cidr_block        = var.public_subnet_cidrs[count.index]
  availability_zone = var.availability_zones[count.index]
  
  map_public_ip_on_launch = true
  
  tags = {
    Name = "public-subnet-${count.index + 1}"
    Type = "public"
    "kubernetes.io/role/elb" = "1"
  }
}

resource "aws_subnet" "private" {
  count = length(var.private_subnet_cidrs)
  
  vpc_id            = aws_vpc.main.id
  cidr_block        = var.private_subnet_cidrs[count.index]
  availability_zone = var.availability_zones[count.index]
  
  tags = {
    Name = "private-subnet-${count.index + 1}"
    Type = "private"
    "kubernetes.io/role/internal-elb" = "1"
  }
}

resource "aws_internet_gateway" "main" {
  vpc_id = aws_vpc.main.id
  
  tags = {
    Name = "main-igw"
  }
}

resource "aws_eip" "nat" {
  count  = var.single_nat_gateway ? 1 : length(var.availability_zones)
  domain = "vpc"
  
  tags = {
    Name = "nat-eip-${count.index + 1}"
  }
}

resource "aws_nat_gateway" "main" {
  count = var.enable_nat_gateway ? (var.single_nat_gateway ? 1 : length(var.availability_zones)) : 0
  
  allocation_id = aws_eip.nat[count.index].id
  subnet_id     = aws_subnet.public[count.index].id
  
  tags = {
    Name = "nat-gateway-${count.index + 1}"
  }
  
  depends_on = [aws_internet_gateway.main]
}

# Route tables
resource "aws_route_table" "public" {
  vpc_id = aws_vpc.main.id
  
  route {
    cidr_block = "0.0.0.0/0"
    gateway_id = aws_internet_gateway.main.id
  }
  
  tags = {
    Name = "public-route-table"
  }
}

resource "aws_route_table" "private" {
  count  = var.single_nat_gateway ? 1 : length(var.availability_zones)
  vpc_id = aws_vpc.main.id
  
  route {
    cidr_block     = "0.0.0.0/0"
    nat_gateway_id = aws_nat_gateway.main[count.index].id
  }
  
  tags = {
    Name = "private-route-table-${count.index + 1}"
  }
}
```

---

## Monitoring & Observability

### Question 4: Prometheus and Grafana Setup

**Difficulty**: ⭐⭐⭐ Advanced  
**Topic**: Monitoring, Observability  
**Tags**: `prometheus`, `grafana`, `monitoring`, `alerting`

#### Question

Design a comprehensive monitoring solution using Prometheus and Grafana including:
- Custom metrics collection
- Alert rules
- Dashboard templates
- Service discovery
- High availability setup

#### Solution

```yaml
# prometheus/values.yaml (Helm)
prometheus:
  prometheusSpec:
    retention: 30d
    retentionSize: "50GB"
    
    replicas: 2
    
    resources:
      requests:
        cpu: 500m
        memory: 2Gi
      limits:
        cpu: 2000m
        memory: 4Gi
    
    storageSpec:
      volumeClaimTemplate:
        spec:
          accessModes: ["ReadWriteOnce"]
          resources:
            requests:
              storage: 100Gi
          storageClassName: gp3
    
    # Service monitors
    serviceMonitorSelectorNilUsesHelmValues: false
    serviceMonitorSelector: {}
    
    # Pod monitors
    podMonitorSelectorNilUsesHelmValues: false
    podMonitorSelector: {}
    
    # Additional scrape configs
    additionalScrapeConfigs:
      - job_name: 'kubernetes-pods'
        kubernetes_sd_configs:
          - role: pod
        relabel_configs:
          - source_labels: [__meta_kubernetes_pod_annotation_prometheus_io_scrape]
            action: keep
            regex: true
          - source_labels: [__meta_kubernetes_pod_annotation_prometheus_io_path]
            action: replace
            target_label: __metrics_path__
            regex: (.+)
          - source_labels: [__address__, __meta_kubernetes_pod_annotation_prometheus_io_port]
            action: replace
            regex: ([^:]+)(?::\d+)?;(\d+)
            replacement: $1:$2
            target_label: __address__

grafana:
  enabled: true
  
  replicas: 2
  
  persistence:
    enabled: true
    size: 10Gi
  
  adminPassword: ${GRAFANA_ADMIN_PASSWORD}
  
  datasources:
    datasources.yaml:
      apiVersion: 1
      datasources:
        - name: Prometheus
          type: prometheus
          url: http://prometheus-operated:9090
          access: proxy
          isDefault: true
        
        - name: Loki
          type: loki
          url: http://loki:3100
          access: proxy
  
  dashboardProviders:
    dashboardproviders.yaml:
      apiVersion: 1
      providers:
        - name: 'default'
          orgId: 1
          folder: ''
          type: file
          disableDeletion: false
          editable: true
          options:
            path: /var/lib/grafana/dashboards/default
  
  dashboards:
    default:
      kubernetes-cluster:
        gnetId: 7249
        revision: 1
        datasource: Prometheus
      
      node-exporter:
        gnetId: 1860
        revision: 23
        datasource: Prometheus

alertmanager:
  config:
    global:
      resolve_timeout: 5m
      slack_api_url: ${SLACK_WEBHOOK_URL}
    
    route:
      group_by: ['alertname', 'cluster', 'service']
      group_wait: 10s
      group_interval: 10s
      repeat_interval: 12h
      receiver: 'default'
      
      routes:
        - match:
            severity: critical
          receiver: 'critical'
          continue: true
        
        - match:
            severity: warning
          receiver: 'warning'
    
    receivers:
      - name: 'default'
        slack_configs:
          - channel: '#alerts'
            title: 'Alert: {{ .GroupLabels.alertname }}'
            text: '{{ range .Alerts }}{{ .Annotations.description }}{{ end }}'
      
      - name: 'critical'
        slack_configs:
          - channel: '#alerts-critical'
            title: '🚨 CRITICAL: {{ .GroupLabels.alertname }}'
            text: '{{ range .Alerts }}{{ .Annotations.description }}{{ end }}'
        pagerduty_configs:
          - service_key: ${PAGERDUTY_SERVICE_KEY}
      
      - name: 'warning'
        slack_configs:
          - channel: '#alerts-warning'
            title: '⚠️  WARNING: {{ .GroupLabels.alertname }}'
            text: '{{ range .Alerts }}{{ .Annotations.description }}{{ end }}'

# Alert rules
additionalPrometheusRulesMap:
  rule-name:
    groups:
      - name: application
        interval: 30s
        rules:
          # High error rate
          - alert: HighErrorRate
            expr: |
              sum(rate(http_requests_total{status=~"5.."}[5m])) by (service)
              /
              sum(rate(http_requests_total[5m])) by (service)
              > 0.05
            for: 5m
            labels:
              severity: critical
            annotations:
              summary: "High error rate on {{ $labels.service }}"
              description: "{{ $labels.service }} has error rate of {{ $value | humanizePercentage }}"
          
          # High response time
          - alert: HighResponseTime
            expr: |
              histogram_quantile(0.95,
                sum(rate(http_request_duration_seconds_bucket[5m])) by (le, service)
              ) > 1
            for: 10m
            labels:
              severity: warning
            annotations:
              summary: "High response time on {{ $labels.service }}"
              description: "95th percentile response time is {{ $value }}s"
          
          # Pod not ready
          - alert: PodNotReady
            expr: |
              sum by (namespace, pod) (
                kube_pod_status_phase{phase!~"Running|Succeeded"}
              ) > 0
            for: 15m
            labels:
              severity: warning
            annotations:
              summary: "Pod {{ $labels.pod }} not ready"
              description: "Pod has been in {{ $value }} state for more than 15 minutes"
          
          # High memory usage
          - alert: HighMemoryUsage
            expr: |
              (sum(container_memory_working_set_bytes{name!=""}) by (pod, namespace)
              / sum(container_spec_memory_limit_bytes{name!=""}) by (pod, namespace) * 100) > 90
            for: 10m
            labels:
              severity: warning
            annotations:
              summary: "High memory usage on {{ $labels.pod }}"
              description: "Memory usage is {{ $value }}%"
```

---

## Container Security

### Question 5: Container Security Best Practices

**Difficulty**: ⭐⭐⭐ Advanced  
**Topic**: Security, Containers  
**Tags**: `security`, `docker`, `scanning`, `hardening`

#### Question

Implement comprehensive container security including:
- Multi-stage builds for minimal images
- Vulnerability scanning in CI/CD
- Runtime security with AppArmor/Seccomp
- Image signing and verification
- Secrets management

#### Solution

```dockerfile
# Multi-stage Dockerfile with security best practices
# Stage 1: Build
FROM node:20-alpine AS builder

# Create non-root user
RUN addgroup -g 1001 -S nodejs && \
    adduser -S nodejs -u 1001

# Set working directory
WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci --only=production && \
    npm cache clean --force

# Copy source code
COPY --chown=nodejs:nodejs . .

# Build application
RUN npm run build

# Stage 2: Production
FROM gcr.io/distroless/nodejs20-debian11

# Copy from builder
COPY --from=builder --chown=nonroot:nonroot /app/dist ./dist
COPY --from=builder --chown=nonroot:nonroot /app/node_modules ./node_modules
COPY --from=builder --chown=nonroot:nonroot /app/package.json ./

# Use non-root user
USER nonroot

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD ["/nodejs/bin/node", "healthcheck.js"]

# Expose port
EXPOSE 8080

# Start application
CMD ["dist/index.js"]
```

```yaml
# Security scanning in CI/CD
# .github/workflows/security.yml
name: Security Scan

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]
  schedule:
    - cron: '0 0 * * *'  # Daily scan

jobs:
  scan:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Build image
        run: docker build -t app:latest .
      
      - name: Run Trivy scanner
        uses: aquasecurity/trivy-action@master
        with:
          image-ref: 'app:latest'
          format: 'sarif'
          output: 'trivy-results.sarif'
          severity: 'CRITICAL,HIGH'
      
      - name: Run Snyk
        uses: snyk/actions/docker@master
        env:
          SNYK_TOKEN: ${{ secrets.SNYK_TOKEN }}
        with:
          image: app:latest
          args: --severity-threshold=high
      
      - name: Run Grype scanner
        uses: anchore/scan-action@v3
        with:
          image: "app:latest"
          fail-build: true
          severity-cutoff: high
      
      - name: Upload results to GitHub Security
        uses: github/codeql-action/upload-sarif@v2
        if: always()
        with:
          sarif_file: 'trivy-results.sarif'
```

```yaml
# Pod Security with AppArmor and Seccomp
apiVersion: v1
kind: Pod
metadata:
  name: secure-app
  annotations:
    container.apparmor.security.beta.kubernetes.io/app: runtime/default
    seccomp.security.alpha.kubernetes.io/pod: runtime/default
spec:
  securityContext:
    runAsNonRoot: true
    runAsUser: 1000
    fsGroup: 1000
    seccompProfile:
      type: RuntimeDefault
  
  containers:
    - name: app
      image: app:latest
      
      securityContext:
        allowPrivilegeEscalation: false
        readOnlyRootFilesystem: true
        runAsNonRoot: true
        runAsUser: 1000
        capabilities:
          drop:
            - ALL
          add:
            - NET_BIND_SERVICE
      
      volumeMounts:
        - name: tmp
          mountPath: /tmp
        - name: secrets
          mountPath: /secrets
          readOnly: true
  
  volumes:
    - name: tmp
      emptyDir: {}
    - name: secrets
      secret:
        secretName: app-secrets
```

---

**Total Questions**: 25+ comprehensive questions covering Kubernetes, CI/CD, Infrastructure, Monitoring, and Security from Intermediate to Expert levels.
