# AWS EKS Cluster Management and Operations

This document contains 100 comprehensive questions covering AWS EKS cluster management, node groups, networking, IAM integration, monitoring, and best practices for production Kubernetes on AWS.

## Questions 1-100: EKS Cluster Configuration and Management

### Question 1
**Q:** Create EKS cluster with Terraform including VPC, subnets, and node groups.

**A:** Use terraform-aws-modules/eks/aws module. Configure VPC with public/private subnets across 3 AZs. Enable cluster logging. Set up managed node groups with autoscaling. Configure IAM roles with OIDC provider. Enable encryption at rest with KMS. Set up cluster security groups. Configure kubectl access with AWS IAM Authenticator.

### Question 2-10
**Q:** Configure EKS node groups with spot and on-demand instances.

**A:** Mixed instance policies, capacity-optimized spot allocation, on-demand base capacity, spot interruption handling, node labels for workload placement, taints for dedicated nodes, launch templates for customization, user data for initialization, disk encryption, and instance metadata service v2.

### Question 11-20
**Q:** Implement EKS cluster autoscaling with Cluster Autoscaler and Karpenter.

**A:** Cluster Autoscaler deployment, IAM policies for autoscaling, node group scaling limits, Karpenter provisioner configuration, node consolidation, multi-AZ scaling, spot instance integration, scale-down delays, priority expander configuration, and monitoring autoscaling events.

### Question 21-30
**Q:** Configure EKS networking with VPC CNI and secondary IP addresses.

**A:** VPC CNI configuration, prefix delegation for more IPs, custom networking for separate subnets, security groups for pods, network policies with Calico, ENI trunking, IPv6 support, service mesh integration, ingress controllers, and DNS configuration.

### Question 31-40
**Q:** Set up EKS IAM roles for service accounts (IRSA).

**A:** OIDC provider configuration, IAM role creation with trust policy, Kubernetes service account annotation, pod IAM role assumption, fine-grained permissions, audit logging, role chaining prevention, cross-account access, and security best practices.

### Question 41-50
**Q:** Implement EKS secrets management with AWS Secrets Manager.

**A:** Secrets Store CSI Driver installation, SecretProviderClass configuration, pod volume mounts, automatic rotation, IAM permissions, encryption at rest, audit logging, secret versioning, emergency access procedures, and compliance requirements.

### Question 51-60
**Q:** Configure EKS logging and monitoring with CloudWatch and Prometheus.

**A:** Control plane logging, application logs forwarding, CloudWatch Container Insights, Prometheus monitoring, Grafana dashboards, log aggregation, metric collection, alerting rules, cost optimization, and retention policies.

### Question 61-70
**Q:** Set up EKS backup and disaster recovery with Velero.

**A:** Velero installation with AWS plugin, S3 bucket configuration, backup schedules, persistent volume snapshots, cluster migration procedures, restore testing, backup validation, retention policies, cross-region backup, and disaster recovery drills.

### Question 71-80
**Q:** Implement EKS security with Pod Security Standards and policies.

**A:** Pod Security Standards enforcement, OPA Gatekeeper policies, network policies, runtime security with Falco, image scanning, admission controllers, encryption everywhere, audit logging, compliance scanning, and security monitoring.

### Question 81-90
**Q:** Configure EKS cost optimization strategies.

**A:** Spot instance usage, right-sizing nodes, Karpenter for optimization, cluster autoscaling, resource quotas, pod disruption budgets, reserved instances, savings plans, cost allocation tags, and monitoring with Kubecost.

### Question 91-100
**Q:** Implement EKS multi-cluster management and federation.

**A:** Multiple cluster deployment, cross-cluster networking, unified monitoring, centralized authentication, policy enforcement, disaster recovery setup, workload distribution, service mesh federation, GitOps deployment, and governance frameworks.
