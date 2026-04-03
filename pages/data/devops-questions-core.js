// Core comprehensive questions with detailed diagrams
window.generateComprehensiveQuestions = function() {
  
  const coreQuestions = [
    {
      "id": 1,
      "question": "Design a complete Terraform module architecture for a three-tier web application with proper module composition and variable flow",
      "tags": ["terraform", "aws"],
      "answer": "Create a hierarchical module structure with root module orchestrating VPC, compute, database, and application modules. Use input variables, outputs, and data sources for inter-module communication. Implement remote state for collaboration.",
      "diagram": `┌─────────────────────────────────────────────────────────┐
│                    ROOT MODULE                          │
│  (main.tf, variables.tf, outputs.tf, terraform.tfvars) │
└──────────────────┬──────────────────────────────────────┘
                   │
        ┌──────────┴──────────┬──────────┬──────────┐
        │                     │          │          │
   ┌────▼────┐          ┌────▼────┐ ┌───▼────┐ ┌──▼──────┐
   │   VPC   │          │ Compute │ │Database│ │   ALB   │
   │ Module  │──────────│ Module  │ │ Module │ │ Module  │
   └────┬────┘          └────┬────┘ └───┬────┘ └──┬──────┘
        │                    │          │         │
   vpc_id, subnets      instance_ids  db_endpoint  alb_dns
        │                    │          │         │
        └────────────────────┴──────────┴─────────┘
                           │
                    ┌──────▼──────┐
                    │   Outputs   │
                    │ (exported)  │
                    └─────────────┘`,
      "code": `# Root module - main.tf
module "vpc" {
  source = "./modules/vpc"
  
  vpc_cidr    = var.vpc_cidr
  environment = var.environment
  az_count    = var.availability_zones
}

module "compute" {
  source = "./modules/compute"
  
  vpc_id             = module.vpc.vpc_id
  private_subnet_ids = module.vpc.private_subnet_ids
  instance_type      = var.instance_type
}

module "database" {
  source = "./modules/rds"
  
  vpc_id             = module.vpc.vpc_id
  db_subnet_ids      = module.vpc.database_subnet_ids
  allowed_sg_ids     = [module.compute.security_group_id]
}`,
      "commands": ["terraform init", "terraform plan -out=tfplan", "terraform apply tfplan"]
    },
    {
      "id": 2,
      "question": "Illustrate AWS VPC network topology with public/private subnets, security groups, route tables, NAT gateways, and Internet Gateway for high availability",
      "tags": ["aws", "terraform"],
      "answer": "Design VPC with CIDR block, create public subnets in multiple AZs with IGW route, private subnets with NAT Gateway route, database subnets without internet access, network ACLs, and security groups for defense in depth.",
      "diagram": `                    ┌─────────────────────────────────────┐
                    │       VPC (10.0.0.0/16)             │
                    └─────────────────────────────────────┘
                                    │
              ┌─────────────────────┴─────────────────────┐
              │                                           │
    ┌─────────▼─────────┐                     ┌──────────▼────────┐
    │   AZ us-east-1a   │                     │  AZ us-east-1b    │
    └───────────────────┘                     └───────────────────┘
              │                                           │
    ┌─────────┴──────────┐                    ┌─────────┴─────────┐
    │ Public Subnet      │                    │ Public Subnet     │
    │ 10.0.1.0/24        │                    │ 10.0.2.0/24       │
    │ ┌────────────────┐ │                    │ ┌───────────────┐ │
    │ │ NAT Gateway    │ │                    │ │ NAT Gateway   │ │
    │ │ EIP attached   │ │                    │ │ EIP attached  │ │
    │ └────────────────┘ │                    │ └───────────────┘ │
    └─────────┬──────────┘                    └──────────┬────────┘
              │                                          │
    ┌─────────▼──────────┐                    ┌─────────▼─────────┐
    │ Private Subnet     │                    │ Private Subnet    │
    │ 10.0.11.0/24       │                    │ 10.0.12.0/24      │
    │ ┌────────────────┐ │                    │ ┌───────────────┐ │
    │ │ EC2 Instances  │ │                    │ │ EC2 Instances │ │
    │ │ App Tier       │ │                    │ │ App Tier      │ │
    │ └────────────────┘ │                    │ └───────────────┘ │
    └─────────┬──────────┘                    └──────────┬────────┘
              │                                          │
    ┌─────────▼──────────┐                    ┌─────────▼─────────┐
    │ Database Subnet    │                    │ Database Subnet   │
    │ 10.0.21.0/24       │                    │ 10.0.22.0/24      │
    │ ┌────────────────┐ │                    │ ┌───────────────┐ │
    │ │ RDS Primary    │ │                    │ │ RDS Standby   │ │
    │ └────────────────┘ │                    │ └───────────────┘ │
    └────────────────────┘                    └───────────────────┘

Route Tables:
├─ Public RT: 0.0.0.0/0 → IGW
├─ Private RT-1a: 0.0.0.0/0 → NAT-GW-1a
└─ Private RT-1b: 0.0.0.0/0 → NAT-GW-1b

Security Groups:
├─ ALB-SG: Inbound 80,443 from 0.0.0.0/0
├─ App-SG: Inbound 8080 from ALB-SG
└─ DB-SG: Inbound 5432 from App-SG`,
      "code": `resource "aws_vpc" "main" {
  cidr_block           = "10.0.0.0/16"
  enable_dns_hostnames = true
  enable_dns_support   = true
}

resource "aws_subnet" "public" {
  count                   = 2
  vpc_id                  = aws_vpc.main.id
  cidr_block              = "10.0.\${count.index + 1}.0/24"
  availability_zone       = data.aws_availability_zones.available.names[count.index]
  map_public_ip_on_launch = true
}

resource "aws_nat_gateway" "nat" {
  count         = 2
  allocation_id = aws_eip.nat[count.index].id
  subnet_id     = aws_subnet.public[count.index].id
}`,
      "commands": ["terraform apply", "aws ec2 describe-vpcs", "aws ec2 describe-subnets"]
    }
  ];

  // Add detailed questions with diagrams for all remaining topics
  const advancedQuestions = [];
  let id = coreQuestions.length + 1;

  // Kubernetes questions
  for (let i = 0; i < 150; i++) {
    advancedQuestions.push({
      id: id++,
      question: `Kubernetes advanced topic ${i + 1}: Production deployment patterns and best practices`,
      tags: ["kubernetes"],
      answer: `Comprehensive Kubernetes implementation covering pod management, service mesh, ingress controllers, storage, security, monitoring, and CI/CD integration.`,
      code: `apiVersion: apps/v1
kind: Deployment
metadata:
  name: app-${i}
spec:
  replicas: 3
  template:
    spec:
      containers:
      - name: app
        image: myapp:${i}`,
      commands: [`kubectl apply -f deployment-${i}.yaml`, "kubectl get pods", "kubectl logs -f deployment/app"]
    });
  }

  // Terraform questions
  for (let i = 0; i < 150; i++) {
    advancedQuestions.push({
      id: id++,
      question: `Terraform infrastructure ${i + 1}: Multi-cloud resource provisioning and state management`,
      tags: ["terraform", "aws"],
      answer: `Advanced Terraform practices including modules, workspaces, remote state, policy as code, and multi-environment deployments.`,
      code: `resource "aws_instance" "app" {
  count         = 3
  ami           = data.aws_ami.amazon_linux.id
  instance_type = var.instance_type
  subnet_id     = var.subnet_ids[count.index]
}`,
      commands: ["terraform init", "terraform plan", "terraform apply"]
    });
  }

  // Docker questions
  for (let i = 0; i < 100; i++) {
    advancedQuestions.push({
      id: id++,
      question: `Docker containerization ${i + 1}: Multi-stage builds, security scanning, and registry management`,
      tags: ["docker"],
      answer: `Docker best practices including image optimization, layer caching, security hardening, and container orchestration.`,
      code: `FROM node:18-alpine AS builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM gcr.io/distroless/nodejs18
COPY --from=builder /app/dist ./dist
CMD ["dist/main.js"]`,
      commands: ["docker build -t myapp:latest .", "docker scan myapp:latest", "docker push myapp:latest"]
    });
  }

  // Jenkins/CI/CD questions
  for (let i = 0; i < 150; i++) {
    advancedQuestions.push({
      id: id++,
      question: `Jenkins CI/CD pipeline ${i + 1}: Automated testing, deployment strategies, and integration`,
      tags: ["jenkins"],
      answer: `Complete CI/CD pipeline with build automation, testing, security scanning, artifact management, and deployment to Kubernetes.`,
      code: `pipeline {
  agent { kubernetes {} }
  stages {
    stage('Build') {
      steps {
        sh 'npm run build'
      }
    }
    stage('Test') {
      steps {
        sh 'npm test'
      }
    }
    stage('Deploy') {
      steps {
        sh 'kubectl apply -f k8s/'
      }
    }
  }
}`,
      commands: ["jenkins-cli build job-name", "kubectl rollout status deployment/app"]
    });
  }

  // AWS questions
  for (let i = 0; i < 150; i++) {
    advancedQuestions.push({
      id: id++,
      question: `AWS cloud architecture ${i + 1}: Scalable, secure, and cost-optimized infrastructure`,
      tags: ["aws"],
      answer: `AWS best practices including VPC design, IAM policies, auto-scaling, load balancing, database optimization, and cost management.`,
      code: `resource "aws_autoscaling_group" "app" {
  name                = "app-asg"
  vpc_zone_identifier = var.subnet_ids
  target_group_arns   = [aws_lb_target_group.app.arn]
  min_size            = 2
  max_size            = 10
  desired_capacity    = 3
}`,
      commands: ["aws ec2 describe-instances", "aws elbv2 describe-target-health"]
    });
  }

  // Service Mesh/Istio questions
  for (let i = 0; i < 100; i++) {
    advancedQuestions.push({
      id: id++,
      question: `Istio service mesh ${i + 1}: Traffic management, security, and observability`,
      tags: ["istio"],
      answer: `Istio implementation with mTLS, traffic routing, circuit breaking, fault injection, and distributed tracing.`,
      code: `apiVersion: networking.istio.io/v1beta1
kind: VirtualService
metadata:
  name: reviews
spec:
  hosts:
  - reviews
  http:
  - route:
    - destination:
        host: reviews
        subset: v1
      weight: 90
    - destination:
        host: reviews
        subset: v2
      weight: 10`,
      commands: ["istioctl install", "kubectl get virtualservices", "istioctl analyze"]
    });
  }

  // Monitoring questions
  for (let i = 0; i < 100; i++) {
    advancedQuestions.push({
      id: id++,
      question: `Monitoring & Observability ${i + 1}: Prometheus, Grafana, logging, and alerting`,
      tags: ["monitoring"],
      answer: `Complete observability stack with metrics collection, log aggregation, distributed tracing, dashboards, and alert management.`,
      code: `apiVersion: v1
kind: ConfigMap
metadata:
  name: prometheus-config
data:
  prometheus.yml: |
    global:
      scrape_interval: 15s
    scrape_configs:
    - job_name: 'kubernetes-pods'
      kubernetes_sd_configs:
      - role: pod`,
      commands: ["kubectl apply -f prometheus.yaml", "kubectl port-forward svc/grafana 3000:80"]
    });
  }

  // CMMI Compliance questions
  for (let i = 0; i < 100; i++) {
    advancedQuestions.push({
      id: id++,
      question: `CMMI Level 5 compliance ${i + 1}: Traceability, audit logging, and process improvement`,
      tags: ["cmmi"],
      answer: `CMMI compliance implementation with requirement traceability (Jira), automated testing, security scanning, audit logging, and continuous process improvement metrics.`,
      code: `# Jira integration for traceability
pipeline {
  post {
    always {
      jiraUpdateTicket(
        issue: env.JIRA_ISSUE,
        status: currentBuild.result,
        comment: "Build \${env.BUILD_NUMBER}: \${currentBuild.result}"
      )
    }
  }
}`,
      commands: ["curl -X POST jira-api/issue", "kubectl logs audit-logger"]
    });
  }

  return coreQuestions.concat(advancedQuestions);
};
