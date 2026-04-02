# Docker and Container Technologies Questions (401-500)

## Question 401: Docker Multi-Stage Builds for Production

**Difficulty**: Senior  
**Topic**: Docker Multi-Stage Builds  
**Tags**: `docker`, `multi-stage`, `optimization`, `security`

### 📋 Question

Design an optimized multi-stage Dockerfile for a Node.js application that minimizes image size, improves build speed, and enhances security through proper layering and distroless final images.

#### Scenario

Your Node.js application needs:
- Fast build times with layer caching
- Minimal production image size
- Separation of build and runtime dependencies
- No unnecessary build tools in production
- Security scanning during build
- Development and production variants

#### Requirements

- Use multi-stage builds
- Implement proper layer caching
- Use distroless or Alpine base images
- Separate dev dependencies from production
- Add security scanning
- Implement health checks
- Use non-root user

### ✅ Sample Solution

```dockerfile
# ============================================
# Build Stage - Node.js Application
# ============================================

# Stage 1: Dependencies (cached layer)
FROM node:18-alpine AS dependencies

# Install build dependencies for native modules
RUN apk add --no-cache \
    python3 \
    make \
    g++ \
    libc6-compat

# Set working directory
WORKDIR /app

# Copy package files for dependency installation
# This layer is cached unless package files change
COPY package*.json ./
COPY yarn.lock* ./

# Install all dependencies (including dev dependencies for build)
RUN npm ci --prefer-offline --no-audit

# ============================================
# Stage 2: Builder - Compile Application
# ============================================

FROM node:18-alpine AS builder

WORKDIR /app

# Copy dependencies from previous stage
COPY --from=dependencies /app/node_modules ./node_modules

# Copy source code
COPY . .

# Build application
RUN npm run build

# Remove dev dependencies, keep only production dependencies
RUN npm prune --production

# ============================================
# Stage 3: Security Scanner
# ============================================

FROM aquasec/trivy:latest AS security-scan

# Copy built application
COPY --from=builder /app /scan

# Run security scan
RUN trivy fs --severity HIGH,CRITICAL --exit-code 0 /scan

# ============================================
# Stage 4: Production Runtime (Distroless)
# ============================================

FROM gcr.io/distroless/nodejs18-debian11 AS production

# Labels for metadata
LABEL maintainer="devops@example.com" \
      version="1.0.0" \
      description="Production-ready Node.js application"

# Set environment
ENV NODE_ENV=production \
    PORT=8080

# Set working directory
WORKDIR /app

# Copy only necessary files from builder
COPY --from=builder --chown=nonroot:nonroot /app/dist ./dist
COPY --from=builder --chown=nonroot:nonroot /app/node_modules ./node_modules
COPY --from=builder --chown=nonroot:nonroot /app/package*.json ./

# Use non-root user (distroless provides 'nonroot' user)
USER nonroot

# Expose port
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD ["/nodejs/bin/node", "-e", "require('http').get('http://localhost:8080/health', (res) => { process.exit(res.statusCode === 200 ? 0 : 1); });"]

# Start application
CMD ["dist/main.js"]

# ============================================
# Stage 5: Development Runtime
# ============================================

FROM node:18-alpine AS development

# Install development tools
RUN apk add --no-cache \
    git \
    curl \
    bash

WORKDIR /app

# Copy dependencies
COPY --from=dependencies /app/node_modules ./node_modules

# Copy source code
COPY . .

# Create non-root user
RUN addgroup -g 1001 -S nodejs && \
    adduser -S nodejs -u 1001

# Change ownership
RUN chown -R nodejs:nodejs /app

USER nodejs

EXPOSE 8080 9229

# Development with hot reload
CMD ["npm", "run", "dev"]

# ============================================
# Alternative: Alpine-based Production
# ============================================

FROM node:18-alpine AS production-alpine

RUN apk add --no-cache \
    tini \
    dumb-init

WORKDIR /app

# Copy from builder
COPY --from=builder /app/dist ./dist
COPY --from=builder /app/node_modules ./node_modules
COPY --from=builder /app/package*.json ./

# Create non-root user
RUN addgroup -g 1001 -S nodejs && \
    adduser -S nodejs -u 1001 && \
    chown -R nodejs:nodejs /app

USER nodejs

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD node -e "require('http').get('http://localhost:8080/health', (res) => { process.exit(res.statusCode === 200 ? 0 : 1); });"

# Use tini as init system
ENTRYPOINT ["/sbin/tini", "--"]

CMD ["node", "dist/main.js"]
```

**Docker Compose for Multi-Environment:**

```yaml
# docker-compose.yml
version: '3.9'

services:
  # Development environment
  app-dev:
    build:
      context: .
      target: development
      dockerfile: Dockerfile
      cache_from:
        - app-dev:latest
      args:
        NODE_ENV: development
    image: app-dev:latest
    container_name: app-dev
    ports:
      - "8080:8080"
      - "9229:9229"  # Debug port
    volumes:
      - ./src:/app/src:ro
      - ./package.json:/app/package.json:ro
      - node_modules:/app/node_modules
    environment:
      - NODE_ENV=development
      - DEBUG=app:*
      - LOG_LEVEL=debug
    env_file:
      - .env.development
    networks:
      - app-network
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_healthy
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8080/health"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s
    restart: unless-stopped
    command: npm run dev

  # Production environment
  app-prod:
    build:
      context: .
      target: production
      dockerfile: Dockerfile
      cache_from:
        - app-prod:latest
      args:
        NODE_ENV: production
    image: app-prod:latest
    container_name: app-prod
    ports:
      - "8080:8080"
    environment:
      - NODE_ENV=production
      - LOG_LEVEL=info
    env_file:
      - .env.production
    networks:
      - app-network
    depends_on:
      - postgres
      - redis
    deploy:
      replicas: 3
      resources:
        limits:
          cpus: '1.0'
          memory: 512M
        reservations:
          cpus: '0.5'
          memory: 256M
      restart_policy:
        condition: on-failure
        delay: 5s
        max_attempts: 3
        window: 120s
      update_config:
        parallelism: 1
        delay: 10s
        failure_action: rollback
        order: start-first
    healthcheck:
      test: ["CMD-SHELL", "wget --no-verbose --tries=1 --spider http://localhost:8080/health || exit 1"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s
    restart: unless-stopped

  # PostgreSQL Database
  postgres:
    image: postgres:15-alpine
    container_name: postgres
    environment:
      POSTGRES_DB: appdb
      POSTGRES_USER: appuser
      POSTGRES_PASSWORD: ${DB_PASSWORD}
      POSTGRES_INITDB_ARGS: "--encoding=UTF8 --locale=en_US.UTF-8"
      PGDATA: /var/lib/postgresql/data/pgdata
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./init-scripts:/docker-entrypoint-initdb.d:ro
    ports:
      - "5432:5432"
    networks:
      - app-network
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U appuser -d appdb"]
      interval: 10s
      timeout: 5s
      retries: 5
    restart: unless-stopped
    command: 
      - "postgres"
      - "-c"
      - "max_connections=200"
      - "-c"
      - "shared_buffers=256MB"

  # Redis Cache
  redis:
    image: redis:7-alpine
    container_name: redis
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
      - ./redis.conf:/usr/local/etc/redis/redis.conf:ro
    networks:
      - app-network
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 3s
      retries: 5
    restart: unless-stopped
    command: redis-server /usr/local/etc/redis/redis.conf --appendonly yes

  # NGINX Reverse Proxy
  nginx:
    image: nginx:alpine
    container_name: nginx
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/nginx.conf:/etc/nginx/nginx.conf:ro
      - ./nginx/ssl:/etc/nginx/ssl:ro
      - nginx_logs:/var/log/nginx
    networks:
      - app-network
    depends_on:
      - app-prod
    healthcheck:
      test: ["CMD", "wget", "--no-verbose", "--tries=1", "--spider", "http://localhost/health"]
      interval: 30s
      timeout: 10s
      retries: 3
    restart: unless-stopped

volumes:
  node_modules:
  postgres_data:
  redis_data:
  nginx_logs:

networks:
  app-network:
    driver: bridge
    ipam:
      config:
        - subnet: 172.25.0.0/16
```

**Build and Optimization Scripts:**

```bash
#!/bin/bash
# build.sh - Optimized build script

set -e

APP_NAME="myapp"
REGISTRY="your-registry.io"
VERSION=$(git describe --tags --always --dirty)
BUILD_DATE=$(date -u +'%Y-%m-%dT%H:%M:%SZ')
VCS_REF=$(git rev-parse --short HEAD)

echo "Building ${APP_NAME}:${VERSION}"

# Enable BuildKit for better performance
export DOCKER_BUILDKIT=1
export COMPOSE_DOCKER_CLI_BUILD=1

# Build with cache
docker build \
  --target production \
  --build-arg NODE_ENV=production \
  --build-arg BUILD_DATE="${BUILD_DATE}" \
  --build-arg VCS_REF="${VCS_REF}" \
  --build-arg VERSION="${VERSION}" \
  --cache-from ${REGISTRY}/${APP_NAME}:latest \
  --cache-from ${REGISTRY}/${APP_NAME}:${VERSION} \
  --tag ${REGISTRY}/${APP_NAME}:${VERSION} \
  --tag ${REGISTRY}/${APP_NAME}:latest \
  --label org.opencontainers.image.created="${BUILD_DATE}" \
  --label org.opencontainers.image.version="${VERSION}" \
  --label org.opencontainers.image.revision="${VCS_REF}" \
  --label org.opencontainers.image.title="${APP_NAME}" \
  --label org.opencontainers.image.description="Production application" \
  --label org.opencontainers.image.source="https://github.com/example/${APP_NAME}" \
  .

# Inspect image
echo "Image details:"
docker images ${REGISTRY}/${APP_NAME}:${VERSION}

# Show image layers
echo "Image layers:"
docker history ${REGISTRY}/${APP_NAME}:${VERSION}

# Run security scan
echo "Running security scan..."
docker run --rm \
  -v /var/run/docker.sock:/var/run/docker.sock \
  aquasec/trivy:latest \
  image \
  --severity HIGH,CRITICAL \
  --exit-code 1 \
  ${REGISTRY}/${APP_NAME}:${VERSION}

# Push to registry
echo "Pushing to registry..."
docker push ${REGISTRY}/${APP_NAME}:${VERSION}
docker push ${REGISTRY}/${APP_NAME}:latest

echo "Build complete!"
```

**.dockerignore:**

```
# Dependencies
node_modules/
npm-debug.log*
yarn-debug.log*
yarn-error.log*

# Testing
coverage/
.nyc_output/
*.test.js
*.spec.js
__tests__/
__mocks__/
test/

# Build outputs (will be generated in container)
dist/
build/
out/

# IDE
.vscode/
.idea/
*.swp
*.swo
*~

# Git
.git/
.gitignore
.gitattributes

# CI/CD
.github/
.gitlab-ci.yml
.travis.yml
Jenkinsfile

# Documentation
*.md
docs/
LICENSE

# Docker
Dockerfile*
docker-compose*.yml
.dockerignore

# Environment files (use build args instead)
.env*
!.env.example

# Logs
logs/
*.log

# OS files
.DS_Store
Thumbs.db

# Misc
.editorconfig
.prettierrc
.eslintrc*
```

**Container Structure Tests:**

```yaml
# container-structure-test.yaml
schemaVersion: 2.0.0

commandTests:
  - name: "Node version check"
    command: "node"
    args: ["--version"]
    expectedOutput: ["v18.*"]

  - name: "Application exists"
    command: "ls"
    args: ["/app/dist/main.js"]
    exitCode: 0

  - name: "Non-root user"
    command: "whoami"
    expectedOutput: ["nonroot"]
    excludedOutput: ["root"]

fileExistenceTests:
  - name: "Application files"
    path: "/app/dist/main.js"
    shouldExist: true

  - name: "Node modules"
    path: "/app/node_modules"
    shouldExist: true

  - name: "No source files"
    path: "/app/src"
    shouldExist: false

  - name: "No build tools"
    path: "/usr/bin/gcc"
    shouldExist: false

metadataTest:
  env:
    - key: NODE_ENV
      value: production
    - key: PORT
      value: "8080"

  exposedPorts: ["8080"]

  workdir: "/app"

  labels:
    - key: "maintainer"
      value: "devops@example.com"
```

### 🎯 Key Concepts to Assess

- [ ] **Multi-Stage Benefits**: Understanding layer caching and image size optimization
- [ ] **Security**: Non-root users, distroless images, vulnerability scanning
- [ ] **Build Optimization**: Proper layer ordering and caching strategies
- [ ] **Production Readiness**: Health checks, proper init systems, signal handling

### 🔄 Follow-up Questions

1. **Why use distroless images?**
   - Minimal attack surface, smaller size, no shell/package manager, only runtime dependencies

2. **How does Docker layer caching work?**
   - Docker caches each layer; if a layer hasn't changed, it reuses the cached version

3. **What's the purpose of .dockerignore?**
   - Excludes files from build context, reducing build time and image size

---

## Question 402: Container Security Scanning and Best Practices

**Difficulty**: Senior  
**Topic**: Container Security  
**Tags**: `docker`, `security`, `scanning`, `compliance`

### 📋 Question

Implement a comprehensive container security strategy including vulnerability scanning, image signing, runtime security, and compliance checks.

#### Scenario

Your organization requires:
- Automated vulnerability scanning in CI/CD
- Image signing and verification
- Runtime security monitoring
- Compliance with CIS Docker Benchmark
- Secrets management
- Network security policies
- Supply chain security

#### Requirements

- Integrate multiple security scanners (Trivy, Clair, Anchore)
- Implement image signing with Sigstore/Cosign
- Set up Falco for runtime security
- Create security policies
- Scan for secrets and misconfigurations
- Generate SBOMs

### ✅ Sample Solution

```yaml
# Security scanning pipeline
# .github/workflows/security-scan.yml

name: Container Security Scan

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]
  schedule:
    - cron: '0 0 * * *'  # Daily scan

env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}

jobs:
  build-and-scan:
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write
      security-events: write
      id-token: write  # For Cosign
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v3
      
      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v2
      
      - name: Log in to Container Registry
        uses: docker/login-action@v2
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}
      
      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@v4
        with:
          images: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}
          tags: |
            type=ref,event=branch
            type=ref,event=pr
            type=semver,pattern={{version}}
            type=semver,pattern={{major}}.{{minor}}
            type=sha
      
      - name: Build image
        uses: docker/build-push-action@v4
        with:
          context: .
          load: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
      
      # Vulnerability Scanning with Trivy
      - name: Run Trivy vulnerability scanner
        uses: aquasecurity/trivy-action@master
        with:
          image-ref: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:sha-${{ github.sha }}
          format: 'sarif'
          output: 'trivy-results.sarif'
          severity: 'CRITICAL,HIGH'
          exit-code: '1'
      
      - name: Upload Trivy results to GitHub Security
        uses: github/codeql-action/upload-sarif@v2
        if: always()
        with:
          sarif_file: 'trivy-results.sarif'
      
      # Comprehensive Trivy scan
      - name: Run comprehensive Trivy scan
        uses: aquasecurity/trivy-action@master
        with:
          image-ref: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:sha-${{ github.sha }}
          format: 'json'
          output: 'trivy-report.json'
          scanners: 'vuln,secret,config'
          timeout: '10m'
      
      # Secret scanning
      - name: Run Trivy secret scan
        uses: aquasecurity/trivy-action@master
        with:
          scan-type: 'fs'
          scan-ref: '.'
          format: 'sarif'
          output: 'trivy-secret-results.sarif'
          scanners: 'secret'
          exit-code: '1'
      
      # Misconfiguration scanning
      - name: Run Trivy config scan
        uses: aquasecurity/trivy-action@master
        with:
          scan-type: 'config'
          scan-ref: '.'
          format: 'sarif'
          output: 'trivy-config-results.sarif'
          exit-code: '0'
      
      # Anchore scanning
      - name: Run Anchore vulnerability scan
        uses: anchore/scan-action@v3
        with:
          image: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:sha-${{ github.sha }}
          fail-build: true
          severity-cutoff: high
          acs-report-enable: true
      
      - name: Upload Anchore scan results
        uses: github/codeql-action/upload-sarif@v2
        if: always()
        with:
          sarif_file: results.sarif
      
      # Generate SBOM
      - name: Generate SBOM with Syft
        uses: anchore/sbom-action@v0
        with:
          image: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:sha-${{ github.sha }}
          format: spdx-json
          output-file: sbom.spdx.json
      
      - name: Upload SBOM
        uses: actions/upload-artifact@v3
        with:
          name: sbom
          path: sbom.spdx.json
      
      # Snyk scanning
      - name: Run Snyk to check Docker image
        uses: snyk/actions/docker@master
        env:
          SNYK_TOKEN: ${{ secrets.SNYK_TOKEN }}
        with:
          image: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:sha-${{ github.sha }}
          args: --severity-threshold=high
      
      # Install Cosign for image signing
      - name: Install Cosign
        uses: sigstore/cosign-installer@v3
      
      # Push image if all scans pass
      - name: Push image to registry
        if: github.event_name != 'pull_request'
        uses: docker/build-push-action@v4
        with:
          context: .
          push: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
      
      # Sign image with Cosign (keyless)
      - name: Sign image with Cosign
        if: github.event_name != 'pull_request'
        env:
          COSIGN_EXPERIMENTAL: "true"
        run: |
          cosign sign --yes ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}@${{ steps.push.outputs.digest }}
      
      # Attest SBOM
      - name: Attest SBOM
        if: github.event_name != 'pull_request'
        env:
          COSIGN_EXPERIMENTAL: "true"
        run: |
          cosign attest --yes \
            --predicate sbom.spdx.json \
            --type spdxjson \
            ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}@${{ steps.push.outputs.digest }}
      
      # CIS Docker Benchmark
      - name: Run Docker Bench Security
        run: |
          docker run --rm \
            --net host \
            --pid host \
            --userns host \
            --cap-add audit_control \
            -v /etc:/etc:ro \
            -v /usr/bin/containerd:/usr/bin/containerd:ro \
            -v /usr/bin/runc:/usr/bin/runc:ro \
            -v /usr/lib/systemd:/usr/lib/systemd:ro \
            -v /var/lib:/var/lib:ro \
            -v /var/run/docker.sock:/var/run/docker.sock:ro \
            docker/docker-bench-security
```

**Runtime Security with Falco:**

```yaml
# falco-rules.yaml
- rule: Unexpected Network Activity
  desc: Detect network activity from container
  condition: >
    container.id != host and
    evt.type = connect and
    not fd.sip in (allowed_ips) and
    proc.name != known_processes
  output: >
    Unexpected network connection
    (user=%user.name command=%proc.cmdline
    container=%container.name
    ip=%fd.sip port=%fd.sport)
  priority: WARNING
  tags: [network, container]

- rule: Write below root
  desc: Attempt to write to root filesystem
  condition: >
    container.id != host and
    evt.type = open and
    evt.is_open_write = true and
    fd.name startswith / and
    not fd.name startswith /tmp and
    not fd.name startswith /dev and
    not fd.name startswith /proc
  output: >
    Write to root filesystem
    (user=%user.name command=%proc.cmdline
    file=%fd.name container=%container.name)
  priority: ERROR
  tags: [filesystem, container]

- rule: Unauthorized Process
  desc: Detect unauthorized process execution
  condition: >
    container.id != host and
    spawned_process and
    not proc.name in (allowed_processes)
  output: >
    Unauthorized process started
    (user=%user.name command=%proc.cmdline
    container=%container.name)
  priority: ERROR
  tags: [process, container]

- rule: Sensitive File Access
  desc: Detect access to sensitive files
  condition: >
    container.id != host and
    evt.type = open and
    (fd.name startswith /etc/shadow or
     fd.name startswith /etc/sudoers or
     fd.name contains passwd)
  output: >
    Sensitive file accessed
    (user=%user.name command=%proc.cmdline
    file=%fd.name container=%container.name)
  priority: CRITICAL
  tags: [filesystem, security]

- rule: Container Drift
  desc: Detect file modifications in container
  condition: >
    container.id != host and
    evt.type in (open, openat, openat2) and
    evt.is_open_write = true and
    fd.name startswith /usr/bin
  output: >
    Container drift detected
    (user=%user.name command=%proc.cmdline
    file=%fd.name container=%container.name)
  priority: ERROR
  tags: [integrity, container]
```

**Security Policy Configuration:**

```yaml
# opa-policy.rego
package kubernetes.admission

deny[msg] {
    input.request.kind.kind == "Pod"
    image := input.request.object.spec.containers[_].image
    not startswith(image, "your-registry.io/")
    msg := sprintf("Image '%v' comes from untrusted registry", [image])
}

deny[msg] {
    input.request.kind.kind == "Pod"
    container := input.request.object.spec.containers[_]
    not container.securityContext.runAsNonRoot == true
    msg := sprintf("Container '%v' must run as non-root", [container.name])
}

deny[msg] {
    input.request.kind.kind == "Pod"
    container := input.request.object.spec.containers[_]
    not container.securityContext.readOnlyRootFilesystem == true
    msg := sprintf("Container '%v' must have read-only root filesystem", [container.name])
}

deny[msg] {
    input.request.kind.kind == "Pod"
    container := input.request.object.spec.containers[_]
    container.securityContext.privileged == true
    msg := sprintf("Container '%v' cannot run in privileged mode", [container.name])
}

deny[msg] {
    input.request.kind.kind == "Pod"
    container := input.request.object.spec.containers[_]
    not container.resources.limits.cpu
    msg := sprintf("Container '%v' must have CPU limit", [container.name])
}

deny[msg] {
    input.request.kind.kind == "Pod"
    container := input.request.object.spec.containers[_]
    not container.resources.limits.memory
    msg := sprintf("Container '%v' must have memory limit", [container.name])
}

violation[{"msg": msg}] {
    input.request.kind.kind == "Pod"
    image := input.request.object.spec.containers[_].image
    not contains(image, "@sha256:")
    msg := sprintf("Image '%v' must use digest instead of tag", [image])
}
```

**Security Scanning Script:**

```bash
#!/bin/bash
# security-scan.sh

set -e

IMAGE=$1
if [ -z "$IMAGE" ]; then
    echo "Usage: $0 <image>"
    exit 1
fi

echo "=== Security Scanning for $IMAGE ==="

# 1. Trivy vulnerability scan
echo "Running Trivy scan..."
trivy image \
    --severity HIGH,CRITICAL \
    --format json \
    --output trivy-report.json \
    "$IMAGE"

# 2. Trivy secret scan
echo "Scanning for secrets..."
trivy image \
    --scanners secret \
    --format table \
    "$IMAGE"

# 3. Trivy misconfiguration scan
echo "Scanning for misconfigurations..."
trivy image \
    --scanners config \
    --format table \
    "$IMAGE"

# 4. Generate SBOM
echo "Generating SBOM..."
syft "$IMAGE" -o spdx-json=sbom.spdx.json
syft "$IMAGE" -o cyclonedx-json=sbom.cyclonedx.json

# 5. Grype vulnerability scan on SBOM
echo "Running Grype scan..."
grype sbom:sbom.spdx.json \
    --fail-on high \
    -o json \
    --file grype-report.json

# 6. Check image signature
echo "Verifying image signature..."
cosign verify "$IMAGE" --certificate-identity-regexp=".*" --certificate-oidc-issuer-regexp=".*" || echo "Warning: Image not signed"

# 7. Verify SBOM attestation
echo "Verifying SBOM attestation..."
cosign verify-attestation "$IMAGE" \
    --type spdxjson \
    --certificate-identity-regexp=".*" \
    --certificate-oidc-issuer-regexp=".*" || echo "Warning: No SBOM attestation found"

# 8. Dockle for best practices
echo "Running Dockle scan..."
dockle --exit-code 1 --exit-level warn "$IMAGE"

# 9. Check for known malware
echo "Scanning for malware..."
clamscan -r --infected --remove=no / 2>/dev/null || true

# Summary
echo "=== Security Scan Complete ==="
echo "Reports generated:"
echo "  - trivy-report.json"
echo "  - grype-report.json"
echo "  - sbom.spdx.json"
echo "  - sbom.cyclonedx.json"

# Exit with error if critical vulnerabilities found
CRITICAL_COUNT=$(jq '[.Results[].Vulnerabilities[]? | select(.Severity=="CRITICAL")] | length' trivy-report.json)
if [ "$CRITICAL_COUNT" -gt 0 ]; then
    echo "ERROR: Found $CRITICAL_COUNT critical vulnerabilities"
    exit 1
fi
```

### 🎯 Key Concepts to Assess

- [ ] **Vulnerability Management**: Multi-tool scanning approach and severity thresholds
- [ ] **Supply Chain Security**: Image signing, SBOM generation, and attestation
- [ ] **Runtime Security**: Falco rules for behavioral monitoring
- [ ] **Policy Enforcement**: OPA policies for admission control

---

*Questions 403-500 continue with topics including: Container orchestration alternatives, Docker Swarm, Podman and Buildah, containerd configuration, CRI-O, Container networking (bridge, host, overlay), Docker volumes and storage drivers, Docker networking deep dive, Container resource limits, cgroups and namespaces, Container escape techniques and prevention, Rootless containers, User namespaces, Docker content trust, Image provenance, Container registries (Harbor, Nexus, Artifactory), Registry mirroring and caching, Image promotion strategies, Container image optimization techniques, Layer squashing, Base image selection, FROM scratch images, Static binaries, Container debugging techniques, nsenter and docker exec, Ephemeral containers, Container logs aggregation, Docker logging drivers, syslog and json-file, Container monitoring with cAdvisor, Prometheus exporters for containers, Container lifecycle hooks, Docker healthchecks, Init containers patterns, Sidecar containers, DinD (Docker-in-Docker), Build cache optimization, Docker BuildKit features, Buildx for multi-arch builds, Cross-compilation, ARM64 images, Windows containers, Mixed OS clusters, and Container migration strategies.*

