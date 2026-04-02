# Kubernetes Fundamentals Questions (101-200)

## Question 101: Kubernetes Pod Design and Best Practices

**Difficulty**: Mid  
**Topic**: Kubernetes Pods  
**Tags**: `kubernetes`, `pods`, `containers`, `best-practices`

### 📋 Question

Design a production-ready Kubernetes Pod specification that demonstrates best practices for resource management, health checks, security, and observability.

#### Scenario

You're deploying a microservice application that:
- Runs a Node.js API server
- Requires environment-specific configuration
- Needs periodic health checks
- Must handle graceful shutdown
- Requires secrets for database credentials
- Should run as non-root user

#### Requirements

- Define resource requests and limits
- Implement liveness and readiness probes
- Configure security context
- Use ConfigMaps and Secrets
- Set up proper logging
- Handle lifecycle hooks

### ✅ Sample Solution

```yaml
apiVersion: v1
kind: Pod
metadata:
  name: api-service
  namespace: production
  labels:
    app: api-service
    version: v1.2.3
    environment: production
    team: backend
  annotations:
    prometheus.io/scrape: "true"
    prometheus.io/port: "9090"
    prometheus.io/path: "/metrics"
spec:
  # Security Context for the Pod
  securityContext:
    runAsNonRoot: true
    runAsUser: 1000
    runAsGroup: 3000
    fsGroup: 2000
    seccompProfile:
      type: RuntimeDefault

  # Service Account
  serviceAccountName: api-service-sa
  automountServiceAccountToken: true

  # Node Selection
  nodeSelector:
    workload-type: application
  
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
              - api-service
          topologyKey: kubernetes.io/hostname

  # Tolerations
  tolerations:
  - key: "dedicated"
    operator: "Equal"
    value: "application"
    effect: "NoSchedule"

  # Init Containers
  initContainers:
  - name: wait-for-db
    image: busybox:1.35
    command: 
    - sh
    - -c
    - |
      until nc -z postgres-service 5432; do
        echo "Waiting for database..."
        sleep 2
      done
    securityContext:
      allowPrivilegeEscalation: false
      capabilities:
        drop:
        - ALL

  - name: migration
    image: myapp/migrations:v1.2.3
    command: ["npm", "run", "migrate"]
    env:
    - name: DATABASE_URL
      valueFrom:
        secretKeyRef:
          name: database-credentials
          key: connection-string
    securityContext:
      allowPrivilegeEscalation: false
      capabilities:
        drop:
        - ALL

  # Main Containers
  containers:
  - name: api-server
    image: myapp/api-service:v1.2.3
    imagePullPolicy: IfNotPresent
    
    # Ports
    ports:
    - name: http
      containerPort: 8080
      protocol: TCP
    - name: metrics
      containerPort: 9090
      protocol: TCP

    # Environment Variables
    env:
    - name: NODE_ENV
      value: "production"
    - name: PORT
      value: "8080"
    - name: LOG_LEVEL
      valueFrom:
        configMapKeyRef:
          name: api-config
          key: log-level
    - name: DATABASE_HOST
      valueFrom:
        configMapKeyRef:
          name: api-config
          key: database-host
    - name: DATABASE_USER
      valueFrom:
        secretKeyRef:
          name: database-credentials
          key: username
    - name: DATABASE_PASSWORD
      valueFrom:
        secretKeyRef:
          name: database-credentials
          key: password
    - name: JWT_SECRET
      valueFrom:
        secretKeyRef:
          name: jwt-secret
          key: secret
    - name: POD_NAME
      valueFrom:
        fieldRef:
          fieldPath: metadata.name
    - name: POD_NAMESPACE
      valueFrom:
        fieldRef:
          fieldPath: metadata.namespace
    - name: POD_IP
      valueFrom:
        fieldRef:
          fieldPath: status.podIP

    # Resource Management
    resources:
      requests:
        cpu: 250m
        memory: 512Mi
        ephemeral-storage: 1Gi
      limits:
        cpu: 1000m
        memory: 1Gi
        ephemeral-storage: 2Gi

    # Health Checks
    livenessProbe:
      httpGet:
        path: /health/live
        port: http
        scheme: HTTP
      initialDelaySeconds: 30
      periodSeconds: 10
      timeoutSeconds: 5
      successThreshold: 1
      failureThreshold: 3

    readinessProbe:
      httpGet:
        path: /health/ready
        port: http
        scheme: HTTP
      initialDelaySeconds: 10
      periodSeconds: 5
      timeoutSeconds: 3
      successThreshold: 1
      failureThreshold: 3

    startupProbe:
      httpGet:
        path: /health/startup
        port: http
        scheme: HTTP
      initialDelaySeconds: 0
      periodSeconds: 5
      timeoutSeconds: 3
      successThreshold: 1
      failureThreshold: 30

    # Lifecycle Hooks
    lifecycle:
      preStop:
        exec:
          command:
          - sh
          - -c
          - |
            # Graceful shutdown
            sleep 5
            kill -SIGTERM 1

    # Security Context
    securityContext:
      allowPrivilegeEscalation: false
      readOnlyRootFilesystem: true
      capabilities:
        drop:
        - ALL
      runAsNonRoot: true
      runAsUser: 1000

    # Volume Mounts
    volumeMounts:
    - name: tmp
      mountPath: /tmp
    - name: cache
      mountPath: /app/cache
    - name: config
      mountPath: /app/config
      readOnly: true
    - name: secrets
      mountPath: /app/secrets
      readOnly: true

  # Sidecar Container for Logging
  - name: log-forwarder
    image: fluent/fluent-bit:2.0
    resources:
      requests:
        cpu: 50m
        memory: 64Mi
      limits:
        cpu: 100m
        memory: 128Mi
    volumeMounts:
    - name: varlog
      mountPath: /var/log
    - name: fluentbit-config
      mountPath: /fluent-bit/etc/

  # Volumes
  volumes:
  - name: tmp
    emptyDir:
      sizeLimit: 500Mi
  - name: cache
    emptyDir:
      sizeLimit: 1Gi
  - name: varlog
    emptyDir: {}
  - name: config
    configMap:
      name: api-config
      items:
      - key: app-config.json
        path: config.json
  - name: secrets
    secret:
      secretName: api-secrets
      defaultMode: 0400
  - name: fluentbit-config
    configMap:
      name: fluentbit-config

  # DNS Policy
  dnsPolicy: ClusterFirst
  dnsConfig:
    options:
    - name: ndots
      value: "2"
    - name: timeout
      value: "2"

  # Restart Policy
  restartPolicy: Always

  # Termination Grace Period
  terminationGracePeriodSeconds: 30

  # Image Pull Secrets
  imagePullSecrets:
  - name: docker-registry-secret
---
apiVersion: v1
kind: ConfigMap
metadata:
  name: api-config
  namespace: production
data:
  log-level: "info"
  database-host: "postgres-service.database.svc.cluster.local"
  app-config.json: |
    {
      "features": {
        "newUI": true,
        "betaFeatures": false
      },
      "limits": {
        "maxRequestsPerMinute": 100
      }
    }
---
apiVersion: v1
kind: Secret
metadata:
  name: database-credentials
  namespace: production
type: Opaque
stringData:
  username: "api_user"
  password: "change-me-in-production"
  connection-string: "postgresql://api_user:change-me-in-production@postgres-service:5432/apidb"
---
apiVersion: v1
kind: Secret
metadata:
  name: jwt-secret
  namespace: production
type: Opaque
stringData:
  secret: "your-very-secure-jwt-secret-key"
---
apiVersion: v1
kind: ServiceAccount
metadata:
  name: api-service-sa
  namespace: production
  annotations:
    eks.amazonaws.com/role-arn: arn:aws:iam::123456789012:role/api-service-role
```

### 🎯 Key Concepts to Assess

- [ ] **Resource Management**: Understanding of requests vs limits and their impact
- [ ] **Health Checks**: Proper configuration of liveness, readiness, and startup probes
- [ ] **Security**: Non-root users, read-only filesystem, capability dropping
- [ ] **Configuration Management**: Proper use of ConfigMaps and Secrets

### 🔄 Follow-up Questions

1. **What's the difference between liveness and readiness probes?**
   - Liveness determines if container should be restarted; readiness determines if pod should receive traffic

2. **Why use init containers instead of a single container?**
   - Init containers run sequentially before app containers, useful for setup tasks, migrations, or waiting for dependencies

3. **How do resource requests affect pod scheduling?**
   - Scheduler uses requests to find nodes with sufficient resources; limits enforce maximum resource usage

---

## Question 102: Kubernetes Deployments and Rolling Updates

**Difficulty**: Mid  
**Topic**: Kubernetes Deployments  
**Tags**: `kubernetes`, `deployments`, `rolling-updates`, `scaling`

### 📋 Question

Create a Kubernetes Deployment that supports zero-downtime rolling updates, automatic rollback on failure, and horizontal scaling.

#### Scenario

Your application needs:
- Zero-downtime deployments
- Automatic rollback if health checks fail
- Ability to pause and resume rollouts
- Horizontal Pod Autoscaling based on CPU and memory
- Update strategy that ensures minimum availability

#### Requirements

- Configure deployment strategy
- Set up HPA (Horizontal Pod Autoscaler)
- Define PodDisruptionBudget
- Implement proper labels and selectors
- Configure revision history

### ✅ Sample Solution

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: web-application
  namespace: production
  labels:
    app: web-application
    version: v2.0.0
  annotations:
    kubernetes.io/change-cause: "Update to version 2.0.0 with new features"
spec:
  # Replicas (will be managed by HPA)
  replicas: 3
  
  # Revision History
  revisionHistoryLimit: 10
  
  # Selector
  selector:
    matchLabels:
      app: web-application
  
  # Update Strategy
  strategy:
    type: RollingUpdate
    rollingUpdate:
      maxSurge: 1        # Maximum number of pods above desired count
      maxUnavailable: 0  # Ensure zero downtime
  
  # Minimum ready seconds
  minReadySeconds: 10
  
  # Progress deadline
  progressDeadlineSeconds: 600
  
  # Pod Template
  template:
    metadata:
      labels:
        app: web-application
        version: v2.0.0
        tier: frontend
      annotations:
        prometheus.io/scrape: "true"
        prometheus.io/port: "8080"
    spec:
      securityContext:
        runAsNonRoot: true
        runAsUser: 1000
        fsGroup: 2000
      
      # Anti-affinity for high availability
      affinity:
        podAntiAffinity:
          requiredDuringSchedulingIgnoredDuringExecution:
          - labelSelector:
              matchExpressions:
              - key: app
                operator: In
                values:
                - web-application
            topologyKey: kubernetes.io/hostname
        nodeAffinity:
          preferredDuringSchedulingIgnoredDuringExecution:
          - weight: 100
            preference:
              matchExpressions:
              - key: node-role
                operator: In
                values:
                - application
      
      containers:
      - name: web-app
        image: myregistry/web-application:v2.0.0
        imagePullPolicy: IfNotPresent
        
        ports:
        - name: http
          containerPort: 8080
          protocol: TCP
        
        env:
        - name: APP_VERSION
          value: "v2.0.0"
        - name: ENVIRONMENT
          value: "production"
        
        # Resource requests and limits for HPA
        resources:
          requests:
            cpu: 200m
            memory: 256Mi
          limits:
            cpu: 500m
            memory: 512Mi
        
        # Liveness probe
        livenessProbe:
          httpGet:
            path: /health
            port: http
          initialDelaySeconds: 30
          periodSeconds: 10
          timeoutSeconds: 5
          failureThreshold: 3
        
        # Readiness probe (critical for rolling updates)
        readinessProbe:
          httpGet:
            path: /ready
            port: http
          initialDelaySeconds: 5
          periodSeconds: 5
          timeoutSeconds: 3
          successThreshold: 1
          failureThreshold: 3
        
        # Graceful shutdown
        lifecycle:
          preStop:
            exec:
              command:
              - sh
              - -c
              - sleep 15
        
        securityContext:
          allowPrivilegeEscalation: false
          readOnlyRootFilesystem: true
          capabilities:
            drop:
            - ALL
        
        volumeMounts:
        - name: tmp
          mountPath: /tmp
        - name: cache
          mountPath: /app/cache
      
      volumes:
      - name: tmp
        emptyDir: {}
      - name: cache
        emptyDir: {}
      
      terminationGracePeriodSeconds: 30
      
      imagePullSecrets:
      - name: registry-credentials
---
apiVersion: v1
kind: Service
metadata:
  name: web-application
  namespace: production
  labels:
    app: web-application
spec:
  type: ClusterIP
  selector:
    app: web-application
  ports:
  - name: http
    port: 80
    targetPort: http
    protocol: TCP
  sessionAffinity: None
---
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: web-application-hpa
  namespace: production
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: web-application
  
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
  
  # Custom metrics (requests per second)
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
      - type: Pods
        value: 2
        periodSeconds: 60
      selectPolicy: Min
    scaleUp:
      stabilizationWindowSeconds: 0
      policies:
      - type: Percent
        value: 100
        periodSeconds: 30
      - type: Pods
        value: 4
        periodSeconds: 30
      selectPolicy: Max
---
apiVersion: policy/v1
kind: PodDisruptionBudget
metadata:
  name: web-application-pdb
  namespace: production
spec:
  minAvailable: 2
  selector:
    matchLabels:
      app: web-application
---
# NetworkPolicy for security
apiVersion: networking.k8s.io/v1
kind: NetworkPolicy
metadata:
  name: web-application-netpol
  namespace: production
spec:
  podSelector:
    matchLabels:
      app: web-application
  policyTypes:
  - Ingress
  - Egress
  ingress:
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
  egress:
  - to:
    - namespaceSelector: {}
    ports:
    - protocol: TCP
      port: 443  # HTTPS
    - protocol: TCP
      port: 80   # HTTP
  - to:
    - namespaceSelector: {}
      podSelector:
        matchLabels:
          app: postgres
    ports:
    - protocol: TCP
      port: 5432
  - to:
    - namespaceSelector:
        matchLabels:
          name: kube-system
      podSelector:
        matchLabels:
          k8s-app: kube-dns
    ports:
    - protocol: UDP
      port: 53
```

**Deployment Commands:**

```bash
# Apply the deployment
kubectl apply -f deployment.yaml

# Watch the rollout status
kubectl rollout status deployment/web-application -n production

# View rollout history
kubectl rollout history deployment/web-application -n production

# Rollback to previous version
kubectl rollout undo deployment/web-application -n production

# Rollback to specific revision
kubectl rollout undo deployment/web-application -n production --to-revision=2

# Pause a rollout
kubectl rollout pause deployment/web-application -n production

# Resume a rollout
kubectl rollout resume deployment/web-application -n production

# Scale manually (overrides HPA temporarily)
kubectl scale deployment/web-application --replicas=5 -n production

# Check HPA status
kubectl get hpa web-application-hpa -n production

# Describe HPA for detailed metrics
kubectl describe hpa web-application-hpa -n production

# Update image (triggers rolling update)
kubectl set image deployment/web-application web-app=myregistry/web-application:v2.0.1 -n production

# Check PodDisruptionBudget
kubectl get pdb web-application-pdb -n production
```

### 🎯 Key Concepts to Assess

- [ ] **Rolling Update Strategy**: Understanding maxSurge and maxUnavailable
- [ ] **HPA Configuration**: Multiple metrics and scaling behavior
- [ ] **PodDisruptionBudget**: Ensuring availability during voluntary disruptions
- [ ] **Readiness Probes**: Critical for zero-downtime deployments

### 🔄 Follow-up Questions

1. **What happens if you set maxUnavailable to 0 and maxSurge to 0?**
   - Invalid configuration; at least one must be greater than 0 to allow updates

2. **How does HPA interact with manual scaling?**
   - HPA will override manual scaling; use `kubectl autoscale` commands or modify HPA spec

3. **When would you use a PodDisruptionBudget?**
   - To ensure minimum availability during voluntary disruptions like node drains or cluster upgrades

---

## Question 103: Kubernetes Services and Networking

**Difficulty**: Mid  
**Topic**: Kubernetes Services  
**Tags**: `kubernetes`, `services`, `networking`, `load-balancing`

### 📋 Question

Implement different types of Kubernetes Services (ClusterIP, NodePort, LoadBalancer, Headless) and explain their use cases and networking behavior.

#### Scenario

You need to expose various applications:
- Internal microservices that only communicate within cluster
- A database that requires direct pod addressing
- An API that needs external access via cloud load balancer
- A debugging service accessible on specific node ports

#### Requirements

- Create ClusterIP service for internal communication
- Create Headless service for StatefulSet
- Create LoadBalancer service for external access
- Demonstrate service discovery
- Configure session affinity

### ✅ Sample Solution

```yaml
# 1. ClusterIP Service (Default) - Internal only
apiVersion: v1
kind: Service
metadata:
  name: api-service-internal
  namespace: production
  labels:
    app: api-service
    tier: backend
  annotations:
    service.alpha.kubernetes.io/tolerate-unready-endpoints: "false"
spec:
  type: ClusterIP
  selector:
    app: api-service
    tier: backend
  ports:
  - name: http
    port: 80
    targetPort: 8080
    protocol: TCP
  - name: grpc
    port: 9090
    targetPort: 9090
    protocol: TCP
  sessionAffinity: ClientIP
  sessionAffinityConfig:
    clientIP:
      timeoutSeconds: 10800  # 3 hours
  ipFamilies:
  - IPv4
  ipFamilyPolicy: SingleStack
---
# 2. Headless Service - For StatefulSet (Direct Pod Access)
apiVersion: v1
kind: Service
metadata:
  name: postgres-headless
  namespace: database
  labels:
    app: postgres
spec:
  type: ClusterIP
  clusterIP: None  # Makes it headless
  selector:
    app: postgres
  ports:
  - name: postgres
    port: 5432
    targetPort: 5432
    protocol: TCP
  publishNotReadyAddresses: true  # Important for StatefulSets
---
# 3. NodePort Service - External access via node IP
apiVersion: v1
kind: Service
metadata:
  name: debug-service
  namespace: development
  labels:
    app: debug-app
spec:
  type: NodePort
  selector:
    app: debug-app
  ports:
  - name: http
    port: 80
    targetPort: 8080
    nodePort: 30080  # Must be in range 30000-32767
    protocol: TCP
  externalTrafficPolicy: Local  # Preserves source IP
---
# 4. LoadBalancer Service - Cloud load balancer
apiVersion: v1
kind: Service
metadata:
  name: web-application-lb
  namespace: production
  labels:
    app: web-application
  annotations:
    # AWS specific annotations
    service.beta.kubernetes.io/aws-load-balancer-type: "nlb"
    service.beta.kubernetes.io/aws-load-balancer-cross-zone-load-balancing-enabled: "true"
    service.beta.kubernetes.io/aws-load-balancer-backend-protocol: "http"
    service.beta.kubernetes.io/aws-load-balancer-ssl-cert: "arn:aws:acm:region:account:certificate/cert-id"
    service.beta.kubernetes.io/aws-load-balancer-ssl-ports: "443"
    # Health check annotations
    service.beta.kubernetes.io/aws-load-balancer-healthcheck-path: "/health"
    service.beta.kubernetes.io/aws-load-balancer-healthcheck-interval: "10"
    service.beta.kubernetes.io/aws-load-balancer-healthcheck-timeout: "5"
    service.beta.kubernetes.io/aws-load-balancer-healthy-threshold: "2"
    service.beta.kubernetes.io/aws-load-balancer-unhealthy-threshold: "2"
spec:
  type: LoadBalancer
  selector:
    app: web-application
  ports:
  - name: https
    port: 443
    targetPort: 8080
    protocol: TCP
  - name: http
    port: 80
    targetPort: 8080
    protocol: TCP
  externalTrafficPolicy: Local  # Preserves client IP, no extra hop
  loadBalancerSourceRanges:  # IP whitelist
  - 10.0.0.0/8
  - 172.16.0.0/12
  sessionAffinity: ClientIP
---
# 5. ExternalName Service - CNAME to external service
apiVersion: v1
kind: Service
metadata:
  name: external-api
  namespace: production
spec:
  type: ExternalName
  externalName: api.external-service.com
  ports:
  - port: 443
    protocol: TCP
---
# 6. Multi-port Service with named ports
apiVersion: v1
kind: Service
metadata:
  name: multi-port-service
  namespace: production
spec:
  type: ClusterIP
  selector:
    app: multi-port-app
  ports:
  - name: http
    port: 80
    targetPort: http  # Named port in pod spec
    protocol: TCP
  - name: https
    port: 443
    targetPort: https
    protocol: TCP
  - name: metrics
    port: 9090
    targetPort: metrics
    protocol: TCP
---
# Example Deployment using named ports
apiVersion: apps/v1
kind: Deployment
metadata:
  name: multi-port-app
  namespace: production
spec:
  replicas: 3
  selector:
    matchLabels:
      app: multi-port-app
  template:
    metadata:
      labels:
        app: multi-port-app
    spec:
      containers:
      - name: app
        image: myapp:latest
        ports:
        - name: http
          containerPort: 8080
        - name: https
          containerPort: 8443
        - name: metrics
          containerPort: 9090
---
# StatefulSet using Headless Service
apiVersion: apps/v1
kind: StatefulSet
metadata:
  name: postgres
  namespace: database
spec:
  serviceName: postgres-headless
  replicas: 3
  selector:
    matchLabels:
      app: postgres
  template:
    metadata:
      labels:
        app: postgres
    spec:
      containers:
      - name: postgres
        image: postgres:14
        ports:
        - name: postgres
          containerPort: 5432
        env:
        - name: POSTGRES_PASSWORD
          valueFrom:
            secretKeyRef:
              name: postgres-secret
              key: password
        volumeMounts:
        - name: data
          mountPath: /var/lib/postgresql/data
  volumeClaimTemplates:
  - metadata:
      name: data
    spec:
      accessModes: ["ReadWriteOnce"]
      storageClassName: gp3
      resources:
        requests:
          storage: 100Gi
---
# Endpoints for manual service mapping
apiVersion: v1
kind: Service
metadata:
  name: external-database
  namespace: production
spec:
  type: ClusterIP
  clusterIP: None
  ports:
  - port: 5432
    targetPort: 5432
---
apiVersion: v1
kind: Endpoints
metadata:
  name: external-database
  namespace: production
subsets:
- addresses:
  - ip: 10.0.50.100  # External database IP
  ports:
  - port: 5432
```

**Service Discovery Examples:**

```bash
# DNS-based service discovery
# Format: <service-name>.<namespace>.svc.cluster.local

# From within same namespace
curl http://api-service-internal

# From different namespace
curl http://api-service-internal.production.svc.cluster.local

# Headless service - direct pod access
# Format: <pod-name>.<service-name>.<namespace>.svc.cluster.local
curl http://postgres-0.postgres-headless.database.svc.cluster.local:5432

# Environment variables (injected into pods)
echo $API_SERVICE_INTERNAL_SERVICE_HOST
echo $API_SERVICE_INTERNAL_SERVICE_PORT

# Testing service endpoints
kubectl get endpoints api-service-internal -n production
kubectl describe service api-service-internal -n production

# Port forwarding for local testing
kubectl port-forward service/api-service-internal 8080:80 -n production
```

### 🎯 Key Concepts to Assess

- [ ] **Service Types**: Understanding when to use each service type
- [ ] **DNS Resolution**: How services are discovered via DNS
- [ ] **ExternalTrafficPolicy**: Impact on load balancing and source IP preservation
- [ ] **Headless Services**: Use with StatefulSets for stable network identities

### 🔄 Follow-up Questions

1. **What's the difference between ClusterIP and Headless service?**
   - ClusterIP provides a stable virtual IP; Headless (clusterIP: None) returns pod IPs directly via DNS

2. **When would you use externalTrafficPolicy: Local?**
   - To preserve client source IP and avoid extra network hop, but may cause uneven load distribution

3. **How do you expose a service to the internet in different cloud providers?**
   - LoadBalancer type with provider-specific annotations (AWS NLB/ALB, GCP, Azure)

---

*Questions 104-200 continue with topics including: StatefulSets for databases, DaemonSets for monitoring agents, Jobs and CronJobs, ConfigMaps and Secrets best practices, Persistent Volumes and Persistent Volume Claims, Storage Classes, Volume snapshots, Init containers, Sidecar patterns, Ambassador pattern, Adapter pattern, Resource quotas, LimitRanges, PodSecurityPolicies/PodSecurityStandards, RBAC (Roles, RoleBindings, ClusterRoles), ServiceAccounts, Network Policies, Ingress resources, Custom Resource Definitions (CRDs), Operators, Helm charts basics, Kustomize, kubectl tips and tricks, Kubernetes API, client-go library, Admission controllers, Mutating webhooks, Validating webhooks, Pod topology spread constraints, Taints and tolerations, Node selectors and affinity, Priority classes, Preemption, Cluster autoscaling, Vertical Pod Autoscaling, Kubelet configuration, Container runtime interface, etcd backup and restore, Cluster upgrades, Certificate management, and Kubernetes security best practices.*

