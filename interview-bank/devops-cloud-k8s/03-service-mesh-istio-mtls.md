# Service Mesh Implementation with Istio and mTLS

This document contains 100 comprehensive questions about implementing service mesh with Istio, focusing on mTLS configuration, security policies, traffic management, and observability.

## Questions 1-10: Istio Architecture and Installation

### Question 1
**Q:** Explain Istio's control plane and data plane architecture components.

**A:** Control plane consists of istiod (Pilot, Citadel, Galley consolidated). Data plane uses Envoy sidecars injected into pods. Pilot manages traffic, Citadel handles certificates, Galley validates config. Envoy proxies intercept traffic, enforce policies, collect telemetry. Control plane programs data plane dynamically.

### Question 2
**Q:** Install Istio using istioctl with production profile and custom configurations.

**A:** Download istioctl. Run 'istioctl install --set profile=production'. Customize with IstioOperator CR. Set resource limits, HPA, pod disruption budgets. Enable tracing, access logging. Configure ingress/egress gateways. Verify with 'istioctl verify-install'.

### Question 3
**Q:** Configure sidecar injection strategies (automatic vs manual).

**A:** Automatic: Label namespace with 'istio-injection=enabled'. Pods get sidecars automatically. Manual: Use 'istioctl kube-inject'. Inject annotation 'sidecar.istio.io/inject: true/false' per pod. Check with 'kubectl get pods -o jsonpath='{.spec.containers[*].name}'.

### Question 4
**Q:** Implement Istio CNI plugin for avoiding init container privileges.

**A:** Install with '--set components.cni.enabled=true'. Removes need for NET_ADMIN capability. CNI plugin configures pod networking. Better security posture. Required for some restricted environments. Works with most CNI providers (Calico, Cilium).

### Question 5
**Q:** Configure Istio discovery selectors for partial mesh adoption.

**A:** Set discoverySelectors in IstioOperator. Specify namespace labels. Only selected namespaces included in mesh. Reduces istiod resource usage. Useful for gradual migration. Improves multi-tenant isolation.

### Question 6
**Q:** Set up Istio multi-primary multi-network architecture.

**A:** Install Istio in each cluster with same trust domain. Configure east-west gateways. Share CA certificates. Enable endpoint discovery across clusters. Use for multi-region HA. Traffic fails over between clusters.

### Question 7
**Q:** Configure Istio resource requirements for production workloads.

**A:** Set istiod resources (CPU: 500m-2000m, Memory: 2Gi-8Gi). Gateway resources (CPU: 2000m-4000m, Memory: 1Gi-4Gi). Enable HPA for gateways. Set pod disruption budgets. Monitor resource usage. Scale based on mesh size.

### Question 8
**Q:** Implement Istio upgrades with canary control plane.

**A:** Install new version with revision tag. Deploy canary istiod. Migrate namespace to new revision. Monitor for errors. Gradually migrate all namespaces. Remove old control plane. Use 'istioctl upgrade' command.

### Question 9
**Q:** Configure Istio telemetry v2 for improved performance.

**A:** Enabled by default in recent versions. Uses in-proxy Wasm extensions. Lower CPU usage vs mixer. Configure stackdriver, prometheus, envoy access logs. Disable unused telemetry. Use 'telemetry' API for configuration.

### Question 10
**Q:** Set up Istio operator for lifecycle management.

**A:** Install Istio operator. Create IstioOperator CR. Operator reconciles desired state. Handles upgrades, scaling. Simplifies management. Integrates with GitOps. Monitor operator logs for issues.

## Questions 11-30: mTLS Configuration and Certificate Management

### Question 11
**Q:** Enable strict mTLS across entire service mesh.

**A:** Create PeerAuthentication with 'mtls: mode: STRICT' at mesh level (istio-system namespace). All services require mTLS. Verifies identity of both client and server. Automatically encrypts traffic. Check with 'istioctl authn tls-check'.

### Question 12
**Q:** Configure permissive mTLS mode for gradual migration.

**A:** Set PeerAuthentication mode to PERMISSIVE. Accepts both plaintext and mTLS. Use during migration. Gradually move services to STRICT. Monitor non-mTLS traffic. Set deadline for full migration.

### Question 13
**Q:** Implement namespace-level mTLS policies with exceptions.

**A:** Create PeerAuthentication in namespace. Set default mode. Override per service with selector. Example: STRICT for most, PERMISSIVE for legacy service. More granular than mesh-wide. Test connectivity thoroughly.

### Question 14
**Q:** Configure port-specific mTLS settings for mixed protocols.

**A:** Use portLevelMtls in PeerAuthentication. Specify port number and mode. Example: port 80 DISABLE, port 443 STRICT. Useful for services with TCP and HTTP. Verify with traffic testing.

### Question 15
**Q:** Set up custom CA integration for certificate management.

**A:** Configure Istio with pluginCA. Integrate with enterprise PKI (Vault, cert-manager). Provide CA certificates and keys. Istio issues workload certificates from custom CA. Maintains corporate compliance. Rotate CA certificates carefully.

### Question 16
**Q:** Configure certificate TTL and rotation policies.

**A:** Default 24-hour certificate lifetime. Automatic rotation at 50% lifetime. Configure with 'meshConfig.defaultConfig.proxyMetadata.CERT_DURATION'. Balance security with performance. Monitor rotation errors. Use workload certificate rotation logs.

### Question 17
**Q:** Implement DNS certificate validation for external services.

**A:** Create ServiceEntry for external service. Enable TLS origination. Configure DestinationRule with 'caCertificates'. Verify server certificate. Use for trusted external APIs. Monitor TLS handshake errors.

### Question 18
**Q:** Set up mTLS with SPIFFE identities.

**A:** Istio uses SPIFFE/SPIRE for identities. Format: spiffe://trust-domain/ns/namespace/sa/serviceaccount. Encoded in certificates. Use for fine-grained authorization. Integrate with SPIRE server for external workloads.

### Question 19
**Q:** Configure mutual TLS for ingress gateway external traffic.

**A:** Create Gateway with TLS mode MUTUAL. Provide server certificate and CA for client validation. Clients present certificates. Verify CN or SAN. Use for B2B integrations. Client certificate authentication.

### Question 20
**Q:** Implement certificate transparency and monitoring.

**A:** Monitor certificate issuance with Citadel metrics. Alert on issuance failures. Check certificate expiration. Use 'istioctl proxy-config secret' to inspect. Log certificate rotation events. Integrate with certificate inventory.

### Question 21
**Q:** Configure mTLS exceptions for health check endpoints.

**A:** Create PeerAuthentication with selector. Disable mTLS for /health, /ready paths. Allows load balancer health checks. Use portLevelMtls or path exclusions. Verify health checks work.

### Question 22
**Q:** Set up mTLS with different trust domains per environment.

**A:** Configure separate trust domains: prod.local, staging.local. Prevents cross-environment access. Different CA roots. Configure service entries for cross-domain communication. Use for multi-tenant isolation.

### Question 23
**Q:** Implement workload certificate renewal monitoring.

**A:** Use Prometheus metrics: istio_agent_cert_expiry_seconds. Alert before expiration. Monitor pilot_cert_provider_secret_updates. Check rotation errors. Dashboard for certificate health. Investigate renewal failures promptly.

### Question 24
**Q:** Configure STRICT mTLS with automatic fallback handling.

**A:** Test services individually before mesh-wide STRICT. Use permissive mode first. Identify services without sidecar. Migrate or configure exceptions. No automatic fallback in STRICT. Plan migration carefully.

### Question 25
**Q:** Set up root certificate rotation without downtime.

**A:** Use intermediate CA certificates. Prepare new root CA. Distribute to all clusters. Dual root trust during transition. Rotate intermediates. Remove old root after TTL. Document procedure thoroughly.

### Question 26
**Q:** Implement mTLS for multi-cluster communication.

**A:** Share root CA across clusters. Configure cross-cluster discovery. East-west gateways handle mTLS. Verify certificates across clusters. Same trust domain or federated. Monitor cross-cluster connectivity.

### Question 27
**Q:** Configure SDS (Secret Discovery Service) for dynamic certificates.

**A:** Enabled by default in Istio. Envoy fetches certificates via SDS. No need to restart on rotation. Reduces risk of invalid certificates. Monitor SDS connection health. Check sidecar logs for SDS errors.

### Question 28
**Q:** Set up private key protection with hardware security modules.

**A:** Integrate Istio CA with HSM or cloud KMS. Private keys never leave HSM. Higher security for critical environments. Performance considerations for signing. Requires enterprise PKI integration.

### Question 29
**Q:** Implement certificate pinning for critical services.

**A:** Configure DestinationRule with specific certificate hash. Validate certificate exactly matches. Prevents unauthorized certificate issuance. Update pins on certificate rotation. Use sparingly due to maintenance overhead.

### Question 30
**Q:** Configure mTLS troubleshooting and diagnostics.

**A:** Use 'istioctl authn tls-check' for status. Check 'istioctl proxy-config secret' for certificates. Review envoy logs for TLS errors. Verify PeerAuthentication resources. Check certificate chain validity. Common issues: clock skew, CA mismatch.

## Questions 31-50: Traffic Management and Routing

### Question 31
**Q:** Implement weighted traffic routing with VirtualService.

**A:** Create VirtualService with http routes. Define weight per destination subset. Example: v1 90%, v2 10%. Use for canary releases. Adjust weights gradually. Monitor metrics per version.

### Question 32
**Q:** Configure header-based routing for A/B testing.

**A:** Use match conditions in VirtualService. Match on headers, cookies, or user-agent. Route to specific version. Example: header 'x-user-type: beta' -> v2. Enable feature flags. Test with curl -H header.

### Question 33
**Q:** Set up fault injection for resilience testing.

**A:** Configure VirtualService with fault.delay or fault.abort. Specify percentage and value. Example: 10% requests with 5s delay. Test timeouts and retries. Verify circuit breakers work. Run in test environment first.

### Question 34
**Q:** Implement request timeout policies with retries.

**A:** Set timeout in VirtualService route. Configure retries with attempts and perTryTimeout. Example: 3 retries with 2s timeout each. Define retryOn conditions (5xx, refused-stream). Monitor retry rates.

### Question 35
**Q:** Configure connection pool settings for backend services.

**A:** Use DestinationRule trafficPolicy.connectionPool. Set TCP maxConnections. Configure HTTP http1MaxPendingRequests, http2MaxRequests. Prevents overwhelming backends. Tune based on capacity. Monitor connection metrics.

### Question 36
**Q:** Set up outlier detection for automatic circuit breaking.

**A:** Configure trafficPolicy.outlierDetection. Set consecutiveErrors threshold. Define interval and baseEjectionTime. Ejected hosts excluded from load balancing. Gradual recovery. Monitor ejection metrics.

### Question 37
**Q:** Implement request mirroring (shadowing) for production testing.

**A:** Use mirror in VirtualService. Specify mirror destination. Set mirror_percent for sampling. Responses ignored. Test new version with real traffic. No user impact. Monitor both versions.

### Question 38
**Q:** Configure cross-origin resource sharing (CORS) policies.

**A:** Set corsPolicy in VirtualService. Define allowOrigins, allowMethods, allowHeaders. Configure maxAge and allowCredentials. Replaces application-level CORS. Centralized configuration. Test with browser.

### Question 39
**Q:** Set up URL rewriting and redirection rules.

**A:** Use rewrite in VirtualService for URL modification. Configure redirect for HTTP -> HTTPS. Match conditions for selective rewriting. Useful for API versioning. Test thoroughly to avoid breaking clients.

### Question 40
**Q:** Implement request/response header manipulation.

**A:** Configure headers in VirtualService route. Add, remove, or modify headers. Example: add correlation-id, remove x-internal. Use for security, tracking. Apply consistently across mesh.

### Question 41
**Q:** Configure traffic locality-aware routing for performance.

**A:** Enable localityLbSetting in DestinationRule. Failover to other regions. Configure distribute weights. Reduces latency. Requires multi-region deployment. Monitor cross-region traffic.

### Question 42
**Q:** Set up consistent hash-based load balancing.

**A:** Configure loadBalancer.consistentHash in DestinationRule. Hash on httpHeaderName, httpCookie, or useSourceIp. Sticky sessions without cookies. Useful for caching. Test distribution.

### Question 43
**Q:** Implement rate limiting with Envoy local rate limit.

**A:** Configure EnvoyFilter for rate limiting. Define tokens and fill rate. Apply per route or virtualHost. Returns 429 when exceeded. Protects services. Monitor rate limit metrics.

### Question 44
**Q:** Configure TCP traffic routing and load balancing.

**A:** Use VirtualService with tcp routes. Match by port. Define weighted destinations. Configure connection pool for TCP. Use for databases, Redis. Monitor TCP connection metrics.

### Question 45
**Q:** Set up egress traffic control with ServiceEntry.

**A:** Create ServiceEntry for external service. Specify hosts and ports. Configure resolution (DNS, STATIC). Apply traffic policies. Monitor egress traffic. Block unknown egress by default.

### Question 46
**Q:** Implement multi-cluster traffic routing and failover.

**A:** Configure VirtualService spanning clusters. Define locality priorities. Failover to remote cluster on failure. Use for disaster recovery. Monitor cross-cluster latency. Test failover scenarios.

### Question 47
**Q:** Configure Gateway listeners for multiple protocols.

**A:** Define servers in Gateway. HTTP, HTTPS, TCP, TLS. Different ports per protocol. SNI-based routing for TLS. Wildcard hosts support. Bind VirtualServices to Gateway.

### Question 48
**Q:** Set up traffic splitting across multiple versions for gradual rollout.

**A:** Create Deployment per version. Define subsets in DestinationRule. VirtualService splits traffic. Increase percentage gradually. Monitor error rates. Automated with Flagger.

### Question 49
**Q:** Implement request authentication at edge with Istio.

**A:** Configure RequestAuthentication on ingress gateway. Validate JWT tokens. Extract claims. Use with AuthorizationPolicy. Deny unauthenticated requests. Centralize authentication logic.

### Question 50
**Q:** Configure source-based routing for multi-tenancy.

**A:** Match on source.namespace or source.principal. Route tenants to dedicated backends. Enforce isolation. Use with AuthorizationPolicy. Monitor per-tenant metrics.

## Questions 51-70: Authorization and Security Policies

### Question 51
**Q:** Implement default-deny authorization policy at namespace level.

**A:** Create AuthorizationPolicy with empty rules (no allow). Denies all traffic by default. Explicitly allow needed communication. Security best practice. Add allow policies incrementally.

### Question 52
**Q:** Configure service-to-service authorization using service accounts.

**A:** Create AuthorizationPolicy with source.principals. Match service account SPIFFE ID. Example: allow frontend to call backend. Deny all others. Verify with negative tests.

### Question 53
**Q:** Set up path-based authorization for API endpoints.

**A:** Use to.operation.paths in AuthorizationPolicy. Allow specific paths per principal. Example: /admin only for admin service. Fine-grained control. Document authorization matrix.

### Question 54
**Q:** Implement method-based authorization (GET vs POST).

**A:** Configure to.operation.methods in rules. Allow GET for read-only services. Require elevated privileges for POST/PUT/DELETE. REST API security. Combine with path-based rules.

### Question 55
**Q:** Configure IP-based authorization for external access.

**A:** Use source.ipBlocks in AuthorizationPolicy. Allow specific CIDRs. Deny others. Useful for admin interfaces. Combine with JWT authentication. Monitor denied IPs.

### Question 56
**Q:** Set up time-based access control policies.

**A:** Use when.request.time condition. Allow access during business hours. Deny outside hours. Requires accurate time sync. Use for compliance. Alternative: external authorization service.

### Question 57
**Q:** Implement JWT claim-based authorization.

**A:** Configure RequestAuthentication for JWT validation. Use when.request.auth.claims in AuthorizationPolicy. Match on roles, groups. Fine-grained RBAC. Extract claims from JWT. Integrate with identity provider.

### Question 58
**Q:** Configure namespace-based authorization for multi-tenancy.

**A:** Use source.namespaces in rules. Allow communication within namespace. Deny cross-namespace by default. Explicit exceptions for shared services. Enforce tenant isolation.

### Question 59
**Q:** Set up custom authorization with external authorizer.

**A:** Configure EnvoyFilter for ext_authz. Point to OPA or custom service. Send request context. Return allow/deny. Centralized policy management. Complex authorization logic. Monitor latency impact.

### Question 60
**Q:** Implement layered authorization (defense in depth).

**A:** Combine multiple policies: NetworkPolicy, mTLS, AuthorizationPolicy, RBAC. Each layer provides protection. Compensates for single layer failure. Security best practice. Test each layer independently.

### Question 61
**Q:** Configure audit logging for authorization decisions.

**A:** Enable access logging in Telemetry resource. Include authorization context. Log to external system. Monitor denied requests. Compliance requirement. Analyze patterns for policy refinement.

### Question 62
**Q:** Set up deny policies for blocking malicious patterns.

**A:** Create AuthorizationPolicy with action: DENY. Higher priority than ALLOW. Block known attack patterns. SQL injection, path traversal. Combine with WAF. Regular updates.

### Question 63
**Q:** Implement custom authorization action (CUSTOM).

**A:** Use action: CUSTOM in AuthorizationPolicy. Requires custom Envoy filter. Advanced use cases. Typically use ext_authz instead. Complex implementation.

### Question 64
**Q:** Configure authorization for gRPC services.

**A:** Use to.operation.paths for gRPC methods. Format: /package.Service/Method. Configure in AuthorizationPolicy. Works with gRPC reflection. Test with grpcurl.

### Question 65
**Q:** Set up dry-run mode for policy testing.

**A:** Use action: AUDIT in AuthorizationPolicy. Logs matches without enforcing. Test policies safely. Analyze logs. Switch to ALLOW/DENY after validation. Gradual rollout approach.

### Question 66
**Q:** Implement request header-based authorization.

**A:** Use when.request.headers in rules. Match specific header values. Example: x-api-key for API authentication. Useful for legacy apps. Consider JWT for new apps.

### Question 67
**Q:** Configure authorization exclusions for health checks.

**A:** Create AuthorizationPolicy allowing specific paths. /health, /ready, /metrics. Allow from kubelet IP range. Minimal permissions. Separate from application authorization.

### Question 68
**Q:** Set up role-based access with JWT groups claim.

**A:** Extract groups from JWT claims. Map to Istio authorization rules. Example: admin group gets full access. Use when.request.auth.claims[groups]. Integrate with LDAP/AD.

### Question 69
**Q:** Implement authorization policy troubleshooting workflow.

**A:** Check deny logs in envoy access logs. Use 'istioctl authz check'. Verify PeerAuthentication and RequestAuthentication. Test with curl. Check policy selector matches pods. Common issue: wrong service account.

### Question 70
**Q:** Configure authorization for east-west gateway traffic.

**A:** Create AuthorizationPolicy on east-west gateway. Validate source clusters. Control multi-cluster access. Use with mTLS. Monitor cross-cluster authorization.

## Questions 71-90: Observability and Monitoring

### Question 71
**Q:** Configure distributed tracing with Jaeger and Zipkin.

**A:** Enable tracing in Istio meshConfig. Set sampling rate (0.1 = 10%). Deploy Jaeger all-in-one or production setup. Propagate trace headers (x-request-id, b3, etc.) in application. View traces in Jaeger UI. Correlate with metrics.

### Question 72
**Q:** Set up Prometheus metrics scraping for Istio mesh.

**A:** Deploy Prometheus. Configure ServiceMonitors for istiod, envoy. Query Istio metrics: istio_requests_total, istio_request_duration_milliseconds. Create recording rules. Alert on SLO violations. Grafana dashboards.

### Question 73
**Q:** Implement custom metrics export from Envoy sidecars.

**A:** Define EnvoyFilter with stats configuration. Add custom metrics in application. Expose via /stats/prometheus. Scrape with Prometheus. Use for business metrics. Monitor with Grafana.

### Question 74
**Q:** Configure access logging with custom format.

**A:** Create Telemetry resource with accessLogging. Define providers (stdout, file, external). Custom format with CEL expressions. Include relevant fields. Send to logging backend. Query with Kibana/CloudWatch.

### Question 75
**Q:** Set up Kiali for mesh visualization and troubleshooting.

**A:** Deploy Kiali with Istio. Configure authentication. View service graph topology. Monitor traffic flows. Visualize mTLS status. Configure validation. Troubleshoot routing issues. Integrate with tracing.

### Question 76
**Q:** Implement golden signals monitoring (latency, traffic, errors, saturation).

**A:** Query Prometheus for Istio metrics. Latency: histogram quantiles. Traffic: requests per second. Errors: error rate by code. Saturation: resource utilization. Create Grafana dashboard. Alert on degradation.

### Question 77
**Q:** Configure SLI/SLO monitoring with Prometheus.

**A:** Define SLIs: availability, latency. Set SLO targets: 99.9% availability, p99 < 500ms. Calculate error budget. Use Prometheus recording rules. Alert on budget burn. Review in retrospectives.

### Question 78
**Q:** Set up mesh-wide access logging to ELK stack.

**A:** Configure Telemetry with accessLogging to external. Deploy Fluentd DaemonSet. Parse Istio logs. Send to Elasticsearch. Create Kibana dashboards. Query by service, status code. Monitor trends.

### Question 79
**Q:** Implement metrics-based alerting with Alertmanager.

**A:** Define PrometheusRule resources. Alert on high error rate, latency. Configure Alertmanager routes. Send to Slack, PagerDuty. Group related alerts. Silence during maintenance. On-call rotations.

### Question 80
**Q:** Configure topology-aware traffic monitoring.

**A:** Use Kiali graph features. Filter by namespace, version. View traffic percentage. Identify bottlenecks. Response time per edge. Error rates highlighted. Drill into traces.

### Question 81
**Q:** Set up Envoy access log analysis for security.

**A:** Enable detailed access logs. Include authorization context. Parse logs for suspicious patterns. Detect failed authentications. Monitor denied requests. Alert on anomalies. Integrate with SIEM.

### Question 82
**Q:** Implement request tracing correlation with logs.

**A:** Propagate trace ID in headers. Include in application logs. Structured logging with trace context. Query logs by trace ID. Correlate across services. End-to-end visibility.

### Question 83
**Q:** Configure metrics aggregation for multi-cluster mesh.

**A:** Deploy Prometheus federation or Thanos. Aggregate metrics from all clusters. Global view of mesh. Compare cluster performance. Cross-cluster queries. Single pane of glass.

### Question 84
**Q:** Set up custom dashboards for business metrics.

**A:** Instrument application with custom metrics. Expose in Prometheus format. Create Grafana dashboard. Monitor business KPIs. Transaction counts, revenue. Combine with technical metrics.

### Question 85
**Q:** Implement latency percentile monitoring (P50, P95, P99).

**A:** Use histogram metrics from Istio. Query with histogram_quantile function. Monitor high percentiles for user experience. Alert on P99 degradation. Different thresholds per service. Track over time.

### Question 86
**Q:** Configure rate limit metrics and monitoring.

**A:** Export rate limit stats from Envoy. Monitor rejected requests. Alert on high rejection rate. Dashboard for rate limits. Adjust limits based on patterns. Capacity planning.

### Question 87
**Q:** Set up control plane health monitoring.

**A:** Monitor istiod metrics: pilot_xds_pushes, pilot_proxy_convergence_time. Check Galley validation metrics. Monitor Citadel certificate issuance. Alert on control plane issues. Dashboard for health.

### Question 88
**Q:** Implement canary deployment monitoring and rollback.

**A:** Monitor metrics per version subset. Compare error rates. Alert on degradation. Automated rollback with Flagger. Manual gates for validation. Progressive delivery. Safe rollouts.

### Question 89
**Q:** Configure mesh expansion monitoring for VMs.

**A:** Monitor VM workloads in mesh. Same metrics as pods. Track connection status. Verify mTLS. Monitor performance. Dashboard includes VMs. Unified observability.

### Question 90
**Q:** Set up cost attribution for mesh resources.

**A:** Tag resources with cost allocation labels. Monitor resource usage per service. Calculate cost per tenant. Identify expensive services. Optimize resource requests. Chargeback to teams.

## Questions 91-100: Advanced Topics and Best Practices

### Question 91
**Q:** Implement zero-trust security model with Istio.

**A:** Enable strict mTLS mesh-wide. Default-deny authorization. Explicit allow policies. JWT authentication at edge. Network policies for defense in depth. Audit all access. Regular security reviews.

### Question 92
**Q:** Configure Istio for PCI-DSS compliance.

**A:** Encrypt all traffic with mTLS. Audit logs for all access. Separate network zones. Access control policies. Regular vulnerability scans. Document configurations. Compliance monitoring. Penetration testing.

### Question 93
**Q:** Implement service mesh migration strategy from legacy.

**A:** Start with monitoring (permissive mTLS). Gradual sidecar injection. Migrate services incrementally. Test thoroughly. Monitor for issues. Document migration. Rollback plan. Communication with stakeholders.

### Question 94
**Q:** Configure Istio for multi-tenancy isolation.

**A:** Separate namespaces per tenant. Network policies between namespaces. Authorization policies for isolation. Resource quotas per tenant. Separate ingress per tenant. Monitor cross-tenant access. Audit tenant access.

### Question 95
**Q:** Set up Istio performance tuning for large-scale deployments.

**A:** Optimize istiod resources. Tune Envoy thread count. Reduce metric cardinality. Efficient resource allocation. Connection pool tuning. Discovery selectors for scale. Monitor resource usage. Load testing.

### Question 96
**Q:** Implement Istio GitOps workflow with Argo CD.

**A:** Store Istio configs in Git. ArgoCD syncs to cluster. Automated deployments. Version control for policies. Rollback via Git revert. CI/CD integration. Review process for changes.

### Question 97
**Q:** Configure Istio for hybrid cloud deployments.

**A:** Multi-cluster setup across clouds. Shared trust domain. Network connectivity (VPN, interconnect). East-west gateways. Service discovery across clouds. Monitor cross-cloud traffic. Disaster recovery.

### Question 98
**Q:** Implement Istio backup and disaster recovery.

**A:** Backup Istio configurations. Store in Git. Backup certificates and keys. Document trust domain. DR runbooks. Test recovery procedures. RTO/RPO requirements. Multi-cluster failover.

### Question 99
**Q:** Set up Istio security scanning and vulnerability management.

**A:** Scan Istio images for CVEs. Monitor security advisories. Patch management process. Upgrade regularly. Security testing. Penetration testing. Bug bounty program. Incident response plan.

### Question 100
**Q:** Configure Istio service mesh day 2 operations.

**A:** Automated upgrades. Monitoring and alerting. Capacity planning. Performance optimization. Security patching. Policy governance. Documentation. Training for team. On-call procedures. Post-mortems.
