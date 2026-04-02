# Kubernetes Production Configurations

This document contains 100 comprehensive questions covering Kubernetes production configurations including HPA with custom metrics, Ingress controllers with TLS termination, and advanced orchestration patterns.

## Questions 1-20: Horizontal Pod Autoscaler (HPA) with Custom Metrics

### Question 1
**Q:** Configure HPA with CPU and memory metrics for a Laravel application deployment.

**A:** Create HPA resource with scaleTargetRef pointing to deployment. Set minReplicas and maxReplicas. Define metrics for CPU (targetAverageUtilization: 70) and memory. Requires metrics-server installed. HPA controller adjusts replicas based on observed metrics. Check with kubectl get hpa.

### Question 2
**Q:** Implement HPA with custom Prometheus metrics for request rate scaling.

**A:** Install Prometheus and Prometheus Adapter. Configure adapter to expose custom metrics API. Create HPA with type: Pods and metric: requests_per_second. Set targetAverageValue. Adapter queries Prometheus for application metrics. Scales based on business metrics.

### Question 3
**Q:** Design HPA with external metrics from CloudWatch for queue depth.

**A:** Install CloudWatch metrics adapter for Kubernetes. Configure external metric type in HPA. Query SQS queue depth from CloudWatch. Set targetAverageValue for queue processing rate. Scales pods based on queue backlog. Integrates AWS metrics with K8s autoscaling.

### Question 4
**Q:** Configure HPA behavior for controlled scale-up and scale-down.

**A:** Use behavior field in HPA spec. Define scaleUp and scaleDown policies. Set stabilizationWindowSeconds to prevent flapping. Configure selectPolicy (Max, Min, Disabled). Set periodSeconds and type (Pods or Percent). Provides fine-grained control over scaling speed.

### Question 5
**Q:** Implement HPA with multiple metrics using targetAverageUtilization.

**A:** Define multiple metrics in HPA spec array. Combine CPU, memory, and custom metrics. HPA uses maximum value across all metrics. Each metric can have different targets. Ensures all resource constraints are met. More robust than single metric.

### Question 6
**Q:** Set up Vertical Pod Autoscaler (VPA) alongside HPA for resource optimization.

**A:** Install VPA components (recommender, updater, admission controller). Create VPA with updateMode: Auto or Recreate. VPA adjusts resource requests/limits. Don't use VPA and HPA on same CPU/memory metrics. Use VPA for right-sizing, HPA for scaling.

### Question 7
**Q:** Configure HPA with object metrics for custom resource scaling.

**A:** Use object metric type in HPA. Reference specific K8s object (Ingress, Service). Query custom metric associated with object. Example: active connections on Ingress. Set targetValue for scaling decision. Scales based on infrastructure-level metrics.

### Question 8
**Q:** Implement HPA with ContainerResource metrics for multi-container pods.

**A:** Use containerResource metric type. Specify container name in metric spec. Target specific container's resource usage. Useful for sidecars with different resource patterns. Separate scaling logic per container. Available in K8s 1.20+.

### Question 9
**Q:** Design HPA testing strategy with load testing tools.

**A:** Use tools like k6, JMeter, Locust for load generation. Monitor HPA with kubectl get hpa --watch. Check metrics with kubectl top pods. Verify scale-up triggers at target utilization. Test scale-down after load reduction. Validate stabilization windows.

### Question 10
**Q:** Configure HPA for burstable workloads with predictive scaling.

**A:** Use Kubernetes Event-driven Autoscaling (KEDA) for advanced scenarios. Configure ScaledObject with multiple triggers. Implement predictive scaling with cron-based triggers. Scale proactively before traffic spikes. Combine reactive and predictive approaches.

### Question 11
**Q:** Implement HPA with API server metrics for control plane load.

**A:** Expose API server metrics via service monitor. Use Prometheus to scrape metrics. Configure HPA with custom metric from API response times. Scale backends based on API server pressure. Useful for API-heavy applications.

### Question 12
**Q:** Set up HPA with datadog metrics for application performance.

**A:** Install Datadog Cluster Agent with external metrics support. Configure DatadogMetric CRD. Create HPA referencing external metric. Query custom application metrics from Datadog. Scale based on APM data like error rates, latency.

### Question 13
**Q:** Configure HPA cooldown periods to prevent thrashing.

**A:** Use behavior.scaleDown.stabilizationWindowSeconds (default 300s). Set behavior.scaleUp.stabilizationWindowSeconds for gradual scale-up. Adjust policies for faster or slower scaling. Prevents rapid scaling oscillations. Balance responsiveness with stability.

### Question 14
**Q:** Implement HPA with percentile-based metrics from New Relic.

**A:** Install New Relic metrics adapter. Query P95/P99 latency metrics. Configure HPA with external metric type. Set targetValue based on latency SLA. Scale when high percentile latency degrades. Better than average for user experience.

### Question 15
**Q:** Design HPA with cost optimization using spot instances.

**A:** Use Cluster Autoscaler with mixed instance types. Configure node affinity for spot instances. Set HPA to scale on spot nodes first. Use pod disruption budgets for fault tolerance. Implement graceful shutdown handlers. Balance cost with availability.

### Question 16
**Q:** Configure HPA limits with min/max constraints for cost control.

**A:** Set minReplicas to maintain minimum capacity. Set maxReplicas to cap maximum cost. Use resource quotas at namespace level. Implement LimitRanges for pod resource bounds. Monitor costs with Kubecost or CloudHealth.

### Question 17
**Q:** Implement HPA with custom Python metrics exporter.

**A:** Create Python service exposing /metrics endpoint. Implement Prometheus client library. Export custom business metrics. Register ServiceMonitor for Prometheus scraping. Configure HPA with custom metric via Prometheus adapter.

### Question 18
**Q:** Set up HPA dashboard in Grafana for monitoring.

**A:** Create Grafana dashboard querying Prometheus. Display current vs desired replicas. Show metric values driving scaling decisions. Graph scaling events over time. Alert on HPA failures or limit reached. Integrate with PagerDuty/Slack.

### Question 19
**Q:** Configure HPA for stateful applications with ordered scaling.

**A:** Use StatefulSet instead of Deployment. HPA works with StatefulSets (K8s 1.9+). Scale-up creates pods sequentially. Scale-down removes highest ordinal first. Coordinate with persistent volume claims. Test data consistency during scaling.

### Question 20
**Q:** Implement HPA troubleshooting workflow for scaling issues.

**A:** Check HPA status with kubectl describe hpa. Verify metrics-server/adapter is running. Check RBAC permissions for HPA controller. Validate metric queries return values. Review HPA controller logs. Test metric endpoint manually.

## Questions 21-40: Ingress Controllers with TLS Termination

### Question 21
**Q:** Deploy NGINX Ingress Controller with Helm in production environment.

**A:** Add NGINX Ingress Helm repo. Install with helm install nginx-ingress. Configure with LoadBalancer service type. Set resource limits and requests. Enable metrics and Prometheus monitoring. Configure ConfigMap for global settings. Use dedicated ingress-nginx namespace.

### Question 22
**Q:** Configure Ingress resource with TLS termination using Let's Encrypt certificates.

**A:** Install cert-manager for automatic certificate management. Create ClusterIssuer for Let's Encrypt. Define Ingress with tls section specifying secretName and hosts. Cert-manager creates Certificate and Secret automatically. Certificates auto-renew before expiration. Use HTTP-01 or DNS-01 challenge.

### Question 23
**Q:** Implement wildcard TLS certificates for multiple subdomains.

**A:** Use DNS-01 challenge type (required for wildcards). Configure ClusterIssuer with DNS provider credentials. Create Certificate with dnsNames: ['*.example.com']. Reference in Ingress tls section. Single certificate covers all subdomains. More cost-effective than individual certs.

### Question 24
**Q:** Set up Ingress with custom TLS certificate from corporate CA.

**A:** Create Secret with type kubernetes.io/tls. Include tls.crt and tls.key from corporate CA. Reference secret in Ingress tls section. Configure clients to trust corporate CA root certificate. For internal services and private networks.

### Question 25
**Q:** Configure Ingress with path-based routing to multiple services.

**A:** Define multiple paths in Ingress rules. Each path routes to different backend service. Use pathType: Prefix or Exact. Order paths from most to least specific. Common pattern: /api -> api-service, / -> frontend-service. Test routing with curl.

### Question 26
**Q:** Implement Ingress with host-based routing for multi-tenant applications.

**A:** Define multiple hosts in Ingress spec. Each host can have different TLS certificate. Route to different services per subdomain. Example: app1.example.com, app2.example.com. Use IngressClassName for different ingress controllers per tenant.

### Question 27
**Q:** Set up Ingress with rate limiting and request throttling.

**A:** Configure NGINX Ingress annotations: nginx.ingress.kubernetes.io/limit-rps. Set limit-connections for concurrent requests. Use limit-whitelist for exceptions. Apply per Ingress resource. Protects backends from overload. Returns 503 when limits exceeded.

### Question 28
**Q:** Configure Ingress with authentication using OAuth2 Proxy.

**A:** Deploy oauth2-proxy as separate service. Configure Ingress annotations for auth-url and auth-signin. Set up OAuth provider (Google, GitHub, Okta). Proxy validates tokens before forwarding. Enables SSO for any backend. No application code changes.

### Question 29
**Q:** Implement Ingress with basic authentication for staging environments.

**A:** Create Secret with htpasswd file: kubectl create secret generic basic-auth --from-file=auth. Add annotation nginx.ingress.kubernetes.io/auth-type: basic. Reference secret with auth-secret annotation. Simple authentication without external dependencies.

### Question 30
**Q:** Set up Ingress with custom error pages for better UX.

**A:** Create ConfigMap with custom HTML error pages. Mount in NGINX Ingress controller. Configure custom-http-errors annotation. Specify error codes (404, 503). Deploy default backend service. Branded error pages for professional appearance.

### Question 31
**Q:** Configure Ingress with request/response header manipulation.

**A:** Use annotations for header control. nginx.ingress.kubernetes.io/configuration-snippet for custom config. Add CORS headers with enable-cors. Set custom headers with proxy-set-headers. Remove headers with hide-headers. Useful for security and compatibility.

### Question 32
**Q:** Implement Ingress with session affinity for stateful applications.

**A:** Set annotation nginx.ingress.kubernetes.io/affinity: cookie. Configure cookie name and expiration. Ensure requests from same client go to same pod. Alternative: use session affinity in Service spec. Important for applications storing session in memory.

### Question 33
**Q:** Set up Ingress with IP whitelisting for admin interfaces.

**A:** Use annotation nginx.ingress.kubernetes.io/whitelist-source-range. Specify CIDR blocks: 10.0.0.0/8,192.168.0.0/16. Multiple ranges comma-separated. Blocks return 403. Protects sensitive endpoints. Combine with authentication for defense in depth.

### Question 34
**Q:** Configure Ingress with WebSocket support for real-time applications.

**A:** NGINX Ingress supports WebSockets by default. Ensure backend service handles upgrades. Configure proxy timeouts for long-lived connections. Set proxy-read-timeout and proxy-send-timeout. Test with wscat or similar tool. Monitor connection count.

### Question 35
**Q:** Implement Ingress with gRPC backend services.

**A:** Set annotation nginx.ingress.kubernetes.io/backend-protocol: GRPC. Configure HTTP/2 in Ingress controller. Backend service must support HTTP/2. Use TLS for production gRPC. Test with grpcurl. Monitor with gRPC-specific metrics.

### Question 36
**Q:** Set up Ingress with request size limits for upload protection.

**A:** Configure nginx.ingress.kubernetes.io/proxy-body-size annotation. Set value like 10m for 10 megabytes. Protects from large upload attacks. Returns 413 when exceeded. Adjust per endpoint needs. Consider CloudFront/CDN for large uploads.

### Question 37
**Q:** Configure Ingress with custom timeouts for long-running requests.

**A:** Set proxy-connect-timeout, proxy-send-timeout, proxy-read-timeout. Default 60 seconds may be too short. Use for batch processing, report generation. Set appropriately to avoid tying up connections. Monitor timeout errors in logs.

### Question 38
**Q:** Implement Ingress with ModSecurity WAF for security.

**A:** Enable ModSecurity in NGINX Ingress controller. Load OWASP Core Rule Set. Configure annotation nginx.ingress.kubernetes.io/enable-modsecurity. Set modsecurity-snippet for custom rules. Blocks common attacks (SQL injection, XSS). Test before production to avoid false positives.

### Question 39
**Q:** Set up Ingress with canary deployments for progressive rollouts.

**A:** Create two Ingress resources for stable and canary. Use annotations for canary weight. nginx.ingress.kubernetes.io/canary: true. Set canary-weight to percentage (0-100). Gradually increase canary traffic. Monitor error rates before full rollout.

### Question 40
**Q:** Configure Ingress monitoring with Prometheus and Grafana.

**A:** NGINX Ingress exposes /metrics endpoint. Create ServiceMonitor for Prometheus scraping. Monitor request rate, latency, status codes. Import NGINX Ingress dashboard in Grafana. Alert on high error rates or latency. Track SSL certificate expiration.

## Questions 41-60: Service Mesh with Istio and mTLS

### Question 41
**Q:** Install Istio service mesh in production Kubernetes cluster.

**A:** Download istioctl CLI tool. Run istioctl install with production profile. Deploys istiod control plane. Configure resource requests/limits. Enable HPA for istiod. Install ingress and egress gateways. Label namespaces for sidecar injection: kubectl label namespace default istio-injection=enabled.

### Question 42
**Q:** Configure automatic mTLS between services in Istio mesh.

**A:** Istio enables mTLS by default (permissive mode). Create PeerAuthentication resource with mtls mode: STRICT. Apply at namespace or mesh level. Citadel issues certificates automatically. 24-hour certificate lifetime with auto-rotation. Verify with istioctl authn tls-check.

### Question 43
**Q:** Implement Istio VirtualService for traffic routing and A/B testing.

**A:** Create VirtualService defining routing rules. Configure match conditions (headers, URI). Define weighted routing to subset versions. Example: 90% v1, 10% v2. Use for A/B tests, canary releases. Combine with DestinationRule for subset definitions.

### Question 44
**Q:** Set up Istio DestinationRule for connection pool and circuit breaking.

**A:** Create DestinationRule for service. Configure trafficPolicy with connectionPool settings. Set TCP and HTTP connection limits. Define outlierDetection for circuit breaking. Specify consecutive errors threshold. Eject unhealthy instances temporarily. Improves resiliency.

### Question 45
**Q:** Configure Istio Gateway for external traffic ingress with TLS.

**A:** Create Gateway resource with selector for istio-ingressgateway. Define servers with port, protocol, and hosts. Configure TLS with credentialName for certificate. Create VirtualService bound to Gateway. Route traffic to internal services. Alternative to Ingress controller.

### Question 46
**Q:** Implement Istio ServiceEntry for external service mesh integration.

**A:** Create ServiceEntry for external service. Specify hosts and ports. Set location: MESH_EXTERNAL. Configure resolution (DNS, STATIC). Enables traffic management for external APIs. Apply timeout and retry policies. Monitor external service calls.

### Question 47
**Q:** Set up Istio authorization policies for fine-grained access control.

**A:** Create AuthorizationPolicy with ALLOW or DENY action. Specify rules with from (source), to (operation), when (condition). Control access by service account, namespace, or JWT claims. Default deny all, explicitly allow. More secure than network policies alone.

### Question 48
**Q:** Configure Istio request authentication with JWT validation.

**A:** Create RequestAuthentication resource. Specify jwtRules with issuer and jwksUri. Validates JWT tokens in Authorization header. Extracts claims for authorization decisions. Integrates with OAuth providers. Use with AuthorizationPolicy for end-to-end security.

### Question 49
**Q:** Implement Istio retry and timeout policies for resilience.

**A:** Configure in VirtualService http route. Set timeout for max request duration. Define retries with attempts and perTryTimeout. Specify retryOn conditions (5xx, refused-stream). Add backoff intervals. Improves service reliability. Monitor retry rates.

### Question 50
**Q:** Set up Istio fault injection for chaos engineering.

**A:** Use VirtualService fault injection. Configure abort with httpStatus and percentage. Define delay with fixedDelay and percentage. Test application resilience. Verify circuit breakers and retries work. Run in test environment first. Use for SRE practices.

### Question 51
**Q:** Configure Istio telemetry with Prometheus and Grafana.

**A:** Istio exports metrics automatically. Deploy Prometheus to scrape Istio metrics. Configure ServiceMonitor for envoy and istiod. Install Istio Grafana dashboards. Monitor request rate, latency, success rate. Set up alerts for SLO violations.

### Question 52
**Q:** Implement distributed tracing with Istio and Jaeger.

**A:** Deploy Jaeger operator or all-in-one. Configure Istio with tracing provider. Set sampling rate (0.1 = 10%). Propagate trace headers in application. View traces in Jaeger UI. Identify latency bottlenecks. Correlate metrics with traces.

### Question 53
**Q:** Set up Istio access logs for security auditing.

**A:** Configure Telemetry resource with access logging. Use TEXT or JSON format. Send to stdout or mesh-external service. Include request/response details, source/destination identity. Aggregate with Fluentd/ELK. Comply with audit requirements.

### Question 54
**Q:** Configure Istio egress gateway for controlled external traffic.

**A:** Deploy egress gateway. Create Gateway for outbound traffic. Define ServiceEntry for external service. Create VirtualService routing through egress gateway. Enables centralized egress control. Apply policies at egress point. Monitor external traffic.

### Question 55
**Q:** Implement Istio multi-cluster service mesh for disaster recovery.

**A:** Install Istio with multi-cluster configuration. Configure trust between clusters. Use DNS or pod IP-based discovery. Deploy VirtualServices spanning clusters. Failover automatically on cluster failure. Requires shared control plane or replicated.

### Question 56
**Q:** Set up Istio with cert-manager for custom certificate management.

**A:** Install cert-manager in cluster. Create Issuer for CA or Let's Encrypt. Generate certificates as Secrets. Configure Gateway with credentialName. Cert-manager handles renewal. Alternative to Istio's built-in CA. Use for corporate CA integration.

### Question 57
**Q:** Configure Istio request mirroring for traffic shadowing.

**A:** Use VirtualService mirror field. Specify mirror service destination. Set mirror_percent for sampling. Mirrored requests don't wait for response. Test new versions with production traffic. No impact to users. Verify new version behavior.

### Question 58
**Q:** Implement Istio rate limiting with Envoy filters.

**A:** Create EnvoyFilter with rate limit configuration. Define actions in virtualHost or route. Deploy rate limit service (Redis-based). Configure descriptors and limits. Return 429 when exceeded. Protect services from abuse. Use global or local rate limiting.

### Question 59
**Q:** Set up Istio upgrade strategy for zero-downtime updates.

**A:** Use canary upgrade approach. Install new control plane with revision. Label namespace for new revision. Gradually migrate namespaces. Monitor for issues. Rollback by changing labels. Finally remove old control plane. Test in non-prod first.

### Question 60
**Q:** Configure Istio with external authorization service (OPA).

**A:** Deploy Open Policy Agent (OPA). Create EnvoyFilter for ext_authz. Point to OPA service. Define policies in Rego. OPA evaluates authorization on each request. Returns allow/deny to Envoy. Centralized policy management. Decouple auth logic from services.

## Questions 61-80: Advanced Kubernetes Patterns

### Question 61
**Q:** Implement multi-tenancy with namespace isolation and network policies.

**A:** Create namespace per tenant. Apply ResourceQuotas for CPU/memory limits. Define LimitRanges for default limits. Implement NetworkPolicies blocking cross-namespace traffic. Use RBAC to restrict access. Consider hierarchical namespaces. Label resources for tenant identification.

### Question 62
**Q:** Configure pod security policies (PSP) for least privilege execution.

**A:** Create PodSecurityPolicy restricting privileged containers. Disallow host network, IPC, PID namespaces. Restrict volume types. Require runAsNonRoot. Drop all capabilities. Create ServiceAccount and RoleBinding. Test with admission controller. Migrate to Pod Security Standards in K8s 1.25+.

### Question 63
**Q:** Set up custom admission webhooks for policy enforcement.

**A:** Create ValidatingWebhookConfiguration or MutatingWebhookConfiguration. Develop webhook server handling AdmissionReview. Validate or mutate resources. Deploy webhook service with TLS. Register webhook with API server. Use for custom validation logic, automatic sidecar injection.

### Question 64
**Q:** Implement init containers for application bootstrapping.

**A:** Define initContainers in pod spec. Run sequentially before app containers. Use for: database migrations, config downloading, waiting for dependencies. Share volumes with main containers. Check exit code for success. Pods won't start until all init containers succeed.

### Question 65
**Q:** Configure pod disruption budgets for high availability during maintenance.

**A:** Create PodDisruptionBudget resource. Specify minAvailable or maxUnavailable. Selector targets pods. Prevents draining nodes if violates PDB. Use for stateful sets, quorum-based systems. Plan maintenance windows accordingly. Test with kubectl drain.

### Question 66
**Q:** Set up pod priority and preemption for critical workloads.

**A:** Create PriorityClass with value. Higher value = higher priority. Assign to pods via priorityClassName. Scheduler preempts lower priority pods if needed. Use for: production vs dev, critical vs batch. System-cluster-critical and system-node-critical are highest.

### Question 67
**Q:** Implement pod affinity and anti-affinity for optimal placement.

**A:** Use affinity rules in pod spec. RequiredDuringSchedulingIgnoredDuringExecution for hard rules. PreferredDuringScheduling for soft rules. Anti-affinity spreads pods across nodes/zones. Affinity co-locates related pods. Use topologyKey for spreading domain.

### Question 68
**Q:** Configure resource requests and limits for capacity planning.

**A:** Set requests for guaranteed resources. Set limits for maximum usage. CPU throttled at limit. Memory limit causes OOMKill. Use Vertical Pod Autoscaler for recommendations. Monitor with kubectl top. Right-size for efficiency and reliability.

### Question 69
**Q:** Set up liveness and readiness probes for health monitoring.

**A:** Define livenessProbe for failure detection. Kubelet restarts failed pods. Define readinessProbe for load balancer integration. Not-ready pods excluded from service endpoints. Use HTTP, TCP, or exec probes. Set appropriate timeouts and thresholds. Test failure scenarios.

### Question 70
**Q:** Implement persistent volumes with dynamic provisioning for stateful apps.

**A:** Create StorageClass for provisioner (EBS, GCE PD). Define PersistentVolumeClaim. Provisioner creates PV automatically. Attach to pod via volumeMounts. Use StatefulSet for stable volume identity. Configure reclaim policy (Delete, Retain). Back up data regularly.

### Question 71
**Q:** Configure secrets management with external secret operators.

**A:** Install External Secrets Operator. Define SecretStore for provider (AWS Secrets Manager, Vault). Create ExternalSecret referencing external secret. Operator syncs to Kubernetes Secret. Rotate automatically. Centralize secret management. Avoid secrets in Git.

### Question 72
**Q:** Set up ConfigMaps with automatic reload for configuration changes.

**A:** Create ConfigMap with config files. Mount in pod as volume or env vars. Use tools like Reloader or stakater/Reloader. Watch ConfigMap changes and restart pods. Alternative: application watches ConfigMap. Enables configuration updates without redeployment.

### Question 73
**Q:** Implement blue-green deployments with services selector switching.

**A:** Deploy blue version with label version=blue. Create service selecting blue. Deploy green version with label version=green. Test green thoroughly. Switch service selector to version=green. Instant cutover. Rollback by switching back. No downtime.

### Question 74
**Q:** Configure custom metrics with Prometheus Operator.

**A:** Install Prometheus Operator. Create ServiceMonitor for application. Expose /metrics endpoint in app. Use Prometheus client library. Define recording rules for aggregation. Create PrometheusRule for alerts. Query metrics in PromQL. Integrate with HPA.

### Question 75
**Q:** Set up log aggregation with EFK (Elasticsearch, Fluentd, Kibana) stack.

**A:** Deploy Elasticsearch cluster for storage. Deploy Fluentd as DaemonSet. Configure input from container logs. Parse JSON logs. Output to Elasticsearch. Deploy Kibana for visualization. Create index patterns. Search logs by namespace, pod. Set retention policies.

### Question 76
**Q:** Implement GitOps with ArgoCD for declarative deployments.

**A:** Install ArgoCD. Connect to Git repository. Define Application CRD. Specify source repo and target cluster. Enable auto-sync. ArgoCD continuously reconciles. Detect drift and alert. Rollback via Git revert. Audit trail in Git history.

### Question 77
**Q:** Configure cluster autoscaler for dynamic node scaling.

**A:** Deploy cluster-autoscaler with cloud provider config. Set min and max node counts. Scales up when pods are unschedulable. Scales down when nodes underutilized. Respects PodDisruptionBudgets. Configure scale-down delay. Monitor with metrics and logs.

### Question 78
**Q:** Set up Kubernetes dashboard with RBAC for secure access.

**A:** Deploy kubernetes-dashboard. Create ServiceAccount with limited permissions. Use kubectl proxy or ingress with authentication. Grant view-only access to developers. Admin access to operators. Audit access logs. Consider alternatives like Lens or k9s.

### Question 79
**Q:** Implement custom resource definitions (CRDs) for domain-specific resources.

**A:** Define CRD with OpenAPI v3 schema. Specify group, version, names. Add validation rules. Install with kubectl apply. Create custom controller to reconcile. Use kubebuilder or operator-sdk. Extends Kubernetes API with business logic.

### Question 80
**Q:** Configure node taints and tolerations for workload segregation.

**A:** Add taints to nodes: kubectl taint nodes node1 key=value:NoSchedule. Add tolerations to pods. Pods without toleration won't schedule. Use for: GPU nodes, spot instances, dedicated tenants. NoSchedule, PreferNoSchedule, NoExecute effects. Test scheduling behavior.

## Questions 81-100: Security, Monitoring, and Best Practices

### Question 81
**Q:** Implement network policies for microsegmentation.

**A:** Create NetworkPolicy with podSelector. Define ingress and egress rules. Specify from/to sources with podSelector, namespaceSelector, ipBlock. Default deny all, explicitly allow needed traffic. Requires CNI support (Calico, Cilium). Test connectivity after applying.

### Question 82
**Q:** Set up Falco for runtime security monitoring.

**A:** Deploy Falco as DaemonSet. Configure rules for suspicious behavior. Detect privilege escalation, unauthorized file access. Send alerts to Slack, PagerDuty. Integrate with Kubernetes audit logs. Create custom rules for applications. Investigate alerts promptly.

### Question 83
**Q:** Configure OPA Gatekeeper for policy-based admission control.

**A:** Install Gatekeeper. Define ConstraintTemplate with Rego policy. Create Constraint using template. Validate resource configurations. Examples: require labels, restrict registries, enforce resource limits. Audit mode first, then enforce. Better than PSP for policy management.

### Question 84
**Q:** Implement image scanning in CI/CD with Trivy or Clair.

**A:** Integrate scanner in pipeline. Scan images after build. Fail build on high/critical CVEs. Push only clean images to registry. Continuous scanning in registry. Alert on new vulnerabilities. Update base images regularly. Use minimal base images.

### Question 85
**Q:** Set up admission controller for image signature verification.

**A:** Use tools like Cosign or Notary. Sign images in CI/CD. Deploy ValidatingAdmissionWebhook. Verify signatures before pod creation. Reject unsigned or invalid signatures. Ensures supply chain integrity. Part of secure software supply chain.

### Question 86
**Q:** Configure RBAC with least privilege principles.

**A:** Create specific Roles/ClusterRoles. Grant minimal permissions needed. Use RoleBindings for namespace scope. Avoid cluster-admin for users. Create ServiceAccounts per application. Audit RBAC with kubectl auth can-i. Review permissions regularly. Document access requirements.

### Question 87
**Q:** Implement pod security contexts for container hardening.

**A:** Set securityContext in pod/container spec. runAsNonRoot: true. readOnlyRootFilesystem: true. allowPrivilegeEscalation: false. Drop ALL capabilities. Set fsGroup for volume permissions. Use seccomp and AppArmor profiles. Test application compatibility.

### Question 88
**Q:** Set up encrypted secrets with sealed-secrets or SOPS.

**A:** Install sealed-secrets controller. Encrypt secrets with kubeseal. Commit sealed secrets to Git. Controller decrypts in cluster. Alternative: SOPS with KMS/PGP. Never commit plain secrets. Rotate encryption keys. Audit secret access.

### Question 89
**Q:** Configure audit logging for compliance requirements.

**A:** Enable API server audit logging. Define audit policy. Log to file or webhook backend. Capture create, update, delete operations. Include request/response payloads. Aggregate in SIEM. Retain per compliance period. Analyze for security events.

### Question 90
**Q:** Implement resource quotas and limit ranges for cost control.

**A:** Create ResourceQuota per namespace. Limit CPU, memory, storage, object counts. Set LimitRange for default requests/limits. Prevents resource exhaustion. Charge back to teams. Monitor quota usage. Adjust based on needs. Enforce with admission control.

### Question 91
**Q:** Set up node problem detector for infrastructure health.

**A:** Deploy node-problem-detector DaemonSet. Detects node issues (kernel deadlock, filesystem corruption). Reports as NodeCondition and Events. Integrate with alerting. Drain problematic nodes. Replace or repair. Improves cluster reliability.

### Question 92
**Q:** Configure cluster backup and disaster recovery with Velero.

**A:** Install Velero with cloud provider plugin. Configure backup storage location. Create backup schedules. Include persistent volumes. Test restores regularly. Backup includes resources and PVs. Restore to same or different cluster. Part of DR plan.

### Question 93
**Q:** Implement chaos engineering with Chaos Mesh or LitmusChaos.

**A:** Install chaos engineering framework. Define chaos experiments. Inject pod failures, network latency, IO stress. Run in test environment. Validate system resilience. Verify monitoring and alerting. Fix discovered issues. Regular chaos drills.

### Question 94
**Q:** Set up service level objectives (SLOs) and error budgets.

**A:** Define SLIs (latency, availability, error rate). Set SLO targets (99.9% availability). Calculate error budget. Monitor with Prometheus. Alert when budget burns too fast. Balance feature velocity with reliability. Review SLOs quarterly.

### Question 95
**Q:** Configure cost monitoring with Kubecost or OpenCost.

**A:** Install cost monitoring tool. Integrate with cloud billing APIs. Break down costs by namespace, label, pod. Identify expensive workloads. Right-size resources. Use spot instances. Set budgets and alerts. Chargeback to teams.

### Question 96
**Q:** Implement multi-cluster management with cluster API.

**A:** Deploy Cluster API management cluster. Define cluster templates. Create WorkloadCluster CRDs. Automate cluster lifecycle. Standardize configurations. Use fleet management tools (Rancher, Anthos). Centralize policy enforcement.

### Question 97
**Q:** Set up service mesh observability with Kiali.

**A:** Deploy Kiali with Istio. Visualize service graph. View traffic flows and metrics. Troubleshoot with distributed tracing. Configure auth (OAuth, OIDC). Monitor service health. Validate mTLS. Plan capacity based on traffic patterns.

### Question 98
**Q:** Configure progressive delivery with Flagger.

**A:** Install Flagger and service mesh. Define Canary CRD with analysis. Specify metrics threshold. Automated rollout increases canary weight. Rollback on failures. Integrate with load testing. Support for A/B testing, blue-green.

### Question 99
**Q:** Implement Kubernetes operators with Operator SDK.

**A:** Install operator-sdk CLI. Bootstrap operator project. Define custom resource. Implement reconciliation logic. Handle create, update, delete events. Build and push operator image. Deploy with OLM or Helm. Test failure scenarios.

### Question 100
**Q:** Set up environment promotion pipeline (dev -> staging -> prod).

**A:** Separate clusters or namespaces per environment. Use Kustomize or Helm for config management. Automated promotion on successful tests. Different resource limits per environment. RBAC restricts prod access. Monitor promotion metrics. Rollback capability.
