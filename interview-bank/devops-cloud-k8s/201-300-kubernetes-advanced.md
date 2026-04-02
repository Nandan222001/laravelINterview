# Kubernetes Advanced Topics Questions (201-300)

## Question 201: Horizontal Pod Autoscaler (HPA) with Custom Metrics

**Difficulty**: Senior  
**Topic**: Kubernetes HPA  
**Tags**: `kubernetes`, `hpa`, `autoscaling`, `custom-metrics`

### 📋 Question

Implement a sophisticated HPA configuration that scales based on custom application metrics from Prometheus, along with CPU and memory utilization.

#### Scenario

Your e-commerce application needs to scale based on:
- Request rate (requests per second)
- Request latency (p95 response time)
- Queue depth (pending jobs)
- CPU and memory utilization

The scaling should be intelligent, with different behaviors for scale-up vs scale-down to prevent flapping.

#### Requirements

- Configure metrics server
- Set up Prometheus adapter for custom metrics
- Create HPA with multiple metrics
- Define scaling behavior policies
- Implement scale-down stabilization

### ✅ Sample Solution

```yaml
# Prometheus ServiceMonitor for metrics collection
apiVersion: monitoring.coreos.com/v1
kind: ServiceMonitor
metadata:
  name: ecommerce-app
  namespace: production
  labels:
    app: ecommerce-app
spec:
  selector:
    matchLabels:
      app: ecommerce-app
  endpoints:
  - port: metrics
    interval: 30s
    path: /metrics
---
# Prometheus Rules for custom metrics
apiVersion: monitoring.coreos.com/v1
kind: PrometheusRule
metadata:
  name: ecommerce-app-rules
  namespace: production
spec:
  groups:
  - name: ecommerce_app
    interval: 30s
    rules:
    - record: app:http_requests_per_second:rate5m
      expr: rate(http_requests_total{app="ecommerce-app"}[5m])
    
    - record: app:http_request_duration_p95:histogram
      expr: histogram_quantile(0.95, rate(http_request_duration_seconds_bucket{app="ecommerce-app"}[5m]))
    
    - record: app:queue_depth:current
      expr: queue_depth{app="ecommerce-app"}
---
# Custom Metrics API Configuration (Prometheus Adapter)
apiVersion: v1
kind: ConfigMap
metadata:
  name: prometheus-adapter
  namespace: monitoring
data:
  config.yaml: |
    rules:
    # HTTP Requests per second
    - seriesQuery: 'http_requests_total{namespace!="",pod!=""}'
      resources:
        overrides:
          namespace: {resource: "namespace"}
          pod: {resource: "pod"}
      name:
        matches: "^(.*)_total"
        as: "http_requests_per_second"
      metricsQuery: 'rate(<<.Series>>{<<.LabelMatchers>>}[2m])'
    
    # HTTP Request latency p95
    - seriesQuery: 'http_request_duration_seconds_bucket{namespace!="",pod!=""}'
      resources:
        overrides:
          namespace: {resource: "namespace"}
          pod: {resource: "pod"}
      name:
        matches: "^(.*)_bucket"
        as: "http_request_latency_p95"
      metricsQuery: 'histogram_quantile(0.95, rate(<<.Series>>{<<.LabelMatchers>>}[5m]))'
    
    # Queue depth
    - seriesQuery: 'queue_depth{namespace!="",pod!=""}'
      resources:
        overrides:
          namespace: {resource: "namespace"}
          pod: {resource: "pod"}
      name:
        as: "queue_depth"
      metricsQuery: 'avg_over_time(<<.Series>>{<<.LabelMatchers>>}[2m])'
    
    # External metrics (not pod-specific)
    externalRules:
    - seriesQuery: 'redis_connected_clients'
      resources:
        template: <<.Resource>>
      name:
        as: "redis_connected_clients"
      metricsQuery: 'sum(redis_connected_clients{job="redis"})'
---
# Advanced HPA with multiple metrics and behaviors
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: ecommerce-app-hpa
  namespace: production
  annotations:
    metric-config.object.prometheus-query.prometheus/requests: |
      rate(http_requests_total{app="ecommerce-app"}[5m])
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: ecommerce-app
  
  minReplicas: 3
  maxReplicas: 50
  
  metrics:
  # 1. CPU utilization
  - type: Resource
    resource:
      name: cpu
      target:
        type: Utilization
        averageUtilization: 70
  
  # 2. Memory utilization
  - type: Resource
    resource:
      name: memory
      target:
        type: Utilization
        averageUtilization: 80
  
  # 3. Custom metric: HTTP requests per second (per pod)
  - type: Pods
    pods:
      metric:
        name: http_requests_per_second
      target:
        type: AverageValue
        averageValue: "100"  # 100 RPS per pod
  
  # 4. Custom metric: HTTP request latency p95
  - type: Pods
    pods:
      metric:
        name: http_request_latency_p95
      target:
        type: AverageValue
        averageValue: "500m"  # 500ms
  
  # 5. Custom metric: Queue depth
  - type: Pods
    pods:
      metric:
        name: queue_depth
      target:
        type: AverageValue
        averageValue: "50"  # 50 items per pod
  
  # 6. External metric: Redis connections
  - type: External
    external:
      metric:
        name: redis_connected_clients
      target:
        type: Value
        value: "1000"
  
  # 7. Object metric: Ingress requests
  - type: Object
    object:
      metric:
        name: requests-per-second
      describedObject:
        apiVersion: networking.k8s.io/v1
        kind: Ingress
        name: ecommerce-ingress
      target:
        type: Value
        value: "10k"
  
  # Scaling behavior configuration
  behavior:
    scaleDown:
      stabilizationWindowSeconds: 300  # 5 minutes
      policies:
      # Limit scale down rate
      - type: Percent
        value: 50  # Max 50% of current pods
        periodSeconds: 60
      - type: Pods
        value: 5   # Max 5 pods at a time
        periodSeconds: 60
      selectPolicy: Min  # Choose the policy that scales down the least
    
    scaleUp:
      stabilizationWindowSeconds: 0  # Scale up immediately
      policies:
      # Aggressive scale up for high load
      - type: Percent
        value: 100  # Double the pods
        periodSeconds: 30
      - type: Pods
        value: 10   # Add 10 pods
        periodSeconds: 30
      selectPolicy: Max  # Choose the policy that scales up the most
---
# Deployment with metrics endpoint
apiVersion: apps/v1
kind: Deployment
metadata:
  name: ecommerce-app
  namespace: production
spec:
  replicas: 3
  selector:
    matchLabels:
      app: ecommerce-app
  template:
    metadata:
      labels:
        app: ecommerce-app
        version: v1.0.0
      annotations:
        prometheus.io/scrape: "true"
        prometheus.io/port: "9090"
        prometheus.io/path: "/metrics"
    spec:
      containers:
      - name: app
        image: ecommerce-app:v1.0.0
        ports:
        - name: http
          containerPort: 8080
        - name: metrics
          containerPort: 9090
        
        resources:
          requests:
            cpu: 500m
            memory: 512Mi
          limits:
            cpu: 2000m
            memory: 2Gi
        
        env:
        - name: METRICS_ENABLED
          value: "true"
        - name: METRICS_PORT
          value: "9090"
        
        livenessProbe:
          httpGet:
            path: /health
            port: 8080
          initialDelaySeconds: 30
          periodSeconds: 10
        
        readinessProbe:
          httpGet:
            path: /ready
            port: 8080
          initialDelaySeconds: 5
          periodSeconds: 5
---
# Service for metrics
apiVersion: v1
kind: Service
metadata:
  name: ecommerce-app
  namespace: production
  labels:
    app: ecommerce-app
spec:
  selector:
    app: ecommerce-app
  ports:
  - name: http
    port: 80
    targetPort: 8080
  - name: metrics
    port: 9090
    targetPort: 9090
---
# PodDisruptionBudget to work with HPA
apiVersion: policy/v1
kind: PodDisruptionBudget
metadata:
  name: ecommerce-app-pdb
  namespace: production
spec:
  maxUnavailable: 1
  selector:
    matchLabels:
      app: ecommerce-app
```

**Monitoring and Debugging HPA:**

```bash
# Check HPA status
kubectl get hpa ecommerce-app-hpa -n production

# Detailed HPA information
kubectl describe hpa ecommerce-app-hpa -n production

# Watch HPA in real-time
kubectl get hpa ecommerce-app-hpa -n production --watch

# Check available metrics
kubectl get --raw "/apis/custom.metrics.k8s.io/v1beta1" | jq .

# Query specific custom metric
kubectl get --raw "/apis/custom.metrics.k8s.io/v1beta1/namespaces/production/pods/*/http_requests_per_second" | jq .

# Check external metrics
kubectl get --raw "/apis/external.metrics.k8s.io/v1beta1" | jq .

# View HPA events
kubectl get events --field-selector involvedObject.name=ecommerce-app-hpa -n production

# Check metrics-server
kubectl top pods -n production
kubectl top nodes

# Test autoscaling with load
kubectl run -i --tty load-generator --rm --image=busybox --restart=Never -- /bin/sh -c "while sleep 0.01; do wget -q -O- http://ecommerce-app.production.svc.cluster.local; done"
```

**Example Application Metrics Code (Go):**

```go
package main

import (
    "net/http"
    "github.com/prometheus/client_golang/prometheus"
    "github.com/prometheus/client_golang/prometheus/promauto"
    "github.com/prometheus/client_golang/prometheus/promhttp"
)

var (
    httpRequestsTotal = promauto.NewCounterVec(
        prometheus.CounterOpts{
            Name: "http_requests_total",
            Help: "Total number of HTTP requests",
        },
        []string{"method", "endpoint", "status"},
    )
    
    httpRequestDuration = promauto.NewHistogramVec(
        prometheus.HistogramOpts{
            Name:    "http_request_duration_seconds",
            Help:    "HTTP request latency distributions",
            Buckets: prometheus.ExponentialBuckets(0.001, 2, 15),
        },
        []string{"method", "endpoint"},
    )
    
    queueDepth = promauto.NewGauge(
        prometheus.GaugeOpts{
            Name: "queue_depth",
            Help: "Current queue depth",
        },
    )
)

func instrumentedHandler(next http.HandlerFunc) http.HandlerFunc {
    return func(w http.ResponseWriter, r *http.Request) {
        timer := prometheus.NewTimer(httpRequestDuration.WithLabelValues(r.Method, r.URL.Path))
        defer timer.ObserveDuration()
        
        next(w, r)
        
        httpRequestsTotal.WithLabelValues(r.Method, r.URL.Path, "200").Inc()
    }
}

func main() {
    // Expose metrics endpoint
    http.Handle("/metrics", promhttp.Handler())
    
    // Application endpoints
    http.HandleFunc("/", instrumentedHandler(func(w http.ResponseWriter, r *http.Request) {
        w.Write([]byte("Hello, World!"))
    }))
    
    http.ListenAndServe(":8080", nil)
}
```

### 🎯 Key Concepts to Assess

- [ ] **Custom Metrics**: Understanding Prometheus adapter and custom metrics API
- [ ] **Multiple Metrics**: How HPA calculates desired replicas with multiple metrics
- [ ] **Scaling Behavior**: Stabilization windows and policies to prevent flapping
- [ ] **Metric Types**: Difference between Resource, Pods, Object, and External metrics

### 🔄 Follow-up Questions

1. **How does HPA calculate desired replicas when multiple metrics are defined?**
   - HPA calculates desired replicas for each metric independently and uses the highest value

2. **What's the difference between stabilizationWindowSeconds for scale-up vs scale-down?**
   - Scale-up typically 0 (immediate), scale-down typically 300s to prevent flapping

3. **How do you troubleshoot HPA not scaling?**
   - Check metrics-server, verify custom metrics availability, check resource requests are set, review HPA events

---

## Question 202: Ingress Controllers and API Gateway (Kong/Ambassador)

**Difficulty**: Senior  
**Topic**: Kubernetes Ingress  
**Tags**: `kubernetes`, `ingress`, `kong`, `api-gateway`

### 📋 Question

Implement a production-ready Ingress setup using NGINX Ingress Controller with SSL/TLS termination, rate limiting, and authentication, plus a Kong API Gateway for advanced routing and API management.

#### Scenario

Your microservices architecture needs:
- SSL/TLS termination at the ingress layer
- Path-based and host-based routing
- Rate limiting per client
- OAuth2 authentication
- API versioning
- Request/response transformation
- Circuit breaking and retry policies

#### Requirements

- Deploy NGINX Ingress Controller
- Configure SSL certificates with cert-manager
- Set up rate limiting and WAF rules
- Deploy Kong Gateway
- Configure Kong plugins for auth and rate limiting
- Implement canary deployments with traffic splitting

### ✅ Sample Solution

```yaml
# ========================================
# NGINX Ingress Controller Setup
# ========================================

# Namespace for ingress
apiVersion: v1
kind: Namespace
metadata:
  name: ingress-nginx
---
# NGINX Ingress Controller via Helm values
# helm install ingress-nginx ingress-nginx/ingress-nginx -n ingress-nginx -f nginx-values.yaml

# nginx-values.yaml content:
controller:
  replicaCount: 3
  
  resources:
    requests:
      cpu: 100m
      memory: 256Mi
    limits:
      cpu: 500m
      memory: 512Mi
  
  config:
    use-forwarded-headers: "true"
    compute-full-forwarded-for: "true"
    use-proxy-protocol: "true"
    enable-real-ip: "true"
    proxy-real-ip-cidr: "0.0.0.0/0"
    proxy-body-size: "50m"
    ssl-protocols: "TLSv1.2 TLSv1.3"
    ssl-ciphers: "ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384"
    enable-modsecurity: "true"
    enable-owasp-modsecurity-crs: "true"
  
  metrics:
    enabled: true
    serviceMonitor:
      enabled: true
  
  autoscaling:
    enabled: true
    minReplicas: 3
    maxReplicas: 10
    targetCPUUtilizationPercentage: 70
  
  podAntiAffinity:
    requiredDuringSchedulingIgnoredDuringExecution:
    - labelSelector:
        matchExpressions:
        - key: app.kubernetes.io/name
          operator: In
          values:
          - ingress-nginx
      topologyKey: kubernetes.io/hostname
---
# Cert-Manager for SSL certificates
# helm install cert-manager jetstack/cert-manager --namespace cert-manager --create-namespace --set installCRDs=true

# ClusterIssuer for Let's Encrypt
apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
  name: letsencrypt-prod
spec:
  acme:
    server: https://acme-v02.api.letsencrypt.org/directory
    email: admin@example.com
    privateKeySecretRef:
      name: letsencrypt-prod
    solvers:
    - http01:
        ingress:
          class: nginx
---
# Rate Limiting ConfigMap
apiVersion: v1
kind: ConfigMap
metadata:
  name: nginx-rate-limit
  namespace: ingress-nginx
data:
  limit-req-zone: "$binary_remote_addr zone=req_limit:10m rate=10r/s"
  limit-conn-zone: "$binary_remote_addr zone=conn_limit:10m"
---
# Ingress with SSL, rate limiting, and authentication
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: api-ingress
  namespace: production
  annotations:
    # Ingress class
    kubernetes.io/ingress.class: nginx
    
    # SSL/TLS
    cert-manager.io/cluster-issuer: letsencrypt-prod
    nginx.ingress.kubernetes.io/ssl-redirect: "true"
    nginx.ingress.kubernetes.io/force-ssl-redirect: "true"
    
    # Rate limiting
    nginx.ingress.kubernetes.io/limit-rps: "100"
    nginx.ingress.kubernetes.io/limit-connections: "50"
    nginx.ingress.kubernetes.io/limit-burst-multiplier: "5"
    
    # CORS
    nginx.ingress.kubernetes.io/enable-cors: "true"
    nginx.ingress.kubernetes.io/cors-allow-methods: "GET, POST, PUT, DELETE, OPTIONS"
    nginx.ingress.kubernetes.io/cors-allow-origin: "https://app.example.com"
    nginx.ingress.kubernetes.io/cors-allow-credentials: "true"
    
    # Security headers
    nginx.ingress.kubernetes.io/configuration-snippet: |
      more_set_headers "X-Frame-Options: DENY";
      more_set_headers "X-Content-Type-Options: nosniff";
      more_set_headers "X-XSS-Protection: 1; mode=block";
      more_set_headers "Strict-Transport-Security: max-age=31536000; includeSubDomains";
    
    # Connection settings
    nginx.ingress.kubernetes.io/proxy-connect-timeout: "30"
    nginx.ingress.kubernetes.io/proxy-send-timeout: "60"
    nginx.ingress.kubernetes.io/proxy-read-timeout: "60"
    nginx.ingress.kubernetes.io/proxy-body-size: "50m"
    
    # Load balancing
    nginx.ingress.kubernetes.io/load-balance: "ewma"
    nginx.ingress.kubernetes.io/upstream-hash-by: "$binary_remote_addr"
    
    # Whitelist IPs
    nginx.ingress.kubernetes.io/whitelist-source-range: "10.0.0.0/8,172.16.0.0/12"
    
    # OAuth2 authentication
    nginx.ingress.kubernetes.io/auth-url: "https://auth.example.com/oauth2/auth"
    nginx.ingress.kubernetes.io/auth-signin: "https://auth.example.com/oauth2/start?rd=$escaped_request_uri"
spec:
  tls:
  - hosts:
    - api.example.com
    secretName: api-example-com-tls
  
  rules:
  - host: api.example.com
    http:
      paths:
      # API v1
      - path: /api/v1
        pathType: Prefix
        backend:
          service:
            name: api-v1-service
            port:
              number: 80
      
      # API v2
      - path: /api/v2
        pathType: Prefix
        backend:
          service:
            name: api-v2-service
            port:
              number: 80
      
      # Admin endpoints (additional auth)
      - path: /admin
        pathType: Prefix
        backend:
          service:
            name: admin-service
            port:
              number: 80
---
# Canary Deployment with traffic splitting
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: api-ingress-canary
  namespace: production
  annotations:
    kubernetes.io/ingress.class: nginx
    nginx.ingress.kubernetes.io/canary: "true"
    nginx.ingress.kubernetes.io/canary-weight: "10"  # 10% traffic to canary
    # Alternative: nginx.ingress.kubernetes.io/canary-by-header: "X-Canary"
    # Alternative: nginx.ingress.kubernetes.io/canary-by-cookie: "canary"
spec:
  tls:
  - hosts:
    - api.example.com
    secretName: api-example-com-tls
  rules:
  - host: api.example.com
    http:
      paths:
      - path: /api/v2
        pathType: Prefix
        backend:
          service:
            name: api-v2-canary-service
            port:
              number: 80
---
# ========================================
# Kong API Gateway Setup
# ========================================

# Kong Gateway installation
# helm install kong kong/kong -n kong --create-namespace -f kong-values.yaml

# kong-values.yaml:
env:
  database: postgres
  pg_host: postgres-service.database.svc.cluster.local
  pg_port: 5432
  pg_user: kong
  pg_password: kong_password
  pg_database: kong
  
  # Plugins
  plugins: bundled,oidc,rate-limiting,request-transformer,response-transformer,correlation-id,prometheus

proxy:
  type: LoadBalancer
  annotations:
    service.beta.kubernetes.io/aws-load-balancer-type: nlb
  
  http:
    enabled: true
    servicePort: 80
    containerPort: 8000
  
  tls:
    enabled: true
    servicePort: 443
    containerPort: 8443
  
  ingress:
    enabled: true
    annotations:
      cert-manager.io/cluster-issuer: letsencrypt-prod
    hostname: kong.example.com
    tls: kong-tls

admin:
  enabled: true
  type: ClusterIP
  
manager:
  enabled: true
  type: LoadBalancer

resources:
  requests:
    cpu: 500m
    memory: 512Mi
  limits:
    cpu: 2000m
    memory: 2Gi

autoscaling:
  enabled: true
  minReplicas: 3
  maxReplicas: 10
  targetCPUUtilizationPercentage: 70
---
# Kong Service definition
apiVersion: configuration.konghq.com/v1
kind: KongPlugin
metadata:
  name: rate-limiting
  namespace: production
config:
  minute: 100
  hour: 10000
  policy: local
plugin: rate-limiting
---
apiVersion: configuration.konghq.com/v1
kind: KongPlugin
metadata:
  name: correlation-id
  namespace: production
config:
  header_name: X-Correlation-ID
  generator: uuid
  echo_downstream: true
plugin: correlation-id
---
apiVersion: configuration.konghq.com/v1
kind: KongPlugin
metadata:
  name: request-transformer
  namespace: production
config:
  add:
    headers:
    - "X-Gateway:Kong"
    - "X-Request-Start:$(msec)"
  remove:
    headers:
    - "X-Internal-Header"
plugin: request-transformer
---
apiVersion: configuration.konghq.com/v1
kind: KongPlugin
metadata:
  name: response-transformer
  namespace: production
config:
  add:
    headers:
    - "X-Response-Time:$(msec)"
  remove:
    headers:
    - "X-Internal-Info"
plugin: response-transformer
---
apiVersion: configuration.konghq.com/v1
kind: KongPlugin
metadata:
  name: oauth2
  namespace: production
config:
  scopes:
  - read
  - write
  mandatory_scope: true
  enable_client_credentials: true
  enable_password_grant: true
plugin: oauth2
---
apiVersion: configuration.konghq.com/v1
kind: KongPlugin
metadata:
  name: prometheus
  namespace: production
plugin: prometheus
---
# Kong Ingress with plugins
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: kong-api-ingress
  namespace: production
  annotations:
    kubernetes.io/ingress.class: kong
    konghq.com/plugins: rate-limiting,correlation-id,request-transformer,response-transformer,prometheus
    konghq.com/protocols: https
    konghq.com/https-redirect-status-code: "301"
spec:
  tls:
  - hosts:
    - api.example.com
    secretName: api-tls-secret
  rules:
  - host: api.example.com
    http:
      paths:
      - path: /v1/users
        pathType: Prefix
        backend:
          service:
            name: user-service
            port:
              number: 80
      - path: /v1/orders
        pathType: Prefix
        backend:
          service:
            name: order-service
            port:
              number: 80
---
# Kong Service with circuit breaker
apiVersion: configuration.konghq.com/v1
kind: KongIngress
metadata:
  name: api-route-config
  namespace: production
upstream:
  healthchecks:
    active:
      healthy:
        interval: 5
        successes: 3
      unhealthy:
        interval: 5
        http_failures: 3
        timeouts: 3
    passive:
      healthy:
        successes: 3
      unhealthy:
        http_failures: 3
        timeouts: 3
  hash_on: header
  hash_on_header: X-User-ID
route:
  protocols:
  - https
  methods:
  - GET
  - POST
  strip_path: false
  preserve_host: true
proxy:
  protocol: http
  connect_timeout: 10000
  write_timeout: 60000
  read_timeout: 60000
  retries: 3
---
# Service with Kong annotations
apiVersion: v1
kind: Service
metadata:
  name: user-service
  namespace: production
  annotations:
    konghq.com/override: api-route-config
    konghq.com/plugins: rate-limiting,oauth2
spec:
  selector:
    app: user-service
  ports:
  - port: 80
    targetPort: 8080
```

**Testing and Monitoring:**

```bash
# Test NGINX Ingress
curl -H "Host: api.example.com" https://api.example.com/api/v1/health

# Test rate limiting
for i in {1..150}; do curl -H "Host: api.example.com" https://api.example.com/api/v1/test; done

# Test canary deployment
curl -H "Host: api.example.com" -H "X-Canary: always" https://api.example.com/api/v2/test

# Kong Admin API
kubectl port-forward -n kong svc/kong-kong-admin 8001:8001

# List Kong services
curl http://localhost:8001/services

# List Kong routes
curl http://localhost:8001/routes

# Kong metrics
kubectl port-forward -n kong svc/kong-kong-proxy 9542:9542
curl http://localhost:9542/metrics

# Check Ingress status
kubectl get ingress -A
kubectl describe ingress api-ingress -n production

# Check certificates
kubectl get certificate -A
kubectl describe certificate api-example-com-tls -n production
```

### 🎯 Key Concepts to Assess

- [ ] **Ingress vs API Gateway**: Understanding the differences and when to use each
- [ ] **SSL/TLS Management**: Cert-manager automation and certificate lifecycle
- [ ] **Traffic Management**: Canary deployments, rate limiting, circuit breaking
- [ ] **Plugin Architecture**: Kong plugins for authentication, transformation, monitoring

---

*Questions 203-300 continue with topics including: Service Mesh (Istio) architecture, Istio sidecar injection, Virtual Services and Destination Rules, Istio Gateway configuration, mTLS in service mesh, Istio traffic management, Circuit breakers with Istio, Distributed tracing with Jaeger, Observability with Kiali, Envoy proxy configuration, NetworkPolicies with Istio, Authorization policies, Helm chart development, Helm hooks and tests, Helm chart dependencies, Kustomize overlays and bases, GitOps with ArgoCD/Flux, Kubernetes Operators development, Custom controllers, Admission webhooks, Policy enforcement with OPA/Gatekeeper, Pod Security Admission, Secrets management with External Secrets Operator, Vault integration, Certificate rotation, Multi-cluster management, Cluster federation, Cross-cluster service discovery, Disaster recovery strategies, Backup and restore with Velero, etcd management, Kubernetes upgrades, Node maintenance, Cluster autoscaling, Cost optimization, Resource quotas and limits, Capacity planning, and Performance tuning.*

