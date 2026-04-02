# Helm Charts and GitOps Questions (701-800)

## Question 701: Production-Ready Helm Chart Development

**Difficulty**: Senior  
**Topic**: Helm Charts  
**Tags**: `helm`, `kubernetes`, `packaging`, `deployment`

### 📋 Question

Create a comprehensive, production-ready Helm chart for a microservices application with proper templating, values management, hooks, tests, and multi-environment support.

#### Scenario

Your application needs a Helm chart that supports:
- Multiple environments (dev, staging, production)
- Conditional resource creation
- Pre-install database migrations
- Post-install verification tests
- Secrets management
- ConfigMap generation
- Dependencies on other charts
- Rollback capability

#### Requirements

- Proper chart structure and metadata
- Flexible values.yaml with overrides
- Template helpers and named templates
- Helm hooks for lifecycle management
- Chart tests for validation
- Dependencies management
- Documentation

### ✅ Sample Solution

```yaml
# Chart.yaml
apiVersion: v2
name: ecommerce-api
description: A Helm chart for E-commerce API microservice
type: application
version: 1.0.0
appVersion: "2.0.0"
keywords:
  - ecommerce
  - api
  - microservice
home: https://github.com/example/ecommerce-api
sources:
  - https://github.com/example/ecommerce-api
maintainers:
  - name: DevOps Team
    email: devops@example.com
    url: https://devops.example.com
icon: https://example.com/icon.png
kubeVersion: ">=1.24.0-0"
dependencies:
  - name: postgresql
    version: 12.x.x
    repository: https://charts.bitnami.com/bitnami
    condition: postgresql.enabled
    tags:
      - database
  - name: redis
    version: 17.x.x
    repository: https://charts.bitnami.com/bitnami
    condition: redis.enabled
    tags:
      - cache
annotations:
  category: Application
  licenses: Apache-2.0
---
# values.yaml
# Default values for ecommerce-api chart

## Global parameters
global:
  imageRegistry: ""
  imagePullSecrets: []
  storageClass: ""

## Common parameters
nameOverride: ""
fullnameOverride: ""
commonLabels: {}
commonAnnotations: {}

## Image parameters
image:
  registry: docker.io
  repository: mycompany/ecommerce-api
  tag: "2.0.0"
  pullPolicy: IfNotPresent
  pullSecrets: []

## Deployment parameters
replicaCount: 3

strategy:
  type: RollingUpdate
  rollingUpdate:
    maxSurge: 1
    maxUnavailable: 0

## Pod annotations
podAnnotations:
  prometheus.io/scrape: "true"
  prometheus.io/port: "9090"
  prometheus.io/path: "/metrics"

## Pod labels
podLabels: {}

## Security context
podSecurityContext:
  fsGroup: 1000
  runAsNonRoot: true
  runAsUser: 1000

containerSecurityContext:
  allowPrivilegeEscalation: false
  readOnlyRootFilesystem: true
  capabilities:
    drop:
      - ALL

## Service parameters
service:
  type: ClusterIP
  port: 80
  targetPort: 8080
  nodePort: ""
  annotations: {}
  sessionAffinity: None

## Ingress parameters
ingress:
  enabled: true
  className: nginx
  annotations:
    cert-manager.io/cluster-issuer: letsencrypt-prod
    nginx.ingress.kubernetes.io/ssl-redirect: "true"
    nginx.ingress.kubernetes.io/rate-limit: "100"
  hosts:
    - host: api.example.com
      paths:
        - path: /
          pathType: Prefix
  tls:
    - secretName: api-example-com-tls
      hosts:
        - api.example.com

## Resource limits
resources:
  requests:
    cpu: 250m
    memory: 512Mi
  limits:
    cpu: 1000m
    memory: 1Gi

## Autoscaling
autoscaling:
  enabled: true
  minReplicas: 3
  maxReplicas: 10
  targetCPUUtilizationPercentage: 70
  targetMemoryUtilizationPercentage: 80
  behavior:
    scaleDown:
      stabilizationWindowSeconds: 300
      policies:
        - type: Percent
          value: 50
          periodSeconds: 60
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

## Pod Disruption Budget
podDisruptionBudget:
  enabled: true
  minAvailable: 1

## Health checks
livenessProbe:
  httpGet:
    path: /health
    port: http
  initialDelaySeconds: 30
  periodSeconds: 10
  timeoutSeconds: 5
  failureThreshold: 3

readinessProbe:
  httpGet:
    path: /ready
    port: http
  initialDelaySeconds: 5
  periodSeconds: 5
  timeoutSeconds: 3
  failureThreshold: 3

startupProbe:
  httpGet:
    path: /startup
    port: http
  initialDelaySeconds: 0
  periodSeconds: 5
  timeoutSeconds: 3
  failureThreshold: 30

## Environment variables
env:
  - name: NODE_ENV
    value: production
  - name: PORT
    value: "8080"
  - name: LOG_LEVEL
    value: info

## Environment from ConfigMap
envFrom:
  - configMapRef:
      name: ecommerce-api-config
  - secretRef:
      name: ecommerce-api-secrets

## ConfigMap data
configMap:
  create: true
  data:
    APP_NAME: ecommerce-api
    CACHE_ENABLED: "true"
    MAX_CONNECTIONS: "100"

## Secrets (should be provided via values override or external secrets)
secrets:
  create: true
  data: {}
    # DATABASE_URL: ""
    # JWT_SECRET: ""
    # API_KEY: ""

## ServiceAccount
serviceAccount:
  create: true
  annotations: {}
  name: ""

## RBAC
rbac:
  create: true
  rules:
    - apiGroups: [""]
      resources: ["configmaps"]
      verbs: ["get", "list"]

## Node selector
nodeSelector: {}

## Tolerations
tolerations: []

## Affinity
affinity:
  podAntiAffinity:
    preferredDuringSchedulingIgnoredDuringExecution:
      - weight: 100
        podAffinityTerm:
          labelSelector:
            matchExpressions:
              - key: app.kubernetes.io/name
                operator: In
                values:
                  - ecommerce-api
          topologyKey: kubernetes.io/hostname

## Volumes
volumes:
  - name: tmp
    emptyDir: {}
  - name: cache
    emptyDir:
      sizeLimit: 1Gi

volumeMounts:
  - name: tmp
    mountPath: /tmp
  - name: cache
    mountPath: /app/cache

## Init containers
initContainers:
  - name: wait-for-db
    image: busybox:1.35
    command:
      - sh
      - -c
      - |
        until nc -z postgresql 5432; do
          echo "Waiting for database..."
          sleep 2
        done

## Database migration job
migration:
  enabled: true
  image:
    registry: docker.io
    repository: mycompany/ecommerce-api-migrations
    tag: "2.0.0"
  resources:
    requests:
      cpu: 100m
      memory: 128Mi
    limits:
      cpu: 500m
      memory: 256Mi

## PostgreSQL dependency configuration
postgresql:
  enabled: true
  auth:
    username: api_user
    password: ""  # Should be provided via secrets
    database: ecommerce
  primary:
    persistence:
      enabled: true
      size: 20Gi
    resources:
      requests:
        cpu: 250m
        memory: 512Mi

## Redis dependency configuration
redis:
  enabled: true
  auth:
    enabled: true
    password: ""  # Should be provided via secrets
  master:
    persistence:
      enabled: true
      size: 8Gi
  replica:
    replicaCount: 2

## Monitoring
monitoring:
  enabled: true
  serviceMonitor:
    enabled: true
    interval: 30s
    path: /metrics

## NetworkPolicy
networkPolicy:
  enabled: true
  policyTypes:
    - Ingress
    - Egress
  ingress:
    - from:
        - namespaceSelector:
            matchLabels:
              name: ingress-nginx
      ports:
        - protocol: TCP
          port: 8080
  egress:
    - to:
        - namespaceSelector: {}
      ports:
        - protocol: TCP
          port: 5432  # PostgreSQL
        - protocol: TCP
          port: 6379  # Redis
        - protocol: TCP
          port: 443   # HTTPS
        - protocol: UDP
          port: 53    # DNS
---
# templates/_helpers.tpl
{{/*
Expand the name of the chart.
*/}}
{{- define "ecommerce-api.name" -}}
{{- default .Chart.Name .Values.nameOverride | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Create a default fully qualified app name.
*/}}
{{- define "ecommerce-api.fullname" -}}
{{- if .Values.fullnameOverride }}
{{- .Values.fullnameOverride | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- $name := default .Chart.Name .Values.nameOverride }}
{{- if contains $name .Release.Name }}
{{- .Release.Name | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- printf "%s-%s" .Release.Name $name | trunc 63 | trimSuffix "-" }}
{{- end }}
{{- end }}
{{- end }}

{{/*
Create chart name and version as used by the chart label.
*/}}
{{- define "ecommerce-api.chart" -}}
{{- printf "%s-%s" .Chart.Name .Chart.Version | replace "+" "_" | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Common labels
*/}}
{{- define "ecommerce-api.labels" -}}
helm.sh/chart: {{ include "ecommerce-api.chart" . }}
{{ include "ecommerce-api.selectorLabels" . }}
{{- if .Chart.AppVersion }}
app.kubernetes.io/version: {{ .Chart.AppVersion | quote }}
{{- end }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- with .Values.commonLabels }}
{{ toYaml . }}
{{- end }}
{{- end }}

{{/*
Selector labels
*/}}
{{- define "ecommerce-api.selectorLabels" -}}
app.kubernetes.io/name: {{ include "ecommerce-api.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
{{- end }}

{{/*
Create the name of the service account to use
*/}}
{{- define "ecommerce-api.serviceAccountName" -}}
{{- if .Values.serviceAccount.create }}
{{- default (include "ecommerce-api.fullname" .) .Values.serviceAccount.name }}
{{- else }}
{{- default "default" .Values.serviceAccount.name }}
{{- end }}
{{- end }}

{{/*
Return the proper image name
*/}}
{{- define "ecommerce-api.image" -}}
{{- $registryName := .Values.image.registry -}}
{{- $repositoryName := .Values.image.repository -}}
{{- $tag := .Values.image.tag | toString -}}
{{- if .Values.global }}
    {{- if .Values.global.imageRegistry }}
        {{- $registryName = .Values.global.imageRegistry -}}
    {{- end -}}
{{- end -}}
{{- printf "%s/%s:%s" $registryName $repositoryName $tag -}}
{{- end }}

{{/*
Return image pull secrets
*/}}
{{- define "ecommerce-api.imagePullSecrets" -}}
{{- $pullSecrets := list }}
{{- if .Values.global }}
  {{- range .Values.global.imagePullSecrets }}
    {{- $pullSecrets = append $pullSecrets . }}
  {{- end }}
{{- end }}
{{- range .Values.image.pullSecrets }}
  {{- $pullSecrets = append $pullSecrets . }}
{{- end }}
{{- if (not (empty $pullSecrets)) }}
imagePullSecrets:
{{- range $pullSecrets }}
  - name: {{ . }}
{{- end }}
{{- end }}
{{- end }}

{{/*
Render environment variables
*/}}
{{- define "ecommerce-api.env" -}}
{{- range .Values.env }}
- name: {{ .name }}
  value: {{ .value | quote }}
{{- end }}
{{- end }}
---
# templates/deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: {{ include "ecommerce-api.fullname" . }}
  namespace: {{ .Release.Namespace }}
  labels:
    {{- include "ecommerce-api.labels" . | nindent 4 }}
  {{- with .Values.commonAnnotations }}
  annotations:
    {{- toYaml . | nindent 4 }}
  {{- end }}
spec:
  {{- if not .Values.autoscaling.enabled }}
  replicas: {{ .Values.replicaCount }}
  {{- end }}
  strategy:
    {{- toYaml .Values.strategy | nindent 4 }}
  selector:
    matchLabels:
      {{- include "ecommerce-api.selectorLabels" . | nindent 6 }}
  template:
    metadata:
      annotations:
        checksum/config: {{ include (print $.Template.BasePath "/configmap.yaml") . | sha256sum }}
        checksum/secret: {{ include (print $.Template.BasePath "/secret.yaml") . | sha256sum }}
        {{- with .Values.podAnnotations }}
        {{- toYaml . | nindent 8 }}
        {{- end }}
      labels:
        {{- include "ecommerce-api.selectorLabels" . | nindent 8 }}
        {{- with .Values.podLabels }}
        {{- toYaml . | nindent 8 }}
        {{- end }}
    spec:
      {{- include "ecommerce-api.imagePullSecrets" . | nindent 6 }}
      serviceAccountName: {{ include "ecommerce-api.serviceAccountName" . }}
      securityContext:
        {{- toYaml .Values.podSecurityContext | nindent 8 }}
      
      {{- if .Values.initContainers }}
      initContainers:
        {{- toYaml .Values.initContainers | nindent 8 }}
      {{- end }}
      
      containers:
      - name: {{ .Chart.Name }}
        image: {{ include "ecommerce-api.image" . }}
        imagePullPolicy: {{ .Values.image.pullPolicy }}
        securityContext:
          {{- toYaml .Values.containerSecurityContext | nindent 12 }}
        ports:
        - name: http
          containerPort: {{ .Values.service.targetPort }}
          protocol: TCP
        - name: metrics
          containerPort: 9090
          protocol: TCP
        env:
          {{- include "ecommerce-api.env" . | nindent 10 }}
        {{- if .Values.envFrom }}
        envFrom:
          {{- toYaml .Values.envFrom | nindent 10 }}
        {{- end }}
        livenessProbe:
          {{- toYaml .Values.livenessProbe | nindent 10 }}
        readinessProbe:
          {{- toYaml .Values.readinessProbe | nindent 10 }}
        {{- if .Values.startupProbe }}
        startupProbe:
          {{- toYaml .Values.startupProbe | nindent 10 }}
        {{- end }}
        resources:
          {{- toYaml .Values.resources | nindent 10 }}
        volumeMounts:
          {{- toYaml .Values.volumeMounts | nindent 10 }}
      
      volumes:
        {{- toYaml .Values.volumes | nindent 8 }}
      
      {{- with .Values.nodeSelector }}
      nodeSelector:
        {{- toYaml . | nindent 8 }}
      {{- end }}
      {{- with .Values.affinity }}
      affinity:
        {{- toYaml . | nindent 8 }}
      {{- end }}
      {{- with .Values.tolerations }}
      tolerations:
        {{- toYaml . | nindent 8 }}
      {{- end }}
---
# templates/hooks/pre-install-migration.yaml
{{- if .Values.migration.enabled }}
apiVersion: batch/v1
kind: Job
metadata:
  name: {{ include "ecommerce-api.fullname" . }}-migration
  namespace: {{ .Release.Namespace }}
  labels:
    {{- include "ecommerce-api.labels" . | nindent 4 }}
    app.kubernetes.io/component: migration
  annotations:
    "helm.sh/hook": pre-install,pre-upgrade
    "helm.sh/hook-weight": "-5"
    "helm.sh/hook-delete-policy": before-hook-creation
spec:
  backoffLimit: 3
  template:
    metadata:
      labels:
        {{- include "ecommerce-api.selectorLabels" . | nindent 8 }}
        app.kubernetes.io/component: migration
    spec:
      restartPolicy: Never
      serviceAccountName: {{ include "ecommerce-api.serviceAccountName" . }}
      containers:
      - name: migration
        image: {{ printf "%s/%s:%s" .Values.migration.image.registry .Values.migration.image.repository .Values.migration.image.tag }}
        command: ["npm", "run", "migrate"]
        envFrom:
        {{- if .Values.envFrom }}
          {{- toYaml .Values.envFrom | nindent 10 }}
        {{- end }}
        resources:
          {{- toYaml .Values.migration.resources | nindent 10 }}
{{- end }}
---
# templates/tests/test-connection.yaml
apiVersion: v1
kind: Pod
metadata:
  name: {{ include "ecommerce-api.fullname" . }}-test-connection
  namespace: {{ .Release.Namespace }}
  labels:
    {{- include "ecommerce-api.labels" . | nindent 4 }}
  annotations:
    "helm.sh/hook": test
    "helm.sh/hook-weight": "1"
    "helm.sh/hook-delete-policy": hook-succeeded
spec:
  restartPolicy: Never
  containers:
  - name: wget
    image: busybox:1.35
    command: ['wget']
    args: 
    - '--spider'
    - '--timeout=30'
    - 'http://{{ include "ecommerce-api.fullname" . }}:{{ .Values.service.port }}/health'
---
# templates/tests/test-api-response.yaml
apiVersion: v1
kind: Pod
metadata:
  name: {{ include "ecommerce-api.fullname" . }}-test-api
  namespace: {{ .Release.Namespace }}
  labels:
    {{- include "ecommerce-api.labels" . | nindent 4 }}
  annotations:
    "helm.sh/hook": test
    "helm.sh/hook-weight": "2"
    "helm.sh/hook-delete-policy": hook-succeeded
spec:
  restartPolicy: Never
  containers:
  - name: curl
    image: curlimages/curl:8.1.0
    command: ['curl']
    args:
    - '--fail'
    - '--retry'
    - '3'
    - '--retry-delay'
    - '5'
    - 'http://{{ include "ecommerce-api.fullname" . }}:{{ .Values.service.port }}/api/v1/health'
```

**Environment-specific values:**

```yaml
# values-production.yaml
replicaCount: 5

resources:
  requests:
    cpu: 500m
    memory: 1Gi
  limits:
    cpu: 2000m
    memory: 2Gi

autoscaling:
  enabled: true
  minReplicas: 5
  maxReplicas: 20

postgresql:
  enabled: true
  primary:
    persistence:
      size: 100Gi
    resources:
      requests:
        cpu: 1000m
        memory: 2Gi

redis:
  enabled: true
  master:
    persistence:
      size: 20Gi
---
# values-development.yaml
replicaCount: 1

resources:
  requests:
    cpu: 100m
    memory: 256Mi
  limits:
    cpu: 500m
    memory: 512Mi

autoscaling:
  enabled: false

postgresql:
  enabled: true
  primary:
    persistence:
      size: 10Gi

redis:
  enabled: false
```

**Helm commands:**

```bash
# Install chart
helm install ecommerce-api ./ecommerce-api \
  --namespace production \
  --create-namespace \
  --values ./ecommerce-api/values-production.yaml \
  --set image.tag=2.0.1 \
  --wait \
  --timeout 10m

# Upgrade chart
helm upgrade ecommerce-api ./ecommerce-api \
  --namespace production \
  --values ./ecommerce-api/values-production.yaml \
  --set image.tag=2.0.2 \
  --atomic \
  --cleanup-on-fail

# Rollback
helm rollback ecommerce-api 1 --namespace production

# Test release
helm test ecommerce-api --namespace production

# Dry run
helm install ecommerce-api ./ecommerce-api \
  --namespace production \
  --values ./ecommerce-api/values-production.yaml \
  --dry-run \
  --debug

# Template rendering
helm template ecommerce-api ./ecommerce-api \
  --namespace production \
  --values ./ecommerce-api/values-production.yaml

# Lint chart
helm lint ./ecommerce-api

# Package chart
helm package ./ecommerce-api

# Dependency management
helm dependency update ./ecommerce-api
helm dependency build ./ecommerce-api
```

### 🎯 Key Concepts to Assess

- [ ] **Template Functions**: Understanding Go templating and Helm functions
- [ ] **Hooks**: Proper use of pre/post install/upgrade hooks
- [ ] **Dependencies**: Managing chart dependencies and conditions
- [ ] **Values Management**: Multi-environment configuration strategy

### 🔄 Follow-up Questions

1. **What's the difference between --set and --values?**
   - --set provides individual key-value overrides; --values loads entire YAML file

2. **When would you use a pre-upgrade hook?**
   - For database migrations, backups, or validation before rolling out new version

3. **How does Helm handle failed upgrades with --atomic?**
   - Automatically rolls back to previous release if upgrade fails

---

*Questions 702-800 continue with topics including: Helm chart repositories, ChartMuseum, Harbor integration, Helm OCI registry support, Helm secrets with SOPS, Sealed Secrets, External Secrets Operator, Helm diff plugin, Helm unittest, Chart testing strategies, Helmfile for multi-release management, ArgoCD for GitOps, ArgoCD ApplicationSet, App of Apps pattern, Sync waves and hooks, Progressive sync, Health assessment, Automated sync policies, ArgoCD notifications, Image updater, Kustomize with ArgoCD, Flux CD architecture, Flux Kustomizations, Flux HelmReleases, Source controller, Notification controller, Image automation, Git repository structure, Monorepo vs polyrepo, Environment promotion, Branch-based vs directory-based, Config drift detection, Reconciliation loops, GitOps security, Signed commits, Policy enforcement, Secrets management in GitOps, Disaster recovery with GitOps, Backup strategies, Multi-cluster GitOps, Cluster bootstrapping, and GitOps best practices.*

