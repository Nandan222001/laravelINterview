# CI/CD with Jenkins Questions (301-400)

## Question 301: Jenkins Declarative Pipeline for Kubernetes Deployment

**Difficulty**: Senior  
**Topic**: Jenkins CI/CD  
**Tags**: `jenkins`, `cicd`, `kubernetes`, `pipeline`

### 📋 Question

Create a comprehensive Jenkins declarative pipeline that builds a Docker image, runs security scans, executes tests, and deploys to Kubernetes with rollback capability, integrated with Jira for CMMI Level 5 traceability.

#### Scenario

Your organization requires:
- Automated build triggered by Git commits
- Docker image building with multi-stage builds
- Container security scanning (Trivy, Clair)
- Unit and integration tests
- Sonar quality gates
- Deployment to multiple Kubernetes environments (dev, staging, prod)
- Jira ticket updates at each stage
- Rollback on failure
- Slack notifications

#### Requirements

- Use declarative pipeline syntax
- Implement parallel stages where possible
- Add approval gates for production
- Store credentials securely
- Generate and archive test reports
- Implement blue-green or canary deployment
- Track deployment metrics

### ✅ Sample Solution

```groovy
// Jenkinsfile - Declarative Pipeline
@Library('shared-library') _

def DOCKER_REGISTRY = 'your-registry.io'
def APP_NAME = 'ecommerce-api'
def DOCKER_IMAGE = "${DOCKER_REGISTRY}/${APP_NAME}"
def GIT_COMMIT_SHORT = ''
def JIRA_ISSUE = ''
def DEPLOYMENT_ENV = ''

pipeline {
    agent {
        kubernetes {
            yaml """
apiVersion: v1
kind: Pod
metadata:
  labels:
    jenkins: agent
spec:
  serviceAccountName: jenkins
  containers:
  - name: docker
    image: docker:24-dind
    command:
    - cat
    tty: true
    volumeMounts:
    - name: docker-sock
      mountPath: /var/run/docker.sock
    securityContext:
      privileged: true
  - name: kubectl
    image: bitnami/kubectl:1.28
    command:
    - cat
    tty: true
  - name: helm
    image: alpine/helm:3.13
    command:
    - cat
    tty: true
  - name: node
    image: node:18-alpine
    command:
    - cat
    tty: true
  - name: trivy
    image: aquasec/trivy:latest
    command:
    - cat
    tty: true
  - name: sonar
    image: sonarsource/sonar-scanner-cli:latest
    command:
    - cat
    tty: true
  volumes:
  - name: docker-sock
    hostPath:
      path: /var/run/docker.sock
"""
        }
    }
    
    options {
        buildDiscarder(logRotator(numToKeepStr: '30', artifactNumToKeepStr: '10'))
        timeout(time: 1, unit: 'HOURS')
        timestamps()
        skipDefaultCheckout()
        disableConcurrentBuilds()
    }
    
    parameters {
        choice(
            name: 'ENVIRONMENT',
            choices: ['dev', 'staging', 'production'],
            description: 'Target deployment environment'
        )
        booleanParam(
            name: 'RUN_SECURITY_SCAN',
            defaultValue: true,
            description: 'Run security vulnerability scan'
        )
        booleanParam(
            name: 'SKIP_TESTS',
            defaultValue: false,
            description: 'Skip test execution'
        )
        string(
            name: 'JIRA_TICKET',
            defaultValue: '',
            description: 'Jira ticket number (e.g., PROJ-123)'
        )
    }
    
    environment {
        DOCKER_BUILDKIT = '1'
        COMPOSE_DOCKER_CLI_BUILD = '1'
        KUBECONFIG = credentials('kubeconfig-production')
        SONAR_TOKEN = credentials('sonarqube-token')
        DOCKER_REGISTRY_CREDS = credentials('docker-registry-credentials')
        JIRA_CREDENTIALS = credentials('jira-api-token')
        SLACK_WEBHOOK = credentials('slack-webhook-url')
        NPM_TOKEN = credentials('npm-token')
    }
    
    stages {
        stage('Initialize') {
            steps {
                script {
                    // Checkout code
                    checkout scm
                    
                    // Extract Jira issue from commit or parameter
                    GIT_COMMIT_SHORT = sh(
                        script: "git rev-parse --short HEAD",
                        returnStdout: true
                    ).trim()
                    
                    JIRA_ISSUE = params.JIRA_TICKET ?: sh(
                        script: "git log -1 --pretty=%B | grep -oE '[A-Z]+-[0-9]+' | head -1",
                        returnStdout: true
                    ).trim()
                    
                    DEPLOYMENT_ENV = params.ENVIRONMENT
                    
                    // Update Jira ticket
                    if (JIRA_ISSUE) {
                        jiraUpdateTicket(
                            issue: JIRA_ISSUE,
                            status: 'In Progress',
                            comment: "Build ${env.BUILD_NUMBER} started for commit ${GIT_COMMIT_SHORT}"
                        )
                    }
                    
                    // Send Slack notification
                    slackSend(
                        color: 'good',
                        message: "Build Started: ${env.JOB_NAME} #${env.BUILD_NUMBER}\nCommit: ${GIT_COMMIT_SHORT}\nJira: ${JIRA_ISSUE}\nEnvironment: ${DEPLOYMENT_ENV}",
                        channel: '#deployments'
                    )
                }
            }
        }
        
        stage('Code Quality & Security Analysis') {
            parallel {
                stage('SonarQube Analysis') {
                    steps {
                        container('sonar') {
                            script {
                                sh """
                                    sonar-scanner \
                                        -Dsonar.projectKey=${APP_NAME} \
                                        -Dsonar.sources=src \
                                        -Dsonar.host.url=${env.SONAR_HOST_URL} \
                                        -Dsonar.login=${env.SONAR_TOKEN} \
                                        -Dsonar.javascript.lcov.reportPaths=coverage/lcov.info \
                                        -Dsonar.exclusions='**/*.test.js,**/node_modules/**'
                                """
                            }
                        }
                    }
                }
                
                stage('Dependency Check') {
                    steps {
                        container('node') {
                            script {
                                sh '''
                                    npm audit --audit-level=high --json > npm-audit.json || true
                                    npm audit --audit-level=high
                                '''
                                archiveArtifacts artifacts: 'npm-audit.json', allowEmptyArchive: true
                            }
                        }
                    }
                }
                
                stage('Secret Scanning') {
                    steps {
                        container('trivy') {
                            script {
                                sh '''
                                    trivy fs --security-checks secret,config \
                                        --severity HIGH,CRITICAL \
                                        --format json \
                                        --output trivy-secret-scan.json \
                                        .
                                '''
                                archiveArtifacts artifacts: 'trivy-secret-scan.json'
                            }
                        }
                    }
                }
            }
        }
        
        stage('Quality Gate') {
            steps {
                script {
                    timeout(time: 5, unit: 'MINUTES') {
                        def qg = waitForQualityGate()
                        if (qg.status != 'OK') {
                            error "Pipeline aborted due to quality gate failure: ${qg.status}"
                        }
                    }
                }
            }
        }
        
        stage('Build & Test') {
            stages {
                stage('Install Dependencies') {
                    steps {
                        container('node') {
                            script {
                                sh '''
                                    echo "//registry.npmjs.org/:_authToken=${NPM_TOKEN}" > .npmrc
                                    npm ci --prefer-offline --no-audit
                                '''
                            }
                        }
                    }
                }
                
                stage('Run Tests') {
                    when {
                        expression { return !params.SKIP_TESTS }
                    }
                    parallel {
                        stage('Unit Tests') {
                            steps {
                                container('node') {
                                    script {
                                        sh '''
                                            npm run test:unit -- \
                                                --coverage \
                                                --ci \
                                                --reporters=default \
                                                --reporters=jest-junit
                                        '''
                                        junit 'junit.xml'
                                        publishCoverage adapters: [
                                            istanbulCoberturaAdapter('coverage/cobertura-coverage.xml')
                                        ]
                                    }
                                }
                            }
                        }
                        
                        stage('Integration Tests') {
                            steps {
                                container('node') {
                                    script {
                                        sh 'npm run test:integration'
                                    }
                                }
                            }
                        }
                        
                        stage('E2E Tests') {
                            steps {
                                container('node') {
                                    script {
                                        sh 'npm run test:e2e'
                                    }
                                }
                            }
                        }
                    }
                }
                
                stage('Build Application') {
                    steps {
                        container('node') {
                            script {
                                sh 'npm run build'
                            }
                        }
                    }
                }
            }
        }
        
        stage('Build Docker Image') {
            steps {
                container('docker') {
                    script {
                        docker.withRegistry("https://${DOCKER_REGISTRY}", 'docker-registry-credentials') {
                            // Build image with multiple tags
                            def app = docker.build(
                                "${DOCKER_IMAGE}:${GIT_COMMIT_SHORT}",
                                "--build-arg BUILD_DATE=\$(date -u +'%Y-%m-%dT%H:%M:%SZ') " +
                                "--build-arg VCS_REF=${GIT_COMMIT_SHORT} " +
                                "--build-arg VERSION=${env.BUILD_NUMBER} " +
                                "--label org.opencontainers.image.created=\$(date -u +'%Y-%m-%dT%H:%M:%SZ') " +
                                "--label org.opencontainers.image.revision=${GIT_COMMIT_SHORT} " +
                                "--label org.opencontainers.image.version=${env.BUILD_NUMBER} " +
                                "--cache-from ${DOCKER_IMAGE}:latest " +
                                "."
                            )
                            
                            // Tag image
                            app.tag("${env.BUILD_NUMBER}")
                            app.tag("${DEPLOYMENT_ENV}")
                            
                            if (DEPLOYMENT_ENV == 'production') {
                                app.tag("latest")
                            }
                        }
                    }
                }
            }
        }
        
        stage('Security Scan') {
            when {
                expression { return params.RUN_SECURITY_SCAN }
            }
            parallel {
                stage('Trivy Image Scan') {
                    steps {
                        container('trivy') {
                            script {
                                sh """
                                    trivy image \
                                        --severity HIGH,CRITICAL \
                                        --format json \
                                        --output trivy-report.json \
                                        ${DOCKER_IMAGE}:${GIT_COMMIT_SHORT}
                                    
                                    trivy image \
                                        --severity HIGH,CRITICAL \
                                        --exit-code 1 \
                                        ${DOCKER_IMAGE}:${GIT_COMMIT_SHORT}
                                """
                                archiveArtifacts artifacts: 'trivy-report.json'
                            }
                        }
                    }
                }
                
                stage('Container Structure Test') {
                    steps {
                        container('docker') {
                            script {
                                sh """
                                    container-structure-test test \
                                        --image ${DOCKER_IMAGE}:${GIT_COMMIT_SHORT} \
                                        --config container-structure-test.yaml
                                """
                            }
                        }
                    }
                }
            }
        }
        
        stage('Push Docker Image') {
            steps {
                container('docker') {
                    script {
                        docker.withRegistry("https://${DOCKER_REGISTRY}", 'docker-registry-credentials') {
                            sh """
                                docker push ${DOCKER_IMAGE}:${GIT_COMMIT_SHORT}
                                docker push ${DOCKER_IMAGE}:${env.BUILD_NUMBER}
                                docker push ${DOCKER_IMAGE}:${DEPLOYMENT_ENV}
                            """
                            
                            if (DEPLOYMENT_ENV == 'production') {
                                sh "docker push ${DOCKER_IMAGE}:latest"
                            }
                        }
                    }
                }
            }
        }
        
        stage('Deploy to Kubernetes') {
            stages {
                stage('Approval') {
                    when {
                        expression { return DEPLOYMENT_ENV == 'production' }
                    }
                    steps {
                        script {
                            timeout(time: 15, unit: 'MINUTES') {
                                input(
                                    message: "Deploy to production?",
                                    ok: "Deploy",
                                    submitter: "admin,release-manager"
                                )
                            }
                        }
                    }
                }
                
                stage('Backup Current State') {
                    steps {
                        container('kubectl') {
                            script {
                                sh """
                                    kubectl get deployment ${APP_NAME} -n ${DEPLOYMENT_ENV} -o yaml > backup-deployment.yaml || true
                                    kubectl get service ${APP_NAME} -n ${DEPLOYMENT_ENV} -o yaml > backup-service.yaml || true
                                """
                                archiveArtifacts artifacts: 'backup-*.yaml', allowEmptyArchive: true
                            }
                        }
                    }
                }
                
                stage('Deploy with Helm') {
                    steps {
                        container('helm') {
                            script {
                                sh """
                                    helm upgrade --install ${APP_NAME} ./helm/${APP_NAME} \
                                        --namespace ${DEPLOYMENT_ENV} \
                                        --create-namespace \
                                        --set image.repository=${DOCKER_IMAGE} \
                                        --set image.tag=${GIT_COMMIT_SHORT} \
                                        --set environment=${DEPLOYMENT_ENV} \
                                        --set replicaCount=3 \
                                        --set ingress.enabled=true \
                                        --set ingress.host=${APP_NAME}-${DEPLOYMENT_ENV}.example.com \
                                        --set monitoring.enabled=true \
                                        --set autoscaling.enabled=true \
                                        --set autoscaling.minReplicas=3 \
                                        --set autoscaling.maxReplicas=10 \
                                        --values helm/${APP_NAME}/values-${DEPLOYMENT_ENV}.yaml \
                                        --wait \
                                        --timeout 10m \
                                        --atomic
                                """
                            }
                        }
                    }
                }
                
                stage('Verify Deployment') {
                    steps {
                        container('kubectl') {
                            script {
                                sh """
                                    kubectl rollout status deployment/${APP_NAME} -n ${DEPLOYMENT_ENV} --timeout=5m
                                    kubectl get pods -n ${DEPLOYMENT_ENV} -l app=${APP_NAME}
                                    kubectl get service ${APP_NAME} -n ${DEPLOYMENT_ENV}
                                """
                                
                                // Health check
                                def healthCheckUrl = sh(
                                    script: "kubectl get ingress ${APP_NAME} -n ${DEPLOYMENT_ENV} -o jsonpath='{.spec.rules[0].host}'",
                                    returnStdout: true
                                ).trim()
                                
                                if (healthCheckUrl) {
                                    retry(5) {
                                        sh "curl -f https://${healthCheckUrl}/health || sleep 10"
                                    }
                                }
                            }
                        }
                    }
                }
                
                stage('Smoke Tests') {
                    steps {
                        container('node') {
                            script {
                                sh """
                                    export API_URL=https://${APP_NAME}-${DEPLOYMENT_ENV}.example.com
                                    npm run test:smoke
                                """
                            }
                        }
                    }
                }
            }
        }
    }
    
    post {
        success {
            script {
                // Update Jira
                if (JIRA_ISSUE) {
                    jiraUpdateTicket(
                        issue: JIRA_ISSUE,
                        status: 'Done',
                        comment: "Successfully deployed build ${env.BUILD_NUMBER} to ${DEPLOYMENT_ENV}\nImage: ${DOCKER_IMAGE}:${GIT_COMMIT_SHORT}"
                    )
                    jiraAddComment(
                        issue: JIRA_ISSUE,
                        comment: "Deployment URL: https://${APP_NAME}-${DEPLOYMENT_ENV}.example.com"
                    )
                }
                
                // Slack notification
                slackSend(
                    color: 'good',
                    message: "✅ Deployment Successful!\nJob: ${env.JOB_NAME} #${env.BUILD_NUMBER}\nEnvironment: ${DEPLOYMENT_ENV}\nCommit: ${GIT_COMMIT_SHORT}\nJira: ${JIRA_ISSUE}",
                    channel: '#deployments'
                )
                
                // Clean workspace
                cleanWs()
            }
        }
        
        failure {
            script {
                // Rollback
                container('helm') {
                    sh """
                        helm rollback ${APP_NAME} -n ${DEPLOYMENT_ENV} || true
                    """
                }
                
                // Update Jira
                if (JIRA_ISSUE) {
                    jiraUpdateTicket(
                        issue: JIRA_ISSUE,
                        status: 'Failed',
                        comment: "Build ${env.BUILD_NUMBER} failed. Deployment rolled back.\nLog: ${env.BUILD_URL}console"
                    )
                }
                
                // Slack notification
                slackSend(
                    color: 'danger',
                    message: "❌ Deployment Failed!\nJob: ${env.JOB_NAME} #${env.BUILD_NUMBER}\nEnvironment: ${DEPLOYMENT_ENV}\nCommit: ${GIT_COMMIT_SHORT}\nJira: ${JIRA_ISSUE}\nLog: ${env.BUILD_URL}console",
                    channel: '#deployments'
                )
            }
        }
        
        unstable {
            script {
                slackSend(
                    color: 'warning',
                    message: "⚠️ Build Unstable: ${env.JOB_NAME} #${env.BUILD_NUMBER}\nLog: ${env.BUILD_URL}console",
                    channel: '#deployments'
                )
            }
        }
        
        always {
            script {
                // Archive artifacts
                archiveArtifacts artifacts: '**/test-results/**/*.xml', allowEmptyArchive: true
                archiveArtifacts artifacts: '**/coverage/**/*', allowEmptyArchive: true
                
                // Publish test results
                junit allowEmptyResults: true, testResults: '**/test-results/**/*.xml'
                
                // Clean up Docker images
                container('docker') {
                    sh 'docker system prune -af --volumes || true'
                }
            }
        }
    }
}

// Helper Functions
def jiraUpdateTicket(Map args) {
    httpRequest(
        url: "${env.JIRA_URL}/rest/api/2/issue/${args.issue}",
        httpMode: 'PUT',
        authentication: 'jira-api-token',
        contentType: 'APPLICATION_JSON',
        requestBody: """
        {
            "fields": {
                "status": {"name": "${args.status}"}
            }
        }
        """
    )
    jiraAddComment(issue: args.issue, comment: args.comment)
}

def jiraAddComment(Map args) {
    httpRequest(
        url: "${env.JIRA_URL}/rest/api/2/issue/${args.issue}/comment",
        httpMode: 'POST',
        authentication: 'jira-api-token',
        contentType: 'APPLICATION_JSON',
        requestBody: """
        {
            "body": "${args.comment}"
        }
        """
    )
}
```

**Jenkins Configuration as Code (JCasC):**

```yaml
# jenkins-casc.yaml
jenkins:
  systemMessage: "Jenkins configured automatically by JCasC"
  numExecutors: 0
  
  securityRealm:
    ldap:
      configurations:
        - server: ldap://ldap.example.com
          rootDN: dc=example,dc=com
          userSearchBase: ou=users
          groupSearchBase: ou=groups
  
  authorizationStrategy:
    roleBased:
      roles:
        global:
          - name: "admin"
            description: "Jenkins administrators"
            permissions:
              - "Overall/Administer"
            assignments:
              - "admin-group"
          - name: "developer"
            description: "Developers"
            permissions:
              - "Overall/Read"
              - "Job/Build"
              - "Job/Read"
              - "Job/Cancel"
            assignments:
              - "developer-group"

credentials:
  system:
    domainCredentials:
      - credentials:
          - usernamePassword:
              scope: GLOBAL
              id: "docker-registry-credentials"
              username: "${DOCKER_REGISTRY_USER}"
              password: "${DOCKER_REGISTRY_PASSWORD}"
              description: "Docker Registry Credentials"
          
          - string:
              scope: GLOBAL
              id: "sonarqube-token"
              secret: "${SONAR_TOKEN}"
              description: "SonarQube Token"
          
          - file:
              scope: GLOBAL
              id: "kubeconfig-production"
              fileName: "kubeconfig"
              secretBytes: "${KUBECONFIG_BASE64}"
          
          - string:
              scope: GLOBAL
              id: "slack-webhook-url"
              secret: "${SLACK_WEBHOOK}"
              description: "Slack Webhook URL"

unclassified:
  location:
    url: https://jenkins.example.com/
  
  slackNotifier:
    teamDomain: example
    tokenCredentialId: slack-token
  
  sonarGlobalConfiguration:
    installations:
      - name: "SonarQube"
        serverUrl: "https://sonar.example.com"
        credentialsId: "sonarqube-token"

tool:
  git:
    installations:
      - name: "Default"
        home: "git"
  
  dockerTool:
    installations:
      - name: "docker"
        properties:
          - installSource:
              installers:
                - fromDocker:
                    version: "latest"

jobs:
  - script: >
      multibranchPipelineJob('ecommerce-api') {
        branchSources {
          git {
            id('ecommerce-api')
            remote('https://github.com/example/ecommerce-api.git')
            credentialsId('github-credentials')
            includes('main develop feature/* release/*')
          }
        }
        orphanedItemStrategy {
          discardOldItems {
            numToKeep(20)
          }
        }
      }
```

### 🎯 Key Concepts to Assess

- [ ] **Declarative vs Scripted**: Understanding pipeline syntax differences
- [ ] **Kubernetes Integration**: Using Kubernetes plugin for dynamic agents
- [ ] **Security Scanning**: Multiple security tools integration
- [ ] **Jira Integration**: Automated ticket updates for traceability
- [ ] **Rollback Strategy**: Automatic rollback on deployment failure

### 🔄 Follow-up Questions

1. **What's the difference between declarative and scripted pipelines?**
   - Declarative is more structured with predefined sections; scripted offers more flexibility with Groovy code

2. **How do you handle secrets in Jenkins pipelines?**
   - Use Jenkins credentials plugin, avoid hardcoding, use credential bindings

3. **What's the purpose of the atomic flag in Helm upgrades?**
   - Automatically rolls back if deployment fails, ensures all-or-nothing deployment

---

*Questions 302-400 continue with topics including: Jenkins shared libraries, Jenkins Configuration as Code, Multi-branch pipelines, Jenkins X for cloud-native CI/CD, BlueOcean UI, Jenkins agents and executors, Distributed builds, Pipeline optimization, Caching strategies, Artifact management with Nexus/Artifactory, GitLab CI/CD pipelines, GitHub Actions workflows, Azure DevOps pipelines, CircleCI configuration, Travis CI, Drone CI, Tekton pipelines, Argo Workflows, Spinnaker for continuous delivery, Flux CD, ArgoCD application sets, Progressive delivery, Feature flags, A/B testing deployments, Shadow deployments, Traffic mirroring, Deployment strategies comparison, Testing in CI/CD (unit, integration, e2e), Performance testing in pipelines, Load testing automation, Chaos engineering in CI/CD, Policy as Code with Conftest, SBOM generation, Supply chain security, Sigstore/Cosign for signing, SLSA framework, Dependency tracking, License compliance, Build reproducibility, Zero-trust CI/CD, Pipeline security best practices, and Secrets rotation in CI/CD.*

