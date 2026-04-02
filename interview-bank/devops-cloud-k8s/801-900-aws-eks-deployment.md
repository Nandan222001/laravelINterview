# AWS EKS and Cloud Deployment Questions (801-900)

## Question 801: AWS EKS Cluster Setup with Terraform

**Difficulty**: Senior  
**Topic**: AWS EKS  
**Tags**: `aws`, `eks`, `terraform`, `kubernetes`

### 📋 Question

Design and deploy a production-grade Amazon EKS cluster using Terraform with proper networking, IAM roles, managed node groups, and add-ons for logging and monitoring.

#### Scenario

Your organization needs an EKS cluster that provides:
- High availability across multiple AZs
- Managed node groups with auto-scaling
- IRSA (IAM Roles for Service Accounts)
- VPC CNI for pod networking
- AWS Load Balancer Controller
- EBS CSI Driver for persistent storage
- CloudWatch Container Insights
- Cluster autoscaling

#### Requirements

- Use Terraform EKS module
- Configure VPC with proper CIDR
- Set up IAM roles and policies
- Enable cluster logging
- Configure managed node groups
- Install essential add-ons
- Implement security best practices

### ✅ Sample Solution

```hcl
# main.tf
terraform {
  required_version = ">= 1.5"
  
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
    kubernetes = {
      source  = "hashicorp/kubernetes"
      version = "~> 2.23"
    }
    helm = {
      source  = "hashicorp/helm"
      version = "~> 2.11"
    }
  }
  
  backend "s3" {
    bucket         = "terraform-state-bucket"
    key            = "eks/terraform.tfstate"
    region         = "us-east-1"
    encrypt        = true
    dynamodb_table = "terraform-state-lock"
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

# Data sources
data "aws_caller_identity" "current" {}
data "aws_availability_zones" "available" {}

# ============================================
# VPC Configuration
# ============================================

module "vpc" {
  source  = "terraform-aws-modules/vpc/aws"
  version = "~> 5.0"
  
  name = "${var.project_name}-vpc"
  cidr = var.vpc_cidr
  
  azs             = slice(data.aws_availability_zones.available.names, 0, 3)
  private_subnets = [for k, v in slice(data.aws_availability_zones.available.names, 0, 3) : cidrsubnet(var.vpc_cidr, 4, k)]
  public_subnets  = [for k, v in slice(data.aws_availability_zones.available.names, 0, 3) : cidrsubnet(var.vpc_cidr, 8, k + 48)]
  
  enable_nat_gateway   = true
  single_nat_gateway   = var.environment != "production"
  enable_dns_hostnames = true
  enable_dns_support   = true
  
  # Kubernetes tags
  public_subnet_tags = {
    "kubernetes.io/role/elb"                    = "1"
    "kubernetes.io/cluster/${var.cluster_name}" = "shared"
  }
  
  private_subnet_tags = {
    "kubernetes.io/role/internal-elb"           = "1"
    "kubernetes.io/cluster/${var.cluster_name}" = "shared"
  }
  
  tags = {
    "kubernetes.io/cluster/${var.cluster_name}" = "shared"
  }
}

# ============================================
# EKS Cluster
# ============================================

module "eks" {
  source  = "terraform-aws-modules/eks/aws"
  version = "~> 19.0"
  
  cluster_name    = var.cluster_name
  cluster_version = var.kubernetes_version
  
  # Cluster endpoint configuration
  cluster_endpoint_public_access  = true
  cluster_endpoint_private_access = true
  cluster_endpoint_public_access_cidrs = var.cluster_endpoint_public_access_cidrs
  
  # VPC configuration
  vpc_id     = module.vpc.vpc_id
  subnet_ids = module.vpc.private_subnets
  
  # Cluster add-ons
  cluster_addons = {
    coredns = {
      most_recent = true
      configuration_values = jsonencode({
        computeType = "Fargate"
        resources = {
          limits = {
            cpu    = "100m"
            memory = "150Mi"
          }
          requests = {
            cpu    = "100m"
            memory = "150Mi"
          }
        }
      })
    }
    
    kube-proxy = {
      most_recent = true
    }
    
    vpc-cni = {
      most_recent = true
      configuration_values = jsonencode({
        env = {
          ENABLE_PREFIX_DELEGATION = "true"
          ENABLE_POD_ENI          = "true"
          POD_SECURITY_GROUP_ENFORCING_MODE = "standard"
        }
        enableNetworkPolicy = "true"
      })
    }
    
    aws-ebs-csi-driver = {
      most_recent = true
      service_account_role_arn = module.ebs_csi_driver_irsa.iam_role_arn
    }
  }
  
  # Cluster logging
  cluster_enabled_log_types = [
    "api",
    "audit",
    "authenticator",
    "controllerManager",
    "scheduler"
  ]
  
  # Cluster encryption
  cluster_encryption_config = {
    provider_key_arn = aws_kms_key.eks.arn
    resources        = ["secrets"]
  }
  
  # ============================================
  # Managed Node Groups
  # ============================================
  
  eks_managed_node_groups = {
    # General purpose node group
    general = {
      name = "${var.cluster_name}-general"
      
      instance_types = ["t3.large"]
      capacity_type  = "ON_DEMAND"
      
      min_size     = 2
      max_size     = 10
      desired_size = 3
      
      # Use the cluster security group
      vpc_security_group_ids = [aws_security_group.node_additional.id]
      
      # Launch template configuration
      create_launch_template = true
      launch_template_name   = "${var.cluster_name}-general-lt"
      
      block_device_mappings = {
        xvda = {
          device_name = "/dev/xvda"
          ebs = {
            volume_size           = 100
            volume_type           = "gp3"
            iops                  = 3000
            throughput            = 150
            encrypted             = true
            kms_key_id            = aws_kms_key.ebs.arn
            delete_on_termination = true
          }
        }
      }
      
      metadata_options = {
        http_endpoint               = "enabled"
        http_tokens                 = "required"
        http_put_response_hop_limit = 1
        instance_metadata_tags      = "enabled"
      }
      
      tags = {
        "k8s.io/cluster-autoscaler/${var.cluster_name}" = "owned"
        "k8s.io/cluster-autoscaler/enabled"             = "true"
      }
    }
    
    # Spot instance node group
    spot = {
      name = "${var.cluster_name}-spot"
      
      instance_types = ["t3.large", "t3a.large"]
      capacity_type  = "SPOT"
      
      min_size     = 0
      max_size     = 10
      desired_size = 2
      
      vpc_security_group_ids = [aws_security_group.node_additional.id]
      
      taints = {
        spot = {
          key    = "workload-type"
          value  = "spot"
          effect = "NoSchedule"
        }
      }
      
      labels = {
        "workload-type" = "spot"
      }
      
      tags = {
        "k8s.io/cluster-autoscaler/${var.cluster_name}" = "owned"
        "k8s.io/cluster-autoscaler/enabled"             = "true"
      }
    }
  }
  
  # ============================================
  # AWS Auth Configuration
  # ============================================
  
  manage_aws_auth_configmap = true
  
  aws_auth_roles = [
    {
      rolearn  = "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/AdminRole"
      username = "admin"
      groups   = ["system:masters"]
    },
  ]
  
  aws_auth_users = [
    {
      userarn  = "arn:aws:iam::${data.aws_caller_identity.current.account_id}:user/developer"
      username = "developer"
      groups   = ["developers"]
    },
  ]
  
  # ============================================
  # Security Group Rules
  # ============================================
  
  node_security_group_additional_rules = {
    ingress_self_all = {
      description = "Node to node all ports/protocols"
      protocol    = "-1"
      from_port   = 0
      to_port     = 0
      type        = "ingress"
      self        = true
    }
    
    ingress_cluster_to_node_all_traffic = {
      description                   = "Cluster API to Nodegroup all traffic"
      protocol                      = "-1"
      from_port                     = 0
      to_port                       = 0
      type                          = "ingress"
      source_cluster_security_group = true
    }
    
    egress_all = {
      description = "Node all egress"
      protocol    = "-1"
      from_port   = 0
      to_port     = 0
      type        = "egress"
      cidr_blocks = ["0.0.0.0/0"]
    }
  }
}

# Additional security group for nodes
resource "aws_security_group" "node_additional" {
  name_prefix = "${var.cluster_name}-node-additional-"
  description = "Additional security group for EKS nodes"
  vpc_id      = module.vpc.vpc_id
  
  ingress {
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = var.ssh_access_cidrs
    description = "SSH access"
  }
  
  tags = {
    Name = "${var.cluster_name}-node-additional"
  }
}

# ============================================
# KMS Keys
# ============================================

resource "aws_kms_key" "eks" {
  description             = "EKS cluster encryption key"
  deletion_window_in_days = 7
  enable_key_rotation     = true
  
  tags = {
    Name = "${var.cluster_name}-eks-key"
  }
}

resource "aws_kms_alias" "eks" {
  name          = "alias/${var.cluster_name}-eks"
  target_key_id = aws_kms_key.eks.key_id
}

resource "aws_kms_key" "ebs" {
  description             = "EBS volume encryption key"
  deletion_window_in_days = 7
  enable_key_rotation     = true
  
  tags = {
    Name = "${var.cluster_name}-ebs-key"
  }
}

resource "aws_kms_alias" "ebs" {
  name          = "alias/${var.cluster_name}-ebs"
  target_key_id = aws_kms_key.ebs.key_id
}

# ============================================
# IRSA for EBS CSI Driver
# ============================================

module "ebs_csi_driver_irsa" {
  source  = "terraform-aws-modules/iam/aws//modules/iam-role-for-service-accounts-eks"
  version = "~> 5.0"
  
  role_name = "${var.cluster_name}-ebs-csi-driver"
  
  attach_ebs_csi_policy = true
  
  oidc_providers = {
    main = {
      provider_arn               = module.eks.oidc_provider_arn
      namespace_service_accounts = ["kube-system:ebs-csi-controller-sa"]
    }
  }
  
  tags = {
    Name = "${var.cluster_name}-ebs-csi-driver"
  }
}

# ============================================
# IRSA for AWS Load Balancer Controller
# ============================================

module "aws_load_balancer_controller_irsa" {
  source  = "terraform-aws-modules/iam/aws//modules/iam-role-for-service-accounts-eks"
  version = "~> 5.0"
  
  role_name = "${var.cluster_name}-aws-load-balancer-controller"
  
  attach_load_balancer_controller_policy = true
  
  oidc_providers = {
    main = {
      provider_arn               = module.eks.oidc_provider_arn
      namespace_service_accounts = ["kube-system:aws-load-balancer-controller"]
    }
  }
  
  tags = {
    Name = "${var.cluster_name}-aws-load-balancer-controller"
  }
}

# ============================================
# IRSA for Cluster Autoscaler
# ============================================

module "cluster_autoscaler_irsa" {
  source  = "terraform-aws-modules/iam/aws//modules/iam-role-for-service-accounts-eks"
  version = "~> 5.0"
  
  role_name = "${var.cluster_name}-cluster-autoscaler"
  
  attach_cluster_autoscaler_policy = true
  cluster_autoscaler_cluster_names = [var.cluster_name]
  
  oidc_providers = {
    main = {
      provider_arn               = module.eks.oidc_provider_arn
      namespace_service_accounts = ["kube-system:cluster-autoscaler"]
    }
  }
  
  tags = {
    Name = "${var.cluster_name}-cluster-autoscaler"
  }
}

# ============================================
# Kubernetes Provider Configuration
# ============================================

provider "kubernetes" {
  host                   = module.eks.cluster_endpoint
  cluster_ca_certificate = base64decode(module.eks.cluster_certificate_authority_data)
  
  exec {
    api_version = "client.authentication.k8s.io/v1beta1"
    command     = "aws"
    args = [
      "eks",
      "get-token",
      "--cluster-name",
      module.eks.cluster_name
    ]
  }
}

provider "helm" {
  kubernetes {
    host                   = module.eks.cluster_endpoint
    cluster_ca_certificate = base64decode(module.eks.cluster_certificate_authority_data)
    
    exec {
      api_version = "client.authentication.k8s.io/v1beta1"
      command     = "aws"
      args = [
        "eks",
        "get-token",
        "--cluster-name",
        module.eks.cluster_name
      ]
    }
  }
}

# ============================================
# AWS Load Balancer Controller
# ============================================

resource "helm_release" "aws_load_balancer_controller" {
  name       = "aws-load-balancer-controller"
  repository = "https://aws.github.io/eks-charts"
  chart      = "aws-load-balancer-controller"
  namespace  = "kube-system"
  version    = "1.6.2"
  
  set {
    name  = "clusterName"
    value = module.eks.cluster_name
  }
  
  set {
    name  = "serviceAccount.create"
    value = "true"
  }
  
  set {
    name  = "serviceAccount.name"
    value = "aws-load-balancer-controller"
  }
  
  set {
    name  = "serviceAccount.annotations.eks\\.amazonaws\\.com/role-arn"
    value = module.aws_load_balancer_controller_irsa.iam_role_arn
  }
  
  set {
    name  = "region"
    value = var.aws_region
  }
  
  set {
    name  = "vpcId"
    value = module.vpc.vpc_id
  }
  
  depends_on = [module.eks]
}

# ============================================
# Cluster Autoscaler
# ============================================

resource "helm_release" "cluster_autoscaler" {
  name       = "cluster-autoscaler"
  repository = "https://kubernetes.github.io/autoscaler"
  chart      = "cluster-autoscaler"
  namespace  = "kube-system"
  version    = "9.29.3"
  
  set {
    name  = "autoDiscovery.clusterName"
    value = module.eks.cluster_name
  }
  
  set {
    name  = "awsRegion"
    value = var.aws_region
  }
  
  set {
    name  = "rbac.serviceAccount.create"
    value = "true"
  }
  
  set {
    name  = "rbac.serviceAccount.name"
    value = "cluster-autoscaler"
  }
  
  set {
    name  = "rbac.serviceAccount.annotations.eks\\.amazonaws\\.com/role-arn"
    value = module.cluster_autoscaler_irsa.iam_role_arn
  }
  
  set {
    name  = "extraArgs.balance-similar-node-groups"
    value = "true"
  }
  
  set {
    name  = "extraArgs.skip-nodes-with-system-pods"
    value = "false"
  }
  
  depends_on = [module.eks]
}

# ============================================
# Metrics Server
# ============================================

resource "helm_release" "metrics_server" {
  name       = "metrics-server"
  repository = "https://kubernetes-sigs.github.io/metrics-server/"
  chart      = "metrics-server"
  namespace  = "kube-system"
  version    = "3.11.0"
  
  set {
    name  = "args[0]"
    value = "--kubelet-preferred-address-types=InternalIP"
  }
  
  depends_on = [module.eks]
}

# ============================================
# CloudWatch Container Insights
# ============================================

resource "aws_iam_policy" "cloudwatch_agent" {
  name        = "${var.cluster_name}-cloudwatch-agent"
  description = "Policy for CloudWatch Container Insights"
  
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Action = [
          "cloudwatch:PutMetricData",
          "ec2:DescribeVolumes",
          "ec2:DescribeTags",
          "logs:PutLogEvents",
          "logs:DescribeLogStreams",
          "logs:DescribeLogGroups",
          "logs:CreateLogStream",
          "logs:CreateLogGroup"
        ]
        Resource = "*"
      },
      {
        Effect = "Allow"
        Action = [
          "ssm:GetParameter"
        ]
        Resource = "arn:aws:ssm:*:*:parameter/AmazonCloudWatch-*"
      }
    ]
  })
}

module "cloudwatch_agent_irsa" {
  source  = "terraform-aws-modules/iam/aws//modules/iam-role-for-service-accounts-eks"
  version = "~> 5.0"
  
  role_name = "${var.cluster_name}-cloudwatch-agent"
  
  role_policy_arns = {
    policy = aws_iam_policy.cloudwatch_agent.arn
  }
  
  oidc_providers = {
    main = {
      provider_arn               = module.eks.oidc_provider_arn
      namespace_service_accounts = [
        "amazon-cloudwatch:cloudwatch-agent",
        "amazon-cloudwatch:fluentd"
      ]
    }
  }
}

# ============================================
# Variables
# ============================================

variable "aws_region" {
  description = "AWS region"
  type        = string
  default     = "us-east-1"
}

variable "project_name" {
  description = "Project name"
  type        = string
}

variable "environment" {
  description = "Environment name"
  type        = string
}

variable "cluster_name" {
  description = "EKS cluster name"
  type        = string
}

variable "kubernetes_version" {
  description = "Kubernetes version"
  type        = string
  default     = "1.28"
}

variable "vpc_cidr" {
  description = "VPC CIDR block"
  type        = string
  default     = "10.0.0.0/16"
}

variable "cluster_endpoint_public_access_cidrs" {
  description = "CIDR blocks allowed to access cluster endpoint"
  type        = list(string)
  default     = ["0.0.0.0/0"]
}

variable "ssh_access_cidrs" {
  description = "CIDR blocks allowed SSH access to nodes"
  type        = list(string)
  default     = []
}

# ============================================
# Outputs
# ============================================

output "cluster_id" {
  description = "EKS cluster ID"
  value       = module.eks.cluster_id
}

output "cluster_endpoint" {
  description = "EKS cluster endpoint"
  value       = module.eks.cluster_endpoint
}

output "cluster_security_group_id" {
  description = "Security group ID attached to the EKS cluster"
  value       = module.eks.cluster_security_group_id
}

output "cluster_oidc_issuer_url" {
  description = "The URL on the EKS cluster OIDC Issuer"
  value       = module.eks.cluster_oidc_issuer_url
}

output "configure_kubectl" {
  description = "Command to configure kubectl"
  value       = "aws eks update-kubeconfig --region ${var.aws_region} --name ${module.eks.cluster_name}"
}
```

**Usage:**

```bash
# Initialize Terraform
terraform init

# Plan deployment
terraform plan -var-file=production.tfvars

# Apply configuration
terraform apply -var-file=production.tfvars

# Configure kubectl
aws eks update-kubeconfig --region us-east-1 --name my-cluster

# Verify cluster
kubectl get nodes
kubectl get pods -A
```

### 🎯 Key Concepts to Assess

- [ ] **EKS Architecture**: Understanding control plane, data plane, and add-ons
- [ ] **IRSA**: IAM Roles for Service Accounts for secure AWS API access
- [ ] **Node Groups**: Managed node groups vs self-managed vs Fargate
- [ ] **Networking**: VPC CNI, pod networking, and security groups

### 🔄 Follow-up Questions

1. **What's the difference between IRSA and instance profiles?**
   - IRSA provides pod-level IAM roles; instance profiles apply to entire node

2. **How does VPC CNI allocate IPs to pods?**
   - Uses secondary IP addresses from node's ENI or prefix delegation

3. **Why use managed node groups over self-managed?**
   - Automated updates, better integration with EKS, simplified management

---

*Questions 802-900 continue with topics including: EKS Fargate profiles, EKS anywhere, EKS blueprints, EKS pod identity, ECR image scanning, ECR repository policies, S3 bucket policies, CloudFront with S3, Lambda@Edge, API Gateway integration, DynamoDB tables, ElastiCache Redis, RDS Aurora Serverless, Application Load Balancer, Network Load Balancer, Route53 hosted zones, ACM certificates, WAF rules, CloudWatch alarms, CloudWatch Logs Insights, X-Ray tracing, EventBridge rules, SNS topics, SQS queues, Step Functions, ECS vs EKS comparison, ECS task definitions, ECS Service Discovery, App Mesh with ECS, Copilot CLI, CDK for infrastructure, CloudFormation stacks, Systems Manager Session Manager, Parameter Store hierarchy, Secrets Manager rotation, KMS key policies, VPC endpoints for AWS services, PrivateLink, Transit Gateway, VPN connections, Direct Connect, AWS Organizations, Service Control Policies, Resource Access Manager, Control Tower, Landing Zone, Multi-account strategy, Cost allocation tags, AWS Cost Explorer, Reserved Instances, Savings Plans, Spot Instances best practices, and AWS Well-Architected Tool.*

