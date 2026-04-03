# DevOps, Cloud & Kubernetes - Complete Answers

1. Kubernetes (K8s) orchestrates containerized applications at scale. Manages deployment, scaling, networking, storage. Nodes run container runtime, control plane manages cluster. Ideal for microservices, high availability, horizontal scaling.

2. Docker containerization packages application with dependencies in image. Build from Dockerfile, run containers from image. Lightweight VMs, fast startup, consistent environment across dev/staging/production.

3. Multi-stage Docker builds reduce image size. Compile/build in first stage, copy artifacts to final stage, avoid shipping build dependencies. Example: Alpine base, compile in builder, copy binary.

4. CI/CD pipelines automate build, test, deploy. GitHub Actions, Jenkins, GitLab CI execute on code push. Run tests, build artifacts, deploy to servers/containers. Faster feedback, fewer manual errors.

5. Jenkins declarative pipelines define build steps as code. Stages (build, test, deploy), agents (runners), tools (Docker, Maven), post-build actions, notifications.

6. GitHub Actions workflows trigger on events (push, PR), run jobs in parallel, each job has steps (checkout, build, test, deploy). Use community actions, reuse workflows across repos.

7. Infrastructure as Code (IaC) defines infrastructure declaratively. Terraform, CloudFormation, Ansible describe resources, version control, repeat deployment. Benefits: reproducibility, disaster recovery, change tracking.

8. Terraform provisioning: define providers (AWS, GCP, Azure), resources (instances, databases), state file tracks real infrastructure. `terraform plan` previews changes, `terraform apply` makes changes.

9. Service mesh (Istio, Linkerd) manages service-to-service communication. Handles routing, retry, circuit breaking, mTLS encryption, observability without app code changes.

10. Istio mTLS: automatic mutual TLS between services. Transparently encrypts traffic, verifies identity via certificates. Zero-trust security at network level.

11. AWS EC2 instances: virtual machines in cloud. Choose instance type (compute, memory, storage optimized), configure security groups, assign elastic IP for static address. Scaling: auto-scaling groups launch/terminate based on load.

12. AWS RDS: managed relational databases. AWS handles backups, patches, failover. Multi-AZ for high availability, read replicas for scaling reads. Costs more than self-managed but less operational burden.

13. AWS S3: object storage for files, backups, static assets. Unlimited scalability, high durability, versioning, lifecycle policies. CDN integration (CloudFront) for fast delivery.

14. AWS Lambda: serverless compute. Functions respond to events (API, database, schedule), scale automatically, pay per invocation. No server management, ideal for event-driven architectures.

15. AWS ECS: container orchestration (alternative to Kubernetes). Simpler than K8s, tighter AWS integration, supports EC2 and Fargate (serverless). Good for AWS-native apps.

16. AWS EKS: Kubernetes service. AWS manages control plane, you manage worker nodes. Integrates with AWS services, familiar K8s ecosystem, operational overhead between ECS and self-managed K8s.

17. GCP Cloud Run: serverless container platform. Deploy Docker image, Cloud Run handles scaling, routing. Pay per request. Easy migration from Lambda (container-based).

18. Azure App Service: managed web app hosting. Supports .NET, Node.js, Python, Java. Integrated CI/CD from GitHub, automatic scaling, HTTPS, custom domains.

19. Azure Kubernetes Service (AKS): managed Kubernetes. Azure manages control plane, you provision nodes. Integrates with Azure DevOps, Azure Container Registry, easier than self-managed K8s.

20. GCP Compute Engine: virtual machines (like EC2). Instance groups, load balancing, Cloud CDN. Simpler pricing model than AWS (per-minute billing).

21. Load balancing: distribute traffic across multiple servers. Layer 4 (network, TCP/UDP) or Layer 7 (application, HTTP). AWS ELB/ALB, GCP Load Balancer, Azure Load Balancer, NGINX.

22. Horizontal scaling: add more servers to handle traffic. Stateless applications scale easily. Requires load balancer, distributed session storage, database replication.

23. Vertical scaling: increase server resources (CPU, RAM). Limited by hardware, causes downtime when scaling. Usually cheaper initially, hits limits eventually.

24. Autoscaling policies: CPU-based (scale when CPU >70%), custom metrics (requests/sec), schedule-based (scale at specific times). Cloud providers provide autoscaling groups/resources.

25. Health checks: load balancer periodically checks if instance healthy. Failed checks remove instance from rotation, new instance launched. Ensures only healthy instances receive traffic.

26. Database replication: primary database replicates writes to replicas. Read replicas handle read load, primary handles writes. Increases read capacity, RPO/RTO for DR.

27. Database backup strategies: daily snapshots, transaction logs, point-in-time recovery. AWS RDS automated backups, GCP Cloud SQL automated backups. Regular restore tests ensure backups valid.

28. Database failover: automatic failover to replica if primary fails. Requires replication lag <few seconds, same data center or cross-region. AWS RDS multi-AZ provides automatic failover.

29. Disaster recovery (DR): RTO (Recovery Time Objective - how fast to recover), RPO (Recovery Point Objective - acceptable data loss). Warm standby (quick recovery, high cost), cold standby (slower recovery, lower cost).

30. Monitoring and alerting: track metrics (CPU, memory, network, application), set thresholds, alert when exceeded. Tools: CloudWatch, Prometheus, Datadog, New Relic.

31. Logging and observability: central log aggregation (ELK stack, Splunk), distributed tracing (Jaeger, Zipkin), metrics (Prometheus), alerts. Essential for debugging production issues.

32. Blue-green deployment: run two identical production environments. Deploy to green, test, switch traffic from blue to green. Fast rollback by switching back to blue.

33. Canary deployment: deploy to small percentage of servers first, monitor metrics, gradually increase traffic. Catch issues early, slow rollout reduces risk.

34. Rolling deployment: update servers one at a time, taking from load balancer, updating, adding back. Zero-downtime, but slower than blue-green.

35. Feature flags: deploy code but disable features with flags. Decouple deployment from feature release, A/B test features, quick rollback by toggling flag.

36. GitOps: declare desired infrastructure/application state in Git, reconciliation controller ensures actual state matches desired. Enables version control, audit trail, easy rollback.

37. Secret management: store credentials, API keys, certificates securely. AWS Secrets Manager, HashiCorp Vault, Azure Key Vault. Never commit secrets to Git, use environment variables/files.

38. Container registry: store Docker images. AWS ECR, Docker Hub, Google Container Registry. Private registries for proprietary images, image scanning for vulnerabilities.

39. Network segmentation: separate networks for different components. Security groups restrict traffic between segments. Prevent lateral movement if one segment breached.

40. VPN/VPC: Virtual Private Cloud isolates cloud resources. Private subnets for databases, public subnets for web servers. Prevents direct internet access to sensitive resources.

41. Content delivery network (CDN): cache static assets geographically close to users. CloudFront (AWS), Cloud CDN (GCP), Azure CDN. Reduces origin load, improves latency.

42. DNS management: Route53 (AWS), Cloud DNS (GCP), Azure DNS. Routing policies (simple, weighted, latency-based, failover), health checks.

43. Certificate management: SSL/TLS certificates for HTTPS. AWS Certificate Manager (free), Let's Encrypt (free, automated), paid CAs. Auto-renewal prevents expiry.

44. Rate limiting/DDoS protection: prevent abuse. WAF rules, traffic shaping, IP blocking. AWS Shield Standard (included), Shield Advanced (DDoS protection).

45. Container security: scan images for vulnerabilities, use minimal base images (Alpine), don't run as root, read-only filesystems where possible.

46. Kubernetes networking: pods communicate via cluster DNS, services expose pods, ingress routes external traffic. Network policies restrict communication between pods.

47. Kubernetes storage: persistent volumes (network-attached), storage classes (provisioning), persistent volume claims (request storage). StatefulSets for stateful apps (databases).

48. Helm: package manager for Kubernetes. Charts define resources (deployments, services, configmaps), parameterized for different environments. Simplifies Kubernetes manifest management.

49. Service mesh observability: see all service-to-service communication, latency, error rates. Kiali (Istio), Grafana (Prometheus), trace every request without app code changes.

50. Prometheus metrics: collect metrics from applications, scrape periodically, store in time-series database. Query with PromQL, visualize in Grafana, alert with Alertmanager.

51. ELK stack: Elasticsearch (storage), Logstash (processing), Kibana (visualization). Centralize logs from all services, powerful searching and analysis.

52. Grafana dashboards: visualize metrics from Prometheus, CloudWatch, custom sources. Build custom dashboards, set up alerts based on metrics.

53. PagerDuty/Incident management: on-call schedules, escalation policies, incident response. Integrated with monitoring tools, sends alerts to on-call engineer.

54. Kubernetes deployments: define replicas, image, resources, health checks. Rolling updates, automatic rollback on failed health checks. Manages pod lifecycle.

55. Kubernetes stateful sets: for stateful applications (databases). Persistent naming, ordered scaling, persistent storage. More complex than deployments.

56. Kubernetes jobs: one-off tasks, run to completion. Useful for batch processing, migrations, backups. CronJob for scheduled execution.

57. ConfigMaps and Secrets: ConfigMaps for non-sensitive config, Secrets for sensitive data. Inject into pods as environment variables or files.

58. Kubernetes RBAC: role-based access control. Roles define permissions (get pods, create services), bindings assign roles to users/service accounts. Fine-grained access control.

59. Pod security policies: enforce security standards on pods (run as non-root, read-only fs, no privileged containers). Deprecated in K8s 1.25+, replaced by Pod Security Standards.

60. Network policies: restrict pod-to-pod communication. Default deny, explicitly allow required connections. Reduces blast radius of breached pods.

61. Kubernetes ingress: routes external traffic to services. Can do path-based routing, host-based routing, TLS termination. Requires ingress controller (NGINX, AWS ALB).

62. Kubernetes resource limits: set CPU/memory requests (minimum needed) and limits (maximum allowed). Prevents resource starvation, enables proper scheduling.

63. Pod disruption budgets: specify minimum available pods during voluntary disruptions (node drains, cluster upgrades). Ensures service availability during maintenance.

64. Kubernetes namespaces: logical isolation of resources. Separate dev, staging, production. RBAC scoped to namespace, resource quotas per namespace.

65. Kubernetes observability: collect logs (Fluentd), metrics (Prometheus), traces (Jaeger) from all pods. Central store, analyze across entire cluster.

66. Istio traffic management: fine-grained routing rules, traffic shifting (canary), circuit breakers, retries. VirtualServices and DestinationRules define routing policy.

67. Istio security: mTLS between services, authorization policies, certificate management. Transparent to applications.

68. Kubernetes operators: extend Kubernetes with custom resources. Define complex application deployment patterns, manage application lifecycle (backup, restore, upgrade).

69. StatefulSets for databases: maintain pod identity, stable network names, ordered scaling. Use with persistent volumes for durable storage.

70. DaemonSets: run pod on every node. Useful for logging agents, monitoring agents, networking plugins. Ensures coverage across entire cluster.

71. Kubernetes multi-tenancy: use namespaces for logical separation, network policies for network isolation, RBAC for access control. Limits resources per tenant with quotas.

72. GitOps with ArgoCD: declare desired state in Git, ArgoCD syncs cluster to match Git. Enables audit trail, easy rollback, policy enforcement.

73. Sealed Secrets: encrypt secrets with cluster public key, store encrypted secrets in Git safely. Only cluster with private key can decrypt.

74. Kubernetes cost optimization: right-size resources, use spot instances (preemptible VMs), consolidate workloads on fewer nodes, use auto-scaling.

75. Kubernetes cluster upgrades: upgrade control plane, then node pools (drain, upgrade, uncordon). Rolling upgrade with health checks, rollback if issues.

76. Kubernetes node pools: different node types (compute optimized, GPU, high-memory) in same cluster. Schedule pods to specific pools with node selectors.

77. Kubernetes pod affinities: schedule pods together (same node, same zone) for performance, or apart for resilience. Anti-affinity prevents single point of failure.

78. Terraform modules: reusable infrastructure components. Define module once, use multiple places with different parameters. Simplifies large infrastructure.

79. Terraform remote state: store state in remote backend (S3, Consul) instead of local. Enables team collaboration, prevents state loss.

80. Terraform locking: prevent concurrent modifications to state. S3 + DynamoDB provides locking, prevents corruption from parallel applies.

81. CloudFormation stack sets: deploy stacks across multiple AWS accounts/regions. Convenient for multi-tenancy, disaster recovery across regions.

82. CloudFormation change sets: preview changes before applying. See what resources will be created/updated/deleted. Safer than direct applies.

83. Ansible playbooks: define infrastructure configuration declaratively. Idempotent (safe to run multiple times), agentless (only needs SSH). Good for configuration management.

84. Configuration management: ensure servers have correct software, config files, users. Ansible, Chef, Puppet maintain desired configuration state.

85. Jenkins pipelines with Docker: build, test, push to registry in pipeline. Deploy to Kubernetes/servers from registry. Reproducible builds.

86. GitLab CI parallel jobs: run multiple jobs simultaneously. Speed up CI/CD, split testing across jobs. Lower overall pipeline duration.

87. Cloud resource quotas: set limits on resource usage per environment/team. Prevents runaway costs, ensures fair resource allocation.

88. VPC peering: connect two VPCs securely. Share resources across VPCs, multi-tenancy architecture. Alternative: private link for narrower access.

89. NAT gateway: enables outbound internet from private subnet without exposing private IPs. Incoming traffic blocked (stateful firewall).

90. Bastion host: jump box in public subnet, SSH into private resources from there. Reduces attack surface, centralized access control.

91. AWS Lambda cold starts: initial function invocation slow (few seconds) as environment initialized. Provisioned concurrency keeps instances warm, eliminates cold start.

92. Serverless databases: DynamoDB (NoSQL), Firestore, Aurora Serverless for RDBMS. Auto-scale, pay per request, no capacity planning. Trade-off: less control, potential latency.

93. Infrastructure testing: test IaC with tools (Terraform validate, CloudFormation lint). Test deployed infrastructure (serverspec, InSpec). Ensures infrastructure as expected.

94. Cost optimization: right-size instances, use reserved instances (discount), spot instances (cheaper), shutdown unused resources. Monitor cost alerts.

95. Chaos engineering: intentionally break things in production (under controlled conditions) to build resilience. Tools: Gremlin, Chaos Toolkit. Identifies weak points.

96. Observability vs monitoring: monitoring answers "what happened", observability answers "why did it happen". Logs, metrics, traces together provide observability.

97. Distributed tracing: follow request through multiple services, see latency at each step. Jaeger, Zipkin, X-Ray show service call graph and bottlenecks.

98. SLI/SLO/SLA: SLI (Service Level Indicator - metric), SLO (target for SLI, e.g., 99.9% uptime), SLA (contractual commitment with penalties). Drives reliability investment.

99. Incident response: on-call rotation, incident commander, runbooks for common issues, post-mortems for major incidents. Blameless culture improves response.

100. Production readiness review: checklist before deploying to production. Covers monitoring, logging, security, disaster recovery, scaling, documentation. Ensures production readiness.

