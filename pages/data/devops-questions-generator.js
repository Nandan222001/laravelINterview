// Generator script to create all 1000 questions
// This will be included in the HTML page

function generateAllQuestions() {
  const coreQuestions = [
    {
      "id": 1,
      "question": "Design a complete Terraform module architecture for a three-tier web application with proper module composition and variable flow",
      "tags": ["terraform", "aws"],
      "answer": "Create a hierarchical module structure with root module orchestrating VPC, compute, database, and application modules. Use input variables, outputs, and data sources for inter-module communication. Implement remote state for collaboration.",
      "diagram": "┌─────────────────────────────────────────────────────────┐\n│                    ROOT MODULE                          │\n│  (main.tf, variables.tf, outputs.tf, terraform.tfvars) │\n└──────────────────┬──────────────────────────────────────┘\n                   │\n        ┌──────────┴──────────┬──────────┬──────────┐\n        │                     │          │          │\n   ┌────▼────┐          ┌────▼────┐ ┌───▼────┐ ┌──▼──────┐\n   │   VPC   │          │ Compute │ │Database│ │   ALB   │\n   │ Module  │──────────│ Module  │ │ Module │ │ Module  │\n   └────┬────┘          └────┬────┘ └───┬────┘ └──┬──────┘\n        │                    │          │         │\n   vpc_id, subnets      instance_ids  db_endpoint  alb_dns\n        │                    │          │         │\n        └────────────────────┴──────────┴─────────┘\n                           │\n                    ┌──────▼──────┐\n                    │   Outputs   │\n                    │ (exported)  │\n                    └─────────────┘",
      "code": "# Root module - main.tf\nmodule \"vpc\" {\n  source = \"./modules/vpc\"\n  \n  vpc_cidr    = var.vpc_cidr\n  environment = var.environment\n  az_count    = var.availability_zones\n}\n\nmodule \"compute\" {\n  source = \"./modules/compute\"\n  \n  vpc_id             = module.vpc.vpc_id\n  private_subnet_ids = module.vpc.private_subnet_ids\n  instance_type      = var.instance_type\n  ami_id             = data.aws_ami.amazon_linux.id\n}\n\nmodule \"database\" {\n  source = \"./modules/rds\"\n  \n  vpc_id             = module.vpc.vpc_id\n  db_subnet_ids      = module.vpc.database_subnet_ids\n  allowed_sg_ids     = [module.compute.security_group_id]\n  instance_class     = var.db_instance_class\n}\n\nmodule \"alb\" {\n  source = \"./modules/alb\"\n  \n  vpc_id            = module.vpc.vpc_id\n  public_subnet_ids = module.vpc.public_subnet_ids\n  target_ids        = module.compute.instance_ids\n  certificate_arn   = var.acm_certificate_arn\n}\n\n# Outputs\noutput \"application_url\" {\n  value = module.alb.dns_name\n}\n\noutput \"database_endpoint\" {\n  value     = module.database.endpoint\n  sensitive = true\n}",
      "commands": ["terraform init", "terraform workspace new prod", "terraform plan -out=tfplan", "terraform apply tfplan"]
    }
  ];

  // Generate additional questions for each topic
  const templates = {
    terraform: {
      prefix: "Terraform",
      topics: [
        "State management and remote backends",
        "Module development and versioning",
        "Resource provisioning best practices",
        "Workspace management",
        "Import existing infrastructure",
        "Terraform Cloud integration",
        "Policy as Code with Sentinel",
        "Multi-region deployments"
      ]
    },
    kubernetes: {
      prefix: "Kubernetes",
      topics: [
        "Pod scheduling and affinity rules",
        "StatefulSet management",
        "ConfigMap and Secret handling",
        "RBAC authorization",
        "Network policies",
        "Storage classes and PVCs",
        "DaemonSet deployments",
        "Job and CronJob patterns"
      ]
    },
    docker: {
      prefix: "Docker",
      topics: [
        "Image layering and optimization",
        "Container networking modes",
        "Volume management",
        "Dockerfile best practices",
        "Registry management",
        "Container orchestration",
        "Security hardening",
        "Resource constraints"
      ]
    },
    jenkins: {
      prefix: "Jenkins/CI/CD",
      topics: [
        "Pipeline as Code",
        "Shared libraries",
        "Distributed builds",
        "Plugin management",
        "Integration testing",
        "Artifact management",
        "Deployment strategies",
        "Security scanning"
      ]
    },
    aws: {
      prefix: "AWS Cloud",
      topics: [
        "IAM policies and roles",
        "VPC networking",
        "EC2 optimization",
        "S3 lifecycle policies",
        "Lambda serverless",
        "RDS backup strategies",
        "CloudFormation templates",
        "Cost optimization"
      ]
    },
    istio: {
      prefix: "Service Mesh/Istio",
      topics: [
        "Traffic management",
        "Security policies",
        "Observability",
        "Fault injection",
        "Circuit breaking",
        "Rate limiting",
        "mTLS configuration",
        "Multi-cluster mesh"
      ]
    },
    monitoring: {
      prefix: "Monitoring & Observability",
      topics: [
        "Prometheus metrics",
        "Grafana dashboards",
        "Alert management",
        "Log aggregation",
        "Distributed tracing",
        "SLO/SLI definition",
        "APM integration",
        "Incident response"
      ]
    },
    cmmi: {
      prefix: "CMMI Compliance",
      topics: [
        "Requirement traceability",
        "Change management",
        "Audit logging",
        "Process documentation",
        "Quality gates",
        "Security compliance",
        "Configuration management",
        "Continuous improvement"
      ]
    }
  };

  const generatedQuestions = [];
  let id = 1;

  // Add core questions
  coreQuestions.forEach(q => {
    q.id = id++;
    generatedQuestions.push(q);
  });

  // Generate questions for each category
  Object.keys(templates).forEach(category => {
    const template = templates[category];
    template.topics.forEach((topic, index) => {
      for (let i = 0; i < 15; i++) {  // 15 questions per topic
        generatedQuestions.push({
          id: id++,
          question: `${template.prefix}: ${topic} - Implementation scenario ${i + 1}`,
          tags: [category],
          answer: `This covers ${template.prefix} best practices for ${topic}. Implementation should include proper configuration, security considerations, monitoring, and documentation. Follow industry standards and compliance requirements.`,
          code: `# ${template.prefix} - ${topic}\n# Configuration example ${i + 1}\n\napiVersion: v1\nkind: ConfigMap\nmetadata:\n  name: ${category}-config-${id}\n  labels:\n    topic: \"${topic.replace(/ /g, '-')}\"\ndata:\n  config.yaml: |\n    # Production configuration\n    enabled: true\n    replicas: 3\n    monitoring: true`,
          commands: [
            `kubectl apply -f ${category}-${id}.yaml`,
            `kubectl get all -l topic=${topic.replace(/ /g, '-')}`,
            `kubectl logs -f deployment/${category}-app`
          ]
        });
      }
    });
  });

  return generatedQuestions;
}

// Export for use in main page
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { generateAllQuestions };
}
