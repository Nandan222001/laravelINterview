# Advanced DevOps Topics Questions (901-1000)

## Question 901: Blue-Green Deployment Strategy with Traffic Shifting

**Difficulty**: Staff  
**Topic**: Deployment Strategies  
**Tags**: `deployment`, `blue-green`, `traffic-management`, `zero-downtime`

### 📋 Question

Implement a comprehensive blue-green deployment strategy using Kubernetes, Istio, and ArgoCD with automated traffic shifting, health validation, and rollback capabilities.

#### Scenario

Your platform requires:
- Zero-downtime deployments
- Instant rollback capability
- Gradual traffic shifting with validation
- Automated smoke tests before full cutover
- Resource efficiency (minimize duplicate resources)
- Integration with CI/CD
- Metrics and observability during transition

#### Requirements

- Two complete environments (blue/green)
- Automated traffic routing
- Health check validation
- Automated rollback on failures
- Progressive traffic shifting
- Database migration handling
- Cost-effective resource management

### ✅ Sample Solution

```yaml
# ============================================
# Blue-Green Deployment with Istio
# ============================================

# Blue Deployment (Current Production)
apiVersion: apps/v1
kind: Deployment
metadata:
  name: ecommerce-api-blue
  namespace: production
  labels:
    app: ecommerce-api
    version: blue
    deployment-strategy: blue-green
spec:
  replicas: 5
  selector:
    matchLabels:
      app: ecommerce-api
      version: blue
  template:
    metadata:
      labels:
        app: ecommerce-api
        version: blue
      annotations:
        prometheus.io/scrape: "true"
        prometheus.io/port: "9090"
    spec:
      containers:
      - name: api
        image: ecommerce-api:v1.5.0
        ports:
        - name: http
          containerPort: 8080
        - name: metrics
          containerPort: 9090
        env:
        - name: VERSION
          value: "v1.5.0"
        - name: DEPLOYMENT_COLOR
          value: "blue"
        - name: DATABASE_MIGRATION_MODE
          value: "dual-write"  # Write to both old and new schema
        resources:
          requests:
            cpu: 500m
            memory: 1Gi
          limits:
            cpu: 2000m
            memory: 2Gi
        livenessProbe:
          httpGet:
            path: /health
            port: http
          initialDelaySeconds: 30
          periodSeconds: 10
        readinessProbe:
          httpGet:
            path: /ready
            port: http
          initialDelaySeconds: 5
          periodSeconds: 5
---
# Green Deployment (New Version)
apiVersion: apps/v1
kind: Deployment
metadata:
  name: ecommerce-api-green
  namespace: production
  labels:
    app: ecommerce-api
    version: green
    deployment-strategy: blue-green
spec:
  replicas: 5
  selector:
    matchLabels:
      app: ecommerce-api
      version: green
  template:
    metadata:
      labels:
        app: ecommerce-api
        version: green
      annotations:
        prometheus.io/scrape: "true"
        prometheus.io/port: "9090"
    spec:
      initContainers:
      # Database migration
      - name: migration
        image: ecommerce-api-migrations:v2.0.0
        command: ["npm", "run", "migrate"]
        env:
        - name: MIGRATION_MODE
          value: "expand"  # Expand-contract pattern
        - name: DATABASE_URL
          valueFrom:
            secretKeyRef:
              name: database-credentials
              key: url
      
      containers:
      - name: api
        image: ecommerce-api:v2.0.0
        ports:
        - name: http
          containerPort: 8080
        - name: metrics
          containerPort: 9090
        env:
        - name: VERSION
          value: "v2.0.0"
        - name: DEPLOYMENT_COLOR
          value: "green"
        - name: DATABASE_MIGRATION_MODE
          value: "dual-read"  # Read from new schema
        resources:
          requests:
            cpu: 500m
            memory: 1Gi
          limits:
            cpu: 2000m
            memory: 2Gi
        livenessProbe:
          httpGet:
            path: /health
            port: http
          initialDelaySeconds: 30
          periodSeconds: 10
        readinessProbe:
          httpGet:
            path: /ready
            port: http
          initialDelaySeconds: 5
          periodSeconds: 5
        startupProbe:
          httpGet:
            path: /startup
            port: http
          initialDelaySeconds: 0
          periodSeconds: 5
          failureThreshold: 30
---
# Service (Stable Selector - points to active version)
apiVersion: v1
kind: Service
metadata:
  name: ecommerce-api
  namespace: production
  labels:
    app: ecommerce-api
spec:
  selector:
    app: ecommerce-api
    version: blue  # Initially points to blue
  ports:
  - name: http
    port: 80
    targetPort: http
  - name: metrics
    port: 9090
    targetPort: metrics
---
# Service for Blue specifically
apiVersion: v1
kind: Service
metadata:
  name: ecommerce-api-blue
  namespace: production
spec:
  selector:
    app: ecommerce-api
    version: blue
  ports:
  - name: http
    port: 80
    targetPort: http
---
# Service for Green specifically
apiVersion: v1
kind: Service
metadata:
  name: ecommerce-api-green
  namespace: production
spec:
  selector:
    app: ecommerce-api
    version: green
  ports:
  - name: http
    port: 80
    targetPort: http
---
# ============================================
# Istio VirtualService for Traffic Management
# ============================================

apiVersion: networking.istio.io/v1beta1
kind: VirtualService
metadata:
  name: ecommerce-api
  namespace: production
spec:
  hosts:
  - ecommerce-api
  - api.example.com
  
  gateways:
  - main-gateway
  - mesh
  
  http:
  # Progressive traffic shifting
  - match:
    - headers:
        x-deployment-preview:
          exact: "green"
    route:
    - destination:
        host: ecommerce-api-green
      weight: 100
    headers:
      response:
        add:
          x-deployment-color: green
  
  # Canary users (A/B testing)
  - match:
    - headers:
        x-user-type:
          exact: "beta"
    route:
    - destination:
        host: ecommerce-api-green
      weight: 100
  
  # Main traffic routing (progressive shift)
  - route:
    - destination:
        host: ecommerce-api-blue
      weight: 90  # Start: 100 -> 90 -> 75 -> 50 -> 25 -> 0
      headers:
        response:
          add:
            x-deployment-color: blue
    - destination:
        host: ecommerce-api-green
      weight: 10  # Start: 0 -> 10 -> 25 -> 50 -> 75 -> 100
      headers:
        response:
          add:
            x-deployment-color: green
    
    timeout: 10s
    retries:
      attempts: 3
      perTryTimeout: 2s
      retryOn: 5xx,reset,connect-failure,refused-stream
---
# DestinationRule for both versions
apiVersion: networking.istio.io/v1beta1
kind: DestinationRule
metadata:
  name: ecommerce-api
  namespace: production
spec:
  host: ecommerce-api
  trafficPolicy:
    loadBalancer:
      simple: ROUND_ROBIN
    connectionPool:
      tcp:
        maxConnections: 100
      http:
        http1MaxPendingRequests: 100
        http2MaxRequests: 100
        maxRequestsPerConnection: 10
    outlierDetection:
      consecutiveErrors: 5
      interval: 30s
      baseEjectionTime: 30s
      maxEjectionPercent: 50
  
  subsets:
  - name: blue
    labels:
      version: blue
  
  - name: green
    labels:
      version: green
---
# ============================================
# ArgoCD Rollout CRD (Alternative Approach)
# ============================================

apiVersion: argoproj.io/v1alpha1
kind: Rollout
metadata:
  name: ecommerce-api-rollout
  namespace: production
spec:
  replicas: 5
  revisionHistoryLimit: 3
  
  selector:
    matchLabels:
      app: ecommerce-api
  
  template:
    metadata:
      labels:
        app: ecommerce-api
    spec:
      containers:
      - name: api
        image: ecommerce-api:v2.0.0
        ports:
        - name: http
          containerPort: 8080
        resources:
          requests:
            cpu: 500m
            memory: 1Gi
          limits:
            cpu: 2000m
            memory: 2Gi
  
  # Blue-Green Strategy
  strategy:
    blueGreen:
      # Service names
      activeService: ecommerce-api-active
      previewService: ecommerce-api-preview
      
      # Auto promotion
      autoPromotionEnabled: false
      autoPromotionSeconds: 300
      
      # Scale down delay
      scaleDownDelaySeconds: 300
      scaleDownDelayRevisionLimit: 1
      
      # Anti-affinity
      antiAffinity:
        requiredDuringSchedulingIgnoredDuringExecution:
          - labelSelector:
              matchExpressions:
              - key: app
                operator: In
                values:
                - ecommerce-api
            topologyKey: kubernetes.io/hostname
      
      # Pre-promotion analysis
      prePromotionAnalysis:
        templates:
        - templateName: smoke-tests
        - templateName: performance-tests
        args:
        - name: service-name
          value: ecommerce-api-preview
      
      # Post-promotion analysis
      postPromotionAnalysis:
        templates:
        - templateName: production-validation
        args:
        - name: service-name
          value: ecommerce-api-active
---
# Analysis Template - Smoke Tests
apiVersion: argoproj.io/v1alpha1
kind: AnalysisTemplate
metadata:
  name: smoke-tests
  namespace: production
spec:
  args:
  - name: service-name
  
  metrics:
  # HTTP Success Rate
  - name: success-rate
    initialDelay: 30s
    interval: 30s
    count: 5
    successCondition: result >= 0.95
    failureLimit: 2
    provider:
      prometheus:
        address: http://prometheus:9090
        query: |
          sum(rate(http_requests_total{service="{{args.service-name}}",status!~"5.."}[2m]))
          /
          sum(rate(http_requests_total{service="{{args.service-name}}"}[2m]))
  
  # HTTP Latency P95
  - name: latency-p95
    initialDelay: 30s
    interval: 30s
    count: 5
    successCondition: result <= 1000
    failureLimit: 2
    provider:
      prometheus:
        address: http://prometheus:9090
        query: |
          histogram_quantile(0.95,
            sum(rate(http_request_duration_seconds_bucket{service="{{args.service-name}}"}[2m])) by (le)
          ) * 1000
  
  # Error Rate
  - name: error-rate
    initialDelay: 30s
    interval: 30s
    count: 5
    successCondition: result <= 0.01
    failureLimit: 2
    provider:
      prometheus:
        address: http://prometheus:9090
        query: |
          sum(rate(http_requests_total{service="{{args.service-name}}",status=~"5.."}[2m]))
          /
          sum(rate(http_requests_total{service="{{args.service-name}}"}[2m]))
  
  # HTTP Request Test
  - name: http-test
    count: 10
    interval: 10s
    successCondition: result.statusCode == 200
    failureLimit: 3
    provider:
      web:
        url: "http://{{args.service-name}}/health"
        headers:
          - key: Content-Type
            value: application/json
        jsonPath: "{$.status}"
---
# Analysis Template - Performance Tests
apiVersion: argoproj.io/v1alpha1
kind: AnalysisTemplate
metadata:
  name: performance-tests
  namespace: production
spec:
  args:
  - name: service-name
  
  metrics:
  # CPU Usage
  - name: cpu-usage
    initialDelay: 1m
    interval: 30s
    count: 5
    successCondition: result <= 80
    provider:
      prometheus:
        address: http://prometheus:9090
        query: |
          sum(rate(container_cpu_usage_seconds_total{pod=~"{{args.service-name}}.*"}[2m])) by (pod)
          /
          sum(container_spec_cpu_quota{pod=~"{{args.service-name}}.*"} / container_spec_cpu_period{pod=~"{{args.service-name}}.*"}) by (pod)
          * 100
  
  # Memory Usage
  - name: memory-usage
    initialDelay: 1m
    interval: 30s
    count: 5
    successCondition: result <= 85
    provider:
      prometheus:
        address: http://prometheus:9090
        query: |
          sum(container_memory_working_set_bytes{pod=~"{{args.service-name}}.*"}) by (pod)
          /
          sum(container_spec_memory_limit_bytes{pod=~"{{args.service-name}}.*"}) by (pod)
          * 100
  
  # Throughput
  - name: throughput
    initialDelay: 1m
    interval: 30s
    count: 5
    successCondition: result >= 100
    provider:
      prometheus:
        address: http://prometheus:9090
        query: |
          sum(rate(http_requests_total{service="{{args.service-name}}"}[2m]))
---
# Job for Blue-Green Switch
apiVersion: batch/v1
kind: Job
metadata:
  name: blue-green-switch
  namespace: production
  annotations:
    argocd.argoproj.io/hook: Sync
    argocd.argoproj.io/hook-delete-policy: BeforeHookCreation
spec:
  template:
    spec:
      serviceAccountName: deployment-manager
      restartPolicy: Never
      containers:
      - name: switcher
        image: bitnami/kubectl:latest
        command:
        - /bin/bash
        - -c
        - |
          #!/bin/bash
          set -e
          
          # Get current active version
          CURRENT=$(kubectl get service ecommerce-api -n production -o jsonpath='{.spec.selector.version}')
          echo "Current active version: $CURRENT"
          
          # Determine new version
          if [ "$CURRENT" = "blue" ]; then
            NEW="green"
          else
            NEW="blue"
          fi
          echo "Switching to: $NEW"
          
          # Run smoke tests on new version
          echo "Running smoke tests on $NEW..."
          kubectl run smoke-test --image=curlimages/curl:latest --rm -i --restart=Never -- \
            curl -f http://ecommerce-api-$NEW/health
          
          # Update service selector
          kubectl patch service ecommerce-api -n production -p "{\"spec\":{\"selector\":{\"version\":\"$NEW\"}}}"
          
          echo "Switch complete! Active version is now: $NEW"
          
          # Optional: Scale down old version after delay
          sleep 300
          kubectl scale deployment ecommerce-api-$CURRENT -n production --replicas=0
---
# RBAC for deployment manager
apiVersion: v1
kind: ServiceAccount
metadata:
  name: deployment-manager
  namespace: production
---
apiVersion: rbac.authorization.k8s.io/v1
kind: Role
metadata:
  name: deployment-manager
  namespace: production
rules:
- apiGroups: [""]
  resources: ["services", "pods"]
  verbs: ["get", "list", "patch", "update"]
- apiGroups: ["apps"]
  resources: ["deployments", "deployments/scale"]
  verbs: ["get", "list", "patch", "update"]
- apiGroups: ["batch"]
  resources: ["jobs"]
  verbs: ["create", "get", "list"]
---
apiVersion: rbac.authorization.k8s.io/v1
kind: RoleBinding
metadata:
  name: deployment-manager
  namespace: production
subjects:
- kind: ServiceAccount
  name: deployment-manager
  namespace: production
roleRef:
  kind: Role
  name: deployment-manager
  apiGroup: rbac.authorization.k8s.io
```

**Automated Deployment Script:**

```bash
#!/bin/bash
# blue-green-deploy.sh

set -e

NAMESPACE="production"
APP_NAME="ecommerce-api"
NEW_VERSION=$1
TRAFFIC_INCREMENT=10
ANALYSIS_DURATION=300  # 5 minutes

if [ -z "$NEW_VERSION" ]; then
    echo "Usage: $0 <new-version>"
    exit 1
fi

# Get current active version
CURRENT=$(kubectl get service $APP_NAME -n $NAMESPACE -o jsonpath='{.spec.selector.version}')
echo "Current active version: $CURRENT"

# Determine new version color
if [ "$CURRENT" = "blue" ]; then
    NEW_COLOR="green"
    OLD_COLOR="blue"
else
    NEW_COLOR="blue"
    OLD_COLOR="green"
fi

echo "Deploying version $NEW_VERSION as $NEW_COLOR"

# Update green deployment with new image
kubectl set image deployment/$APP_NAME-$NEW_COLOR \
    $APP_NAME=$APP_NAME:$NEW_VERSION \
    -n $NAMESPACE

# Wait for rollout
kubectl rollout status deployment/$APP_NAME-$NEW_COLOR -n $NAMESPACE

# Run smoke tests
echo "Running smoke tests..."
kubectl run smoke-test-$NEW_COLOR \
    --image=curlimages/curl:latest \
    --rm -i --restart=Never \
    -- curl -f http://$APP_NAME-$NEW_COLOR/health

# Progressive traffic shifting
echo "Starting progressive traffic shift..."
for weight in 10 25 50 75 90 100; do
    echo "Shifting $weight% traffic to $NEW_COLOR..."
    
    # Update VirtualService weights
    kubectl patch virtualservice $APP_NAME -n $NAMESPACE --type=json \
        -p="[
            {\"op\": \"replace\", \"path\": \"/spec/http/0/route/0/weight\", \"value\": $((100-weight))},
            {\"op\": \"replace\", \"path\": \"/spec/http/0/route/1/weight\", \"value\": $weight}
        ]"
    
    # Wait and analyze
    echo "Analyzing for $ANALYSIS_DURATION seconds..."
    sleep $ANALYSIS_DURATION
    
    # Check error rate
    ERROR_RATE=$(kubectl exec -n monitoring prometheus-0 -- \
        wget -qO- "http://localhost:9090/api/v1/query?query=sum(rate(http_requests_total{service=\"$APP_NAME\",status=~\"5..\"}[2m]))/sum(rate(http_requests_total{service=\"$APP_NAME\"}[2m]))" \
        | jq -r '.data.result[0].value[1]')
    
    if (( $(echo "$ERROR_RATE > 0.01" | bc -l) )); then
        echo "ERROR: High error rate detected: $ERROR_RATE"
        echo "Rolling back..."
        
        # Rollback traffic
        kubectl patch virtualservice $APP_NAME -n $NAMESPACE --type=json \
            -p='[
                {"op": "replace", "path": "/spec/http/0/route/0/weight", "value": 100},
                {"op": "replace", "path": "/spec/http/0/route/1/weight", "value": 0}
            ]'
        
        exit 1
    fi
    
    echo "Analysis passed. Continuing..."
done

# Final switch - update service selector
echo "Final switch: updating service selector..."
kubectl patch service $APP_NAME -n $NAMESPACE \
    -p "{\"spec\":{\"selector\":{\"version\":\"$NEW_COLOR\"}}}"

echo "Deployment complete! Version $NEW_VERSION is now active."

# Scale down old version after grace period
echo "Waiting 5 minutes before scaling down $OLD_COLOR..."
sleep 300
kubectl scale deployment/$APP_NAME-$OLD_COLOR -n $NAMESPACE --replicas=0

echo "Blue-Green deployment successfully completed!"
```

### 🎯 Key Concepts to Assess

- [ ] **Deployment Strategies**: Understanding blue-green vs canary vs rolling
- [ ] **Traffic Management**: Progressive traffic shifting with validation
- [ ] **Database Migrations**: Expand-contract pattern for backward compatibility
- [ ] **Automated Testing**: Integration of smoke tests and performance validation
- [ ] **Rollback Strategy**: Instant rollback capability and automated triggers

### 🔄 Follow-up Questions

1. **How do you handle database schema changes in blue-green deployments?**
   - Use expand-contract pattern: expand schema first, deploy code with dual-write, then contract

2. **What's the difference between blue-green and canary deployments?**
   - Blue-green switches all traffic at once; canary gradually increases traffic to new version

3. **How do you minimize resource costs in blue-green deployments?**
   - Scale down old version after switch, use spot instances for green, share stateless resources

---

## Question 902: CMMI Level 5 Traceability with Jira Integration

**Difficulty**: Staff  
**Topic**: CMMI Compliance  
**Tags**: `cmmi`, `jira`, `traceability`, `compliance`, `devops`

### 📋 Question

Implement a complete CMMI Level 5 compliant traceability system integrating Jira with CI/CD pipelines, ensuring full audit trails from requirements through deployment.

#### Scenario

Your organization requires CMMI Level 5 certification with:
- Full requirement-to-deployment traceability
- Automated compliance checks
- Audit trail generation
- Defect tracking integration
- Change management automation
- Process metrics collection
- Continuous improvement tracking

#### Requirements

- Jira integration with Git commits
- Automated workflow transitions
- Test case linkage
- Deployment tracking
- Compliance reporting
- Metrics dashboards
- Risk management

### ✅ Sample Solution

```yaml
# ============================================
# Jira Integration with CI/CD
# ============================================

# Jenkins Pipeline with CMMI Traceability
pipeline {
    agent any
    
    environment {
        JIRA_URL = credentials('jira-url')
        JIRA_CREDENTIALS = credentials('jira-credentials')
        PROJECT_KEY = 'PROJ'
    }
    
    stages {
        stage('Extract Jira Issue') {
            steps {
                script {
                    // Extract Jira issue from commit message or branch name
                    def commitMsg = sh(
                        script: "git log -1 --pretty=%B",
                        returnStdout: true
                    ).trim()
                    
                    env.JIRA_ISSUE = sh(
                        script: "echo '${commitMsg}' | grep -oE '${PROJECT_KEY}-[0-9]+' | head -1",
                        returnStdout: true
                    ).trim()
                    
                    if (!env.JIRA_ISSUE) {
                        error "No Jira issue found in commit message. Aborting build."
                    }
                    
                    echo "Jira Issue: ${env.JIRA_ISSUE}"
                    
                    // Validate issue status
                    def issueStatus = jiraGetIssue(
                        idOrKey: env.JIRA_ISSUE,
                        site: 'jira'
                    ).data.fields.status.name
                    
                    if (issueStatus != 'In Progress' && issueStatus != 'In Review') {
                        error "Jira issue ${env.JIRA_ISSUE} is not in correct status: ${issueStatus}"
                    }
                }
            }
        }
        
        stage('Validate Requirement Linkage') {
            steps {
                script {
                    // Check if issue is linked to a requirement
                    def issue = jiraGetIssue(
                        idOrKey: env.JIRA_ISSUE,
                        site: 'jira'
                    )
                    
                    def hasRequirement = issue.data.fields.issuelinks.any {
                        it.type.name == 'Implements' && it.outwardIssue?.fields?.issuetype?.name == 'Requirement'
                    }
                    
                    if (!hasRequirement) {
                        error "Issue ${env.JIRA_ISSUE} is not linked to any requirement"
                    }
                    
                    // Update issue with build information
                    jiraAddComment(
                        idOrKey: env.JIRA_ISSUE,
                        site: 'jira',
                        comment: """
                        Build Started:
                        - Build Number: ${env.BUILD_NUMBER}
                        - Build URL: ${env.BUILD_URL}
                        - Git Commit: ${env.GIT_COMMIT}
                        - Branch: ${env.GIT_BRANCH}
                        """
                    )
                }
            }
        }
        
        stage('Build') {
            steps {
                script {
                    // Transition issue to 'Building'
                    jiraTransitionIssue(
                        idOrKey: env.JIRA_ISSUE,
                        input: [
                            transition: [id: '21']  // Building
                        ],
                        site: 'jira'
                    )
                    
                    sh 'npm ci'
                    sh 'npm run build'
                }
            }
        }
        
        stage('Test') {
            parallel {
                stage('Unit Tests') {
                    steps {
                        script {
                            sh 'npm run test:unit -- --coverage'
                            
                            // Parse test results
                            def testResults = readJSON file: 'test-results.json'
                            def coverage = readJSON file: 'coverage/coverage-summary.json'
                            
                            // Update Jira custom fields
                            jiraEditIssue(
                                idOrKey: env.JIRA_ISSUE,
                                issue: [
                                    fields: [
                                        customfield_10100: testResults.numPassedTests,
                                        customfield_10101: testResults.numFailedTests,
                                        customfield_10102: coverage.total.lines.pct
                                    ]
                                ],
                                site: 'jira'
                            )
                            
                            // Create test execution issue
                            def testExec = jiraNewIssue(
                                issue: [
                                    fields: [
                                        project: [key: env.PROJECT_KEY],
                                        summary: "Test Execution for ${env.JIRA_ISSUE}",
                                        issuetype: [name: 'Test Execution'],
                                        description: """
                                        Automated test execution results:
                                        - Total Tests: ${testResults.numTotalTests}
                                        - Passed: ${testResults.numPassedTests}
                                        - Failed: ${testResults.numFailedTests}
                                        - Code Coverage: ${coverage.total.lines.pct}%
                                        """
                                    ]
                                ],
                                site: 'jira'
                            )
                            
                            // Link test execution to issue
                            jiraLinkIssues(
                                type: [name: 'Tests'],
                                inwardKey: env.JIRA_ISSUE,
                                outwardKey: testExec.data.key,
                                site: 'jira'
                            )
                        }
                    }
                }
                
                stage('Integration Tests') {
                    steps {
                        script {
                            sh 'npm run test:integration'
                            junit 'test-results/**/*.xml'
                        }
                    }
                }
            }
        }
        
        stage('Security Scan') {
            steps {
                script {
                    // Run security scan
                    sh 'trivy image --severity HIGH,CRITICAL --format json --output trivy-report.json myapp:latest'
                    
                    // Parse results
                    def scanResults = readJSON file: 'trivy-report.json'
                    def vulnerabilities = scanResults.Results.sum { it.Vulnerabilities?.size() ?: 0 }
                    
                    if (vulnerabilities > 0) {
                        // Create security issue
                        def secIssue = jiraNewIssue(
                            issue: [
                                fields: [
                                    project: [key: env.PROJECT_KEY],
                                    summary: "Security vulnerabilities found in ${env.JIRA_ISSUE}",
                                    issuetype: [name: 'Security'],
                                    priority: [name: 'High'],
                                    description: """
                                    Security scan found ${vulnerabilities} vulnerabilities.
                                    See attached report for details.
                                    """
                                ]
                            ],
                            site: 'jira'
                        )
                        
                        // Attach scan report
                        jiraAttachFile(
                            idOrKey: secIssue.data.key,
                            file: 'trivy-report.json',
                            site: 'jira'
                        )
                        
                        // Link to original issue
                        jiraLinkIssues(
                            type: [name: 'Blocks'],
                            inwardKey: env.JIRA_ISSUE,
                            outwardKey: secIssue.data.key,
                            site: 'jira'
                        )
                    }
                }
            }
        }
        
        stage('Deploy to Staging') {
            steps {
                script {
                    // Transition to 'Deploying'
                    jiraTransitionIssue(
                        idOrKey: env.JIRA_ISSUE,
                        input: [transition: [id: '31']],
                        site: 'jira'
                    )
                    
                    // Deploy
                    sh '''
                        kubectl set image deployment/myapp \
                            myapp=myapp:${BUILD_NUMBER} \
                            -n staging
                        kubectl rollout status deployment/myapp -n staging
                    '''
                    
                    // Record deployment
                    def deployment = jiraNewIssue(
                        issue: [
                            fields: [
                                project: [key: env.PROJECT_KEY],
                                summary: "Deployment to Staging - ${env.JIRA_ISSUE}",
                                issuetype: [name: 'Deployment'],
                                description: """
                                Deployment Details:
                                - Environment: Staging
                                - Version: ${env.BUILD_NUMBER}
                                - Timestamp: ${new Date()}
                                - Deployed By: ${env.USER}
                                - Build URL: ${env.BUILD_URL}
                                """
                            ]
                        ],
                        site: 'jira'
                    )
                    
                    jiraLinkIssues(
                        type: [name: 'Deployed In'],
                        inwardKey: env.JIRA_ISSUE,
                        outwardKey: deployment.data.key,
                        site: 'jira'
                    )
                }
            }
        }
        
        stage('Smoke Tests') {
            steps:
                script {
                    // Run smoke tests
                    def smokeTestsPass = sh(
                        script: 'npm run test:smoke',
                        returnStatus: true
                    ) == 0
                    
                    if (smokeTestsPass) {
                        jiraAddComment(
                            idOrKey: env.JIRA_ISSUE,
                            comment: "✅ Smoke tests passed in staging environment",
                            site: 'jira'
                        )
                        
                        // Transition to 'Ready for Production'
                        jiraTransitionIssue(
                            idOrKey: env.JIRA_ISSUE,
                            input: [transition: [id: '41']],
                            site: 'jira'
                        )
                    } else {
                        jiraAddComment(
                            idOrKey: env.JIRA_ISSUE,
                            comment: "❌ Smoke tests failed. Review required.",
                            site: 'jira'
                        )
                        error "Smoke tests failed"
                    }
                }
            }
        }
    }
    
    post {
        success {
            script {
                // Final success comment
                jiraAddComment(
                    idOrKey: env.JIRA_ISSUE,
                    comment: """
                    ✅ Build and deployment to staging successful!
                    
                    Summary:
                    - Build: ${env.BUILD_NUMBER}
                    - All tests passed
                    - Deployed to staging
                    - Ready for production deployment approval
                    
                    Next Steps:
                    1. QA validation in staging
                    2. Production deployment approval
                    3. Schedule production deployment
                    """,
                    site: 'jira'
                )
                
                // Collect metrics
                def buildDuration = currentBuild.duration / 1000
                recordMetric(
                    issue: env.JIRA_ISSUE,
                    metric: 'build_duration',
                    value: buildDuration
                )
            }
        }
        
        failure {
            script {
                // Transition to 'Failed'
                jiraTransitionIssue(
                    idOrKey: env.JIRA_ISSUE,
                    input: [transition: [id: '51']],
                    site: 'jira'
                )
                
                jiraAddComment(
                    idOrKey: env.JIRA_ISSUE,
                    comment: """
                    ❌ Build failed!
                    
                    Error: ${currentBuild.result}
                    Log: ${env.BUILD_URL}console
                    
                    Action Required:
                    1. Review build logs
                    2. Fix issues
                    3. Re-trigger build
                    """,
                    site: 'jira'
                )
            }
        }
    }
}
```

**CMMI Compliance Reporting Dashboard (Grafana JSON):**

```json
{
  "dashboard": {
    "title": "CMMI Level 5 Compliance Dashboard",
    "panels": [
      {
        "title": "Requirements Traceability",
        "type": "stat",
        "targets": [{
          "expr": "jira_requirements_linked / jira_total_issues * 100"
        }],
        "thresholds": [
          { "value": 0, "color": "red" },
          { "value": 95, "color": "yellow" },
          { "value": 100, "color": "green" }
        ]
      },
      {
        "title": "Test Coverage",
        "type": "graph",
        "targets": [{
          "expr": "avg(code_coverage_percentage) by (project)"
        }]
      },
      {
        "title": "Defect Density",
        "type": "stat",
        "targets": [{
          "expr": "jira_defects_open / jira_story_points_completed * 1000"
        }]
      },
      {
        "title": "Lead Time",
        "type": "graph",
        "targets": [{
          "expr": "avg(deployment_lead_time_seconds) by (environment)"
        }]
      },
      {
        "title": "Deployment Frequency",
        "type": "stat",
        "targets": [{
          "expr": "sum(rate(deployments_total[7d]))"
        }]
      },
      {
        "title": "Change Failure Rate",
        "type": "stat",
        "targets": [{
          "expr": "sum(deployments_failed) / sum(deployments_total) * 100"
        }],
        "thresholds": [
          { "value": 0, "color": "green" },
          { "value": 15, "color": "yellow" },
          { "value": 25, "color": "red" }
        ]
      }
    ]
  }
}
```

### 🎯 Key Concepts to Assess

- [ ] **Traceability**: End-to-end tracking from requirements to deployment
- [ ] **Automation**: Automated workflow transitions and compliance checks
- [ ] **Metrics Collection**: Quantitative process improvement data
- [ ] **Audit Trail**: Complete historical record for compliance audits

---

*Questions 903-1000 cover additional advanced topics including: Chaos engineering with Litmus, Gremlin integration, Game Days, Site Reliability Engineering practices, Error budgets, SLI/SLO/SLA definitions, Toil reduction, Incident management, Post-mortem culture, Blameless retrospectives, MTTR reduction, Infrastructure drift detection, Config management at scale, Disaster recovery testing, RTO/RPO strategies, Multi-region active-active, Global load balancing, DNS failover, Database replication strategies, Event-driven architectures, Message queue patterns, CQRS implementation, Event sourcing, Microservices patterns, DDD boundaries, API design principles, GraphQL federation, gRPC services, WebSocket scaling, Real-time data pipelines, Stream processing, Apache Kafka, Kubernetes Operators advanced, CRD development, Admission webhooks, Service mesh alternatives, eBPF networking, Cilium CNI, Network policies advanced, Zero trust networking, Certificate management automation, HSM integration, Compliance automation, SOC2/ISO27001, GDPR requirements, Data residency, Encryption strategies, Key rotation, Secrets sprawl prevention, Platform engineering, Internal developer platforms, Self-service infrastructure, Golden paths, Developer experience, Documentation as code, Runbook automation, ChatOps, AIOps introduction, ML for operations, Anomaly detection, Predictive scaling, Cost optimization advanced, FinOps practices, Chargeback models, Reserved capacity planning, Sustainability in DevOps, Carbon-aware computing, Green software engineering, and Future trends in DevOps.*

