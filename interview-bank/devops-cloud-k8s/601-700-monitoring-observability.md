# Monitoring and Observability Questions (601-700)

## Question 601: Complete Observability Stack with Prometheus, Grafana, and Loki

**Difficulty**: Senior  
**Topic**: Monitoring and Observability  
**Tags**: `prometheus`, `grafana`, `loki`, `observability`, `monitoring`

### 📋 Question

Design and implement a comprehensive observability stack using Prometheus for metrics, Loki for logs, Tempo for traces, and Grafana for visualization, with proper alerting and SLO tracking.

#### Scenario

Your microservices platform requires:
- Metrics collection from applications and infrastructure
- Centralized log aggregation
- Distributed tracing
- Real-time dashboards
- Alerting based on SLIs/SLOs
- Long-term metrics storage
- Multi-tenancy support

#### Requirements

- Deploy Prometheus with high availability
- Set up Grafana Loki for log aggregation
- Configure Tempo for distributed tracing
- Create comprehensive Grafana dashboards
- Implement alert rules and notification channels
- Set up recording rules for performance
- Configure service discovery

### ✅ Sample Solution

```yaml
# ============================================
# Prometheus Configuration
# ============================================

# prometheus-config.yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: prometheus-config
  namespace: monitoring
data:
  prometheus.yml: |
    global:
      scrape_interval: 15s
      scrape_timeout: 10s
      evaluation_interval: 15s
      external_labels:
        cluster: production
        region: us-east-1
    
    # Alertmanager configuration
    alerting:
      alertmanagers:
      - static_configs:
        - targets:
          - alertmanager:9093
        timeout: 10s
        api_version: v2
    
    # Rule files
    rule_files:
    - '/etc/prometheus/rules/*.yml'
    
    # Remote write for long-term storage
    remote_write:
    - url: http://thanos-receive:19291/api/v1/receive
      queue_config:
        capacity: 10000
        max_shards: 10
        min_shards: 1
        max_samples_per_send: 5000
        batch_send_deadline: 5s
        min_backoff: 30ms
        max_backoff: 100ms
    
    # Scrape configurations
    scrape_configs:
    # Prometheus itself
    - job_name: 'prometheus'
      static_configs:
      - targets: ['localhost:9090']
    
    # Kubernetes API server
    - job_name: 'kubernetes-apiservers'
      kubernetes_sd_configs:
      - role: endpoints
      scheme: https
      tls_config:
        ca_file: /var/run/secrets/kubernetes.io/serviceaccount/ca.crt
      bearer_token_file: /var/run/secrets/kubernetes.io/serviceaccount/token
      relabel_configs:
      - source_labels: [__meta_kubernetes_namespace, __meta_kubernetes_service_name, __meta_kubernetes_endpoint_port_name]
        action: keep
        regex: default;kubernetes;https
    
    # Kubernetes nodes
    - job_name: 'kubernetes-nodes'
      kubernetes_sd_configs:
      - role: node
      scheme: https
      tls_config:
        ca_file: /var/run/secrets/kubernetes.io/serviceaccount/ca.crt
      bearer_token_file: /var/run/secrets/kubernetes.io/serviceaccount/token
      relabel_configs:
      - action: labelmap
        regex: __meta_kubernetes_node_label_(.+)
      - target_label: __address__
        replacement: kubernetes.default.svc:443
      - source_labels: [__meta_kubernetes_node_name]
        regex: (.+)
        target_label: __metrics_path__
        replacement: /api/v1/nodes/${1}/proxy/metrics
    
    # Kubernetes pods
    - job_name: 'kubernetes-pods'
      kubernetes_sd_configs:
      - role: pod
      relabel_configs:
      # Only scrape pods with prometheus.io/scrape annotation
      - source_labels: [__meta_kubernetes_pod_annotation_prometheus_io_scrape]
        action: keep
        regex: true
      # Get port from annotation
      - source_labels: [__meta_kubernetes_pod_annotation_prometheus_io_port]
        action: replace
        regex: (.+)
        target_label: __address__
        replacement: $1
      # Get path from annotation
      - source_labels: [__meta_kubernetes_pod_annotation_prometheus_io_path]
        action: replace
        regex: (.+)
        target_label: __metrics_path__
        replacement: $1
      # Add namespace label
      - source_labels: [__meta_kubernetes_namespace]
        action: replace
        target_label: kubernetes_namespace
      # Add pod name label
      - source_labels: [__meta_kubernetes_pod_name]
        action: replace
        target_label: kubernetes_pod_name
      # Add container name label
      - source_labels: [__meta_kubernetes_pod_container_name]
        action: replace
        target_label: kubernetes_container_name
    
    # Service monitors (via Prometheus Operator)
    - job_name: 'kubernetes-service-endpoints'
      kubernetes_sd_configs:
      - role: endpoints
      relabel_configs:
      - source_labels: [__meta_kubernetes_service_annotation_prometheus_io_scrape]
        action: keep
        regex: true
      - source_labels: [__meta_kubernetes_service_annotation_prometheus_io_scheme]
        action: replace
        target_label: __scheme__
        regex: (https?)
      - source_labels: [__meta_kubernetes_service_annotation_prometheus_io_path]
        action: replace
        target_label: __metrics_path__
        regex: (.+)
      - source_labels: [__address__, __meta_kubernetes_service_annotation_prometheus_io_port]
        action: replace
        target_label: __address__
        regex: ([^:]+)(?::\\d+)?;(\\d+)
        replacement: $1:$2
      - action: labelmap
        regex: __meta_kubernetes_service_label_(.+)
      - source_labels: [__meta_kubernetes_namespace]
        action: replace
        target_label: kubernetes_namespace
      - source_labels: [__meta_kubernetes_service_name]
        action: replace
        target_label: kubernetes_name
    
    # Istio metrics
    - job_name: 'envoy-stats'
      kubernetes_sd_configs:
      - role: pod
      relabel_configs:
      - source_labels: [__meta_kubernetes_pod_container_port_name]
        action: keep
        regex: '.*-envoy-prom'
      - source_labels: [__address__, __meta_kubernetes_pod_annotation_prometheus_io_port]
        action: replace
        regex: ([^:]+)(?::\\d+)?;(\\d+)
        replacement: $1:15090
        target_label: __address__
      - action: labelmap
        regex: __meta_kubernetes_pod_label_(.+)
      - source_labels: [__meta_kubernetes_namespace]
        action: replace
        target_label: namespace
      - source_labels: [__meta_kubernetes_pod_name]
        action: replace
        target_label: pod_name

  # Recording rules for SLOs
  recording_rules.yml: |
    groups:
    - name: slo_rules
      interval: 30s
      rules:
      # HTTP request rate
      - record: job:http_requests:rate5m
        expr: sum(rate(http_requests_total[5m])) by (job, method, status)
      
      # HTTP error rate
      - record: job:http_errors:rate5m
        expr: sum(rate(http_requests_total{status=~"5.."}[5m])) by (job)
      
      # HTTP success rate (SLI)
      - record: job:http_success_rate:rate5m
        expr: |
          sum(rate(http_requests_total{status!~"5.."}[5m])) by (job)
          /
          sum(rate(http_requests_total[5m])) by (job)
      
      # HTTP latency quantiles
      - record: job:http_request_duration:p50
        expr: histogram_quantile(0.50, sum(rate(http_request_duration_seconds_bucket[5m])) by (job, le))
      
      - record: job:http_request_duration:p95
        expr: histogram_quantile(0.95, sum(rate(http_request_duration_seconds_bucket[5m])) by (job, le))
      
      - record: job:http_request_duration:p99
        expr: histogram_quantile(0.99, sum(rate(http_request_duration_seconds_bucket[5m])) by (job, le))
      
      # CPU usage
      - record: instance:cpu_usage:rate5m
        expr: |
          100 - (
            avg by (instance) (irate(node_cpu_seconds_total{mode="idle"}[5m])) * 100
          )
      
      # Memory usage percentage
      - record: instance:memory_usage:percentage
        expr: |
          100 * (1 - (node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes))
      
      # Disk usage percentage
      - record: instance:disk_usage:percentage
        expr: |
          100 * (1 - (node_filesystem_avail_bytes / node_filesystem_size_bytes))

  # Alert rules
  alert_rules.yml: |
    groups:
    - name: application_alerts
      interval: 30s
      rules:
      # High error rate
      - alert: HighErrorRate
        expr: |
          (
            sum(rate(http_requests_total{status=~"5.."}[5m])) by (job)
            /
            sum(rate(http_requests_total[5m])) by (job)
          ) > 0.05
        for: 5m
        labels:
          severity: critical
          team: backend
        annotations:
          summary: "High error rate on {{ $labels.job }}"
          description: "Error rate is {{ $value | humanizePercentage }} on {{ $labels.job }}"
          runbook_url: "https://runbooks.example.com/high-error-rate"
      
      # SLO breach - 99.9% availability
      - alert: SLOBreach
        expr: |
          job:http_success_rate:rate5m < 0.999
        for: 5m
        labels:
          severity: warning
          team: sre
        annotations:
          summary: "SLO breach on {{ $labels.job }}"
          description: "Success rate {{ $value | humanizePercentage }} is below 99.9% SLO"
      
      # High latency
      - alert: HighLatency
        expr: |
          job:http_request_duration:p95 > 1
        for: 10m
        labels:
          severity: warning
          team: backend
        annotations:
          summary: "High p95 latency on {{ $labels.job }}"
          description: "P95 latency is {{ $value }}s (threshold: 1s)"
      
      # Pod crash looping
      - alert: PodCrashLooping
        expr: |
          rate(kube_pod_container_status_restarts_total[15m]) > 0
        for: 5m
        labels:
          severity: critical
          team: platform
        annotations:
          summary: "Pod {{ $labels.namespace }}/{{ $labels.pod }} is crash looping"
          description: "Container {{ $labels.container }} is crash looping"
    
    - name: infrastructure_alerts
      interval: 30s
      rules:
      # Node down
      - alert: NodeDown
        expr: up{job="kubernetes-nodes"} == 0
        for: 5m
        labels:
          severity: critical
          team: platform
        annotations:
          summary: "Node {{ $labels.instance }} is down"
          description: "Node has been down for more than 5 minutes"
      
      # High CPU usage
      - alert: HighCPUUsage
        expr: instance:cpu_usage:rate5m > 85
        for: 10m
        labels:
          severity: warning
          team: platform
        annotations:
          summary: "High CPU usage on {{ $labels.instance }}"
          description: "CPU usage is {{ $value | humanize }}%"
      
      # High memory usage
      - alert: HighMemoryUsage
        expr: instance:memory_usage:percentage > 90
        for: 10m
        labels:
          severity: warning
          team: platform
        annotations:
          summary: "High memory usage on {{ $labels.instance }}"
          description: "Memory usage is {{ $value | humanize }}%"
      
      # Disk space low
      - alert: DiskSpaceLow
        expr: instance:disk_usage:percentage > 85
        for: 10m
        labels:
          severity: warning
          team: platform
        annotations:
          summary: "Low disk space on {{ $labels.instance }}"
          description: "Disk usage is {{ $value | humanize }}% on {{ $labels.mountpoint }}"
---
# Prometheus StatefulSet
apiVersion: apps/v1
kind: StatefulSet
metadata:
  name: prometheus
  namespace: monitoring
spec:
  serviceName: prometheus
  replicas: 2
  selector:
    matchLabels:
      app: prometheus
  template:
    metadata:
      labels:
        app: prometheus
    spec:
      serviceAccountName: prometheus
      securityContext:
        fsGroup: 65534
        runAsNonRoot: true
        runAsUser: 65534
      
      containers:
      - name: prometheus
        image: prom/prometheus:v2.45.0
        args:
        - '--config.file=/etc/prometheus/prometheus.yml'
        - '--storage.tsdb.path=/prometheus'
        - '--storage.tsdb.retention.time=15d'
        - '--storage.tsdb.retention.size=50GB'
        - '--web.enable-lifecycle'
        - '--web.enable-admin-api'
        - '--web.console.libraries=/usr/share/prometheus/console_libraries'
        - '--web.console.templates=/usr/share/prometheus/consoles'
        
        ports:
        - name: web
          containerPort: 9090
        
        resources:
          requests:
            cpu: 500m
            memory: 2Gi
          limits:
            cpu: 2000m
            memory: 4Gi
        
        volumeMounts:
        - name: config
          mountPath: /etc/prometheus
        - name: rules
          mountPath: /etc/prometheus/rules
        - name: storage
          mountPath: /prometheus
        
        livenessProbe:
          httpGet:
            path: /-/healthy
            port: 9090
          initialDelaySeconds: 30
          periodSeconds: 10
        
        readinessProbe:
          httpGet:
            path: /-/ready
            port: 9090
          initialDelaySeconds: 30
          periodSeconds: 10
      
      # Thanos sidecar for HA and long-term storage
      - name: thanos-sidecar
        image: quay.io/thanos/thanos:v0.32.0
        args:
        - sidecar
        - --tsdb.path=/prometheus
        - --prometheus.url=http://localhost:9090
        - --objstore.config-file=/etc/thanos/objstore.yml
        - --grpc-address=0.0.0.0:10901
        - --http-address=0.0.0.0:10902
        
        ports:
        - name: grpc
          containerPort: 10901
        - name: http
          containerPort: 10902
        
        resources:
          requests:
            cpu: 100m
            memory: 256Mi
          limits:
            cpu: 500m
            memory: 512Mi
        
        volumeMounts:
        - name: storage
          mountPath: /prometheus
        - name: thanos-config
          mountPath: /etc/thanos
      
      volumes:
      - name: config
        configMap:
          name: prometheus-config
      - name: rules
        configMap:
          name: prometheus-rules
      - name: thanos-config
        secret:
          secretName: thanos-objstore-config
  
  volumeClaimTemplates:
  - metadata:
      name: storage
    spec:
      accessModes: ["ReadWriteOnce"]
      storageClassName: fast-ssd
      resources:
        requests:
          storage: 100Gi
---
# ============================================
# Grafana Loki for Log Aggregation
# ============================================

# Loki ConfigMap
apiVersion: v1
kind: ConfigMap
metadata:
  name: loki-config
  namespace: monitoring
data:
  loki.yaml: |
    auth_enabled: false
    
    server:
      http_listen_port: 3100
      grpc_listen_port: 9096
      log_level: info
    
    common:
      path_prefix: /loki
      storage:
        filesystem:
          chunks_directory: /loki/chunks
          rules_directory: /loki/rules
      replication_factor: 1
      ring:
        kvstore:
          store: inmemory
    
    ingester:
      lifecycler:
        address: 127.0.0.1
        ring:
          kvstore:
            store: inmemory
          replication_factor: 1
      chunk_idle_period: 30m
      chunk_retain_period: 1m
      max_transfer_retries: 0
      wal:
        enabled: true
        dir: /loki/wal
    
    schema_config:
      configs:
      - from: 2023-01-01
        store: boltdb-shipper
        object_store: filesystem
        schema: v11
        index:
          prefix: index_
          period: 24h
    
    storage_config:
      boltdb_shipper:
        active_index_directory: /loki/boltdb-shipper-active
        cache_location: /loki/boltdb-shipper-cache
        cache_ttl: 24h
        shared_store: filesystem
      filesystem:
        directory: /loki/chunks
    
    compactor:
      working_directory: /loki/boltdb-shipper-compactor
      shared_store: filesystem
    
    limits_config:
      enforce_metric_name: false
      reject_old_samples: true
      reject_old_samples_max_age: 168h
      ingestion_rate_mb: 10
      ingestion_burst_size_mb: 20
      per_stream_rate_limit: 5MB
      per_stream_rate_limit_burst: 15MB
    
    chunk_store_config:
      max_look_back_period: 0s
    
    table_manager:
      retention_deletes_enabled: true
      retention_period: 720h
    
    ruler:
      storage:
        type: local
        local:
          directory: /loki/rules
      rule_path: /loki/rules-temp
      alertmanager_url: http://alertmanager:9093
      ring:
        kvstore:
          store: inmemory
      enable_api: true
---
# Promtail DaemonSet for log collection
apiVersion: apps/v1
kind: DaemonSet
metadata:
  name: promtail
  namespace: monitoring
spec:
  selector:
    matchLabels:
      app: promtail
  template:
    metadata:
      labels:
        app: promtail
    spec:
      serviceAccountName: promtail
      containers:
      - name: promtail
        image: grafana/promtail:2.8.0
        args:
        - -config.file=/etc/promtail/promtail.yaml
        
        ports:
        - name: http-metrics
          containerPort: 9080
        
        resources:
          requests:
            cpu: 100m
            memory: 128Mi
          limits:
            cpu: 200m
            memory: 256Mi
        
        volumeMounts:
        - name: config
          mountPath: /etc/promtail
        - name: varlog
          mountPath: /var/log
          readOnly: true
        - name: varlibdockercontainers
          mountPath: /var/lib/docker/containers
          readOnly: true
        
        securityContext:
          runAsUser: 0
          privileged: true
      
      volumes:
      - name: config
        configMap:
          name: promtail-config
      - name: varlog
        hostPath:
          path: /var/log
      - name: varlibdockercontainers
        hostPath:
          path: /var/lib/docker/containers
---
# ============================================
# Grafana Configuration
# ============================================

apiVersion: v1
kind: ConfigMap
metadata:
  name: grafana-datasources
  namespace: monitoring
data:
  datasources.yaml: |
    apiVersion: 1
    datasources:
    # Prometheus
    - name: Prometheus
      type: prometheus
      access: proxy
      url: http://prometheus:9090
      isDefault: true
      editable: true
      jsonData:
        timeInterval: 15s
        httpMethod: POST
    
    # Loki
    - name: Loki
      type: loki
      access: proxy
      url: http://loki:3100
      editable: true
      jsonData:
        maxLines: 1000
    
    # Tempo
    - name: Tempo
      type: tempo
      access: proxy
      url: http://tempo:3100
      editable: true
      jsonData:
        tracesToLogs:
          datasourceUid: loki
          tags: ['job', 'instance', 'pod', 'namespace']
          mappedTags: [{ key: 'service.name', value: 'job' }]
          mapTagNamesEnabled: false
          spanStartTimeShift: '1h'
          spanEndTimeShift: '1h'
        serviceMap:
          datasourceUid: prometheus
        nodeGraph:
          enabled: true
```

**Example Grafana Dashboard JSON:**

```json
{
  "dashboard": {
    "title": "Application SLO Dashboard",
    "uid": "app-slo",
    "panels": [
      {
        "title": "Availability (SLO: 99.9%)",
        "type": "stat",
        "targets": [
          {
            "expr": "job:http_success_rate:rate5m",
            "legendFormat": "{{ job }}"
          }
        ],
        "fieldConfig": {
          "defaults": {
            "unit": "percentunit",
            "thresholds": {
              "mode": "absolute",
              "steps": [
                { "value": 0, "color": "red" },
                { "value": 0.999, "color": "green" }
              ]
            }
          }
        }
      },
      {
        "title": "Request Rate",
        "type": "graph",
        "targets": [
          {
            "expr": "sum(rate(http_requests_total[5m])) by (job, status)",
            "legendFormat": "{{ job }} - {{ status }}"
          }
        ]
      },
      {
        "title": "P95 Latency (SLO: < 1s)",
        "type": "graph",
        "targets": [
          {
            "expr": "job:http_request_duration:p95",
            "legendFormat": "{{ job }}"
          }
        ],
        "fieldConfig": {
          "defaults": {
            "unit": "s",
            "thresholds": {
              "mode": "absolute",
              "steps": [
                { "value": 0, "color": "green" },
                { "value": 1, "color": "red" }
              ]
            }
          }
        }
      }
    ]
  }
}
```

### 🎯 Key Concepts to Assess

- [ ] **Three Pillars**: Understanding metrics, logs, and traces integration
- [ ] **SLO Tracking**: Recording rules and alert rules for SLI/SLO monitoring
- [ ] **Service Discovery**: Kubernetes SD and relabeling configuration
- [ ] **High Availability**: HA Prometheus setup with Thanos

---

*Questions 602-700 continue with topics including: Thanos for long-term storage, Cortex multi-tenancy, Victoria Metrics, OpenTelemetry collector, Jaeger architecture, Zipkin integration, Distributed tracing best practices, Log parsing with Promtail, FluentBit and Fluentd, Elasticsearch for logs, OpenSearch, CloudWatch integration, Datadog APM, New Relic, Dynatrace, Custom metrics exporters, Prometheus client libraries, StatsD and Graphite, InfluxDB time series, Telegraf collection, Grafana alerting, Alert routing with Alertmanager, PagerDuty integration, Slack notifications, Webhook receivers, Silence management, Alert inhibition rules, Dashboard design best practices, Variable templating, Dashboard provisioning, Snapshot sharing, Dashboard versioning, Grafana plugins, Custom panels, Data transformations, Query optimization, PromQL advanced queries, LogQL for Loki, TraceQL for Tempo, Metric cardinality management, Label best practices, Recording rule optimization, Remote write tuning, Federation setup, Multi-cluster monitoring, Cost optimization, Retention policies, Downsampling strategies, and Compliance logging.*

