# CI/CD Pipelines with Complete Jenkinsfile Examples

This document contains 100 comprehensive questions covering CI/CD pipelines with Jenkins declarative pipelines, including complete Jenkinsfile examples for Laravel applications, Docker builds, and deployment automation.

## Questions 1-20: Jenkins Declarative Pipeline Fundamentals

### Question 1
**Q:** Create a basic declarative Jenkinsfile for Laravel application with stages: Checkout, Build, Test, Deploy.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Checkout') {
            steps {
                git branch: 'main', url: 'https://github.com/company/laravel-app.git'
            }
        }
        stage('Build') {
            steps {
                sh 'composer install --no-dev --optimize-autoloader'
            }
        }
        stage('Test') {
            steps {
                sh 'vendor/bin/phpunit'
            }
        }
        stage('Deploy') {
            steps {
                sh 'php artisan migrate --force'
                sh 'php artisan config:cache'
            }
        }
    }
}
```

### Question 2
**Q:** Implement Jenkins pipeline with environment variables and credentials.

**A:**
```groovy
pipeline {
    agent any
    environment {
        APP_ENV = 'production'
        APP_DEBUG = 'false'
        DB_CREDENTIALS = credentials('mysql-prod-credentials')
        AWS_CREDENTIALS = credentials('aws-deploy-credentials')
    }
    stages {
        stage('Deploy') {
            steps {
                sh '''
                    export DB_USERNAME=${DB_CREDENTIALS_USR}
                    export DB_PASSWORD=${DB_CREDENTIALS_PSW}
                    ./deploy.sh
                '''
            }
        }
    }
}
```

### Question 3
**Q:** Configure Jenkins pipeline with multiple agent labels for different stages.

**A:**
```groovy
pipeline {
    agent none
    stages {
        stage('Build') {
            agent { label 'docker' }
            steps {
                sh 'docker build -t app:${BUILD_NUMBER} .'
            }
        }
        stage('Test') {
            agent { label 'test-runner' }
            steps {
                sh 'composer test'
            }
        }
        stage('Deploy') {
            agent { label 'kubernetes' }
            steps {
                sh 'kubectl apply -f k8s/'
            }
        }
    }
}
```

### Question 4
**Q:** Implement post actions for cleanup, notifications, and artifact archiving.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Build') {
            steps {
                sh 'npm run build'
            }
        }
    }
    post {
        always {
            cleanWs()
        }
        success {
            archiveArtifacts artifacts: 'dist/**/*', fingerprint: true
            mail to: 'team@company.com', subject: 'Build Success', body: 'Build completed successfully'
        }
        failure {
            mail to: 'team@company.com', subject: 'Build Failed', body: 'Build failed. Check console output.'
        }
    }
}
```

### Question 5
**Q:** Configure Jenkins pipeline with parameters for dynamic builds.

**A:**
```groovy
pipeline {
    agent any
    parameters {
        choice(name: 'ENVIRONMENT', choices: ['dev', 'staging', 'production'], description: 'Deployment environment')
        string(name: 'VERSION', defaultValue: 'latest', description: 'Application version')
        booleanParam(name: 'RUN_TESTS', defaultValue: true, description: 'Run test suite')
    }
    stages {
        stage('Test') {
            when {
                expression { params.RUN_TESTS == true }
            }
            steps {
                sh 'npm test'
            }
        }
        stage('Deploy') {
            steps {
                sh \"./deploy.sh ${params.ENVIRONMENT} ${params.VERSION}\"
            }
        }
    }
}
```

### Question 6
**Q:** Implement Jenkins pipeline with parallel stages for faster builds.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Tests') {
            parallel {
                stage('Unit Tests') {
                    steps {
                        sh 'vendor/bin/phpunit --testsuite Unit'
                    }
                }
                stage('Integration Tests') {
                    steps {
                        sh 'vendor/bin/phpunit --testsuite Integration'
                    }
                }
                stage('Static Analysis') {
                    steps {
                        sh 'vendor/bin/phpstan analyse'
                    }
                }
            }
        }
    }
}
```

### Question 7
**Q:** Configure Jenkins pipeline with when conditions for conditional execution.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy to Prod') {
            when {
                allOf {
                    branch 'main'
                    environment name: 'DEPLOY_ENV', value: 'production'
                }
            }
            steps {
                sh 'kubectl apply -f k8s/production/'
            }
        }
        stage('Deploy to Dev') {
            when {
                not {
                    branch 'main'
                }
            }
            steps {
                sh 'kubectl apply -f k8s/dev/'
            }
        }
    }
}
```

### Question 8
**Q:** Implement Jenkins pipeline with input steps for manual approval.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Build') {
            steps {
                sh 'docker build -t app .'
            }
        }
        stage('Approval') {
            steps {
                input message: 'Deploy to production?', ok: 'Deploy', submitter: 'admin,devops'
            }
        }
        stage('Deploy') {
            steps {
                sh 'kubectl apply -f k8s/prod/'
            }
        }
    }
}
```

### Question 9
**Q:** Configure Jenkins pipeline with try-catch error handling.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy') {
            steps {
                script {
                    try {
                        sh 'kubectl apply -f deployment.yaml'
                        sh 'kubectl rollout status deployment/app'
                    } catch (Exception e) {
                        sh 'kubectl rollout undo deployment/app'
                        error('Deployment failed, rolled back')
                    }
                }
            }
        }
    }
}
```

### Question 10
**Q:** Implement Jenkins pipeline with Docker agent for isolated builds.

**A:**
```groovy
pipeline {
    agent {
        docker {
            image 'php:8.2-cli'
            args '-v $HOME/.composer:/tmp/.composer'
        }
    }
    stages {
        stage('Build') {
            steps {
                sh 'composer install'
                sh 'composer test'
            }
        }
    }
}
```

### Question 11
**Q:** Configure Jenkins shared library for reusable pipeline code.

**A:**
```groovy
@Library('my-shared-library') _

pipeline {
    agent any
    stages {
        stage('Build') {
            steps {
                buildLaravelApp()
            }
        }
        stage('Deploy') {
            steps {
                deployToKubernetes(environment: 'production')
            }
        }
    }
}

// In shared library vars/buildLaravelApp.groovy:
def call() {
    sh 'composer install'
    sh 'npm run build'
}
```

### Question 12
**Q:** Implement Jenkins pipeline with stash/unstash for artifact passing.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Build') {
            steps {
                sh 'npm run build'
                stash includes: 'dist/**/*', name: 'built-app'
            }
        }
        stage('Deploy') {
            agent { label 'deploy-agent' }
            steps {
                unstash 'built-app'
                sh 'rsync -avz dist/ server:/var/www/'
            }
        }
    }
}
```

### Question 13
**Q:** Configure Jenkins pipeline with timeout for long-running stages.

**A:**
```groovy
pipeline {
    agent any
    options {
        timeout(time: 1, unit: 'HOURS')
    }
    stages {
        stage('Build') {
            options {
                timeout(time: 10, unit: 'MINUTES')
            }
            steps {
                sh 'composer install'
            }
        }
        stage('Test') {
            options {
                timeout(time: 30, unit: 'MINUTES')
            }
            steps {
                sh 'vendor/bin/phpunit'
            }
        }
    }
}
```

### Question 14
**Q:** Implement Jenkins pipeline with retry for flaky tests.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Test') {
            steps {
                retry(3) {
                    sh 'vendor/bin/phpunit'
                }
            }
        }
    }
}
```

### Question 15
**Q:** Configure Jenkins pipeline with build triggers (SCM polling, webhooks).

**A:**
```groovy
pipeline {
    agent any
    triggers {
        pollSCM('H/5 * * * *')  // Poll every 5 minutes
        cron('H 2 * * *')       // Run daily at 2 AM
    }
    stages {
        stage('Build') {
            steps {
                sh 'make build'
            }
        }
    }
}
```

### Question 16
**Q:** Implement Jenkins pipeline with build discarder for cleanup.

**A:**
```groovy
pipeline {
    agent any
    options {
        buildDiscarder(logRotator(numToKeepStr: '10', artifactNumToKeepStr: '5'))
    }
    stages {
        stage('Build') {
            steps {
                sh 'npm run build'
            }
        }
    }
}
```

### Question 17
**Q:** Configure Jenkins pipeline with timestamps in console output.

**A:**
```groovy
pipeline {
    agent any
    options {
        timestamps()
        ansiColor('xterm')
    }
    stages {
        stage('Build') {
            steps {
                sh 'echo \"Building at $(date)\"'
            }
        }
    }
}
```

### Question 18
**Q:** Implement Jenkins pipeline with custom workspace.

**A:**
```groovy
pipeline {
    agent {
        node {
            label 'linux'
            customWorkspace '/var/jenkins/custom-workspace'
        }
    }
    stages {
        stage('Build') {
            steps {
                sh 'pwd'
                sh 'make build'
            }
        }
    }
}
```

### Question 19
**Q:** Configure Jenkins pipeline with skipDefaultCheckout option.

**A:**
```groovy
pipeline {
    agent any
    options {
        skipDefaultCheckout(true)
    }
    stages {
        stage('Custom Checkout') {
            steps {
                checkout([
                    $class: 'GitSCM',
                    branches: [[name: 'main']],
                    userRemoteConfigs: [[url: 'https://github.com/repo.git']],
                    extensions: [[$class: 'CloneOption', depth: 1, shallow: true]]
                ])
            }
        }
    }
}
```

### Question 20
**Q:** Implement Jenkins pipeline with multiple SCM checkouts.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Checkout') {
            steps {
                dir('app') {
                    git url: 'https://github.com/company/app.git'
                }
                dir('config') {
                    git url: 'https://github.com/company/config.git'
                }
            }
        }
    }
}
```

## Questions 21-40: Laravel-Specific Pipelines

### Question 21
**Q:** Create complete Laravel CI/CD pipeline with testing and deployment.

**A:**
```groovy
pipeline {
    agent any
    environment {
        APP_KEY = credentials('laravel-app-key')
    }
    stages {
        stage('Install Dependencies') {
            steps {
                sh 'composer install --no-dev --prefer-dist'
                sh 'npm ci'
            }
        }
        stage('Prepare Environment') {
            steps {
                sh 'cp .env.example .env'
                sh 'php artisan key:generate'
            }
        }
        stage('Run Tests') {
            parallel {
                stage('PHPUnit') {
                    steps {
                        sh 'vendor/bin/phpunit'
                    }
                }
                stage('Laravel Dusk') {
                    steps {
                        sh 'php artisan dusk'
                    }
                }
            }
        }
        stage('Build Assets') {
            steps {
                sh 'npm run production'
            }
        }
        stage('Deploy') {
            steps {
                sh 'php artisan down'
                sh 'rsync -avz . server:/var/www/app'
                sh 'ssh server \"cd /var/www/app && php artisan migrate --force\"'
                sh 'ssh server \"cd /var/www/app && php artisan config:cache\"'
                sh 'ssh server \"cd /var/www/app && php artisan route:cache\"'
                sh 'ssh server \"cd /var/www/app && php artisan view:cache\"'
                sh 'php artisan up'
            }
        }
    }
}
```

### Question 22
**Q:** Implement Laravel pipeline with database migrations and seeders.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Run Migrations') {
            steps {
                sh 'php artisan migrate --force'
            }
        }
        stage('Seed Database') {
            when {
                environment name: 'ENV', value: 'staging'
            }
            steps {
                sh 'php artisan db:seed --force'
            }
        }
        stage('Fresh Install') {
            when {
                environment name: 'FRESH_INSTALL', value: 'true'
            }
            steps {
                sh 'php artisan migrate:fresh --seed --force'
            }
        }
    }
}
```

### Question 23
**Q:** Configure Laravel pipeline with code quality checks (PHPStan, PHP CS Fixer).

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Code Quality') {
            parallel {
                stage('PHPStan') {
                    steps {
                        sh 'vendor/bin/phpstan analyse --memory-limit=2G'
                    }
                }
                stage('PHP CS Fixer') {
                    steps {
                        sh 'vendor/bin/php-cs-fixer fix --dry-run --diff'
                    }
                }
                stage('PHPUnit Coverage') {
                    steps {
                        sh 'vendor/bin/phpunit --coverage-html coverage'
                        publishHTML target: [
                            allowMissing: false,
                            alwaysLinkToLastBuild: true,
                            keepAll: true,
                            reportDir: 'coverage',
                            reportFiles: 'index.html',
                            reportName: 'Coverage Report'
                        ]
                    }
                }
            }
        }
    }
}
```

### Question 24
**Q:** Implement Laravel queue worker deployment pipeline.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy Application') {
            steps {
                sh 'rsync -avz . server:/var/www/app'
            }
        }
        stage('Restart Queue Workers') {
            steps {
                sh 'ssh server \"php /var/www/app/artisan queue:restart\"'
                sh 'ssh server \"sudo supervisorctl restart laravel-worker:*\"'
            }
        }
        stage('Verify Workers') {
            steps {
                sh 'ssh server \"sudo supervisorctl status laravel-worker:*\"'
            }
        }
    }
}
```

### Question 25
**Q:** Configure Laravel pipeline with Redis cache clearing.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Clear Caches') {
            steps {
                sh 'php artisan cache:clear'
                sh 'php artisan config:clear'
                sh 'php artisan route:clear'
                sh 'php artisan view:clear'
                sh 'redis-cli FLUSHDB'
            }
        }
        stage('Optimize') {
            steps {
                sh 'php artisan config:cache'
                sh 'php artisan route:cache'
                sh 'php artisan view:cache'
            }
        }
    }
}
```

### Question 26
**Q:** Implement Laravel Horizon deployment with supervisor configuration.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy Horizon') {
            steps {
                sh 'php artisan horizon:terminate'
                sh 'sudo supervisorctl stop laravel-horizon'
                sh 'composer install --no-dev'
                sh 'php artisan migrate --force'
                sh 'sudo supervisorctl start laravel-horizon'
            }
        }
        stage('Verify Horizon') {
            steps {
                sh 'php artisan horizon:status'
                sh 'sudo supervisorctl status laravel-horizon'
            }
        }
    }
}
```

### Question 27
**Q:** Configure Laravel Octane deployment pipeline.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy Octane') {
            steps {
                sh 'php artisan octane:stop'
                sh 'composer install --no-dev'
                sh 'php artisan config:cache'
                sh 'php artisan route:cache'
                sh 'php artisan octane:start --server=swoole --host=0.0.0.0 --port=8000'
            }
        }
        stage('Health Check') {
            steps {
                sh 'curl -f http://localhost:8000/health || exit 1'
            }
        }
    }
}
```

### Question 28
**Q:** Implement Laravel Nova deployment with asset publishing.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Install Nova') {
            steps {
                sh 'composer install --no-dev'
                sh 'php artisan nova:install'
                sh 'php artisan nova:publish'
            }
        }
        stage('Build Nova Assets') {
            steps {
                sh 'npm run production'
            }
        }
        stage('Clear Nova Cache') {
            steps {
                sh 'php artisan nova:cache-clear'
            }
        }
    }
}
```

### Question 29
**Q:** Configure Laravel Telescope deployment for production.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Install Telescope') {
            when {
                environment name: 'INSTALL_TELESCOPE', value: 'true'
            }
            steps {
                sh 'php artisan telescope:install'
                sh 'php artisan migrate --force'
            }
        }
        stage('Prune Telescope') {
            steps {
                sh 'php artisan telescope:prune --hours=48'
            }
        }
    }
}
```

### Question 30
**Q:** Implement Laravel Sanctum API token deployment.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Publish Sanctum') {
            steps {
                sh 'php artisan vendor:publish --provider=\"Laravel\\\\Sanctum\\\\SanctumServiceProvider\"'
                sh 'php artisan migrate --force'
            }
        }
        stage('Generate API Tokens') {
            when {
                environment name: 'GENERATE_TOKENS', value: 'true'
            }
            steps {
                sh 'php artisan tinker --execute=\"User::first()->createToken(\\'api-token\\').plainTextToken\"'
            }
        }
    }
}
```

### Question 31
**Q:** Configure Laravel Passport OAuth deployment.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Install Passport') {
            steps {
                sh 'php artisan passport:install --force'
            }
        }
        stage('Generate Keys') {
            steps {
                sh 'php artisan passport:keys --force'
            }
        }
        stage('Create Client') {
            steps {
                sh 'php artisan passport:client --personal'
            }
        }
    }
}
```

### Question 32
**Q:** Implement Laravel Livewire deployment with asset compilation.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Publish Livewire') {
            steps {
                sh 'php artisan livewire:publish --assets'
            }
        }
        stage('Build Livewire Assets') {
            steps {
                sh 'npm run production'
            }
        }
        stage('Clear Livewire Cache') {
            steps {
                sh 'php artisan livewire:delete-uploaded-files'
            }
        }
    }
}
```

### Question 33
**Q:** Configure Laravel Breeze/Jetstream deployment.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Install Auth Scaffold') {
            when {
                environment name: 'FRESH_INSTALL', value: 'true'
            }
            steps {
                sh 'php artisan jetstream:install inertia'
            }
        }
        stage('Build Auth Assets') {
            steps {
                sh 'npm install'
                sh 'npm run production'
            }
        }
        stage('Migrate Auth Tables') {
            steps {
                sh 'php artisan migrate --force'
            }
        }
    }
}
```

### Question 34
**Q:** Implement Laravel Vapor deployment pipeline.

**A:**
```groovy
pipeline {
    agent any
    environment {
        VAPOR_API_TOKEN = credentials('vapor-api-token')
    }
    stages {
        stage('Deploy to Vapor') {
            steps {
                sh 'composer install --no-dev'
                sh 'npm run production'
                sh 'vapor deploy production'
            }
        }
        stage('Run Vapor Commands') {
            steps {
                sh 'vapor command production \"migrate --force\"'
                sh 'vapor command production \"cache:clear\"'
            }
        }
    }
}
```

### Question 35
**Q:** Configure Laravel Forge deployment integration.

**A:**
```groovy
pipeline {
    agent any
    environment {
        FORGE_API_TOKEN = credentials('forge-api-token')
    }
    stages {
        stage('Trigger Forge Deployment') {
            steps {
                sh '''
                    curl -X POST https://forge.laravel.com/servers/${SERVER_ID}/sites/${SITE_ID}/deployment/deploy \\
                    -H \"Authorization: Bearer ${FORGE_API_TOKEN}\" \\
                    -H \"Accept: application/json\"
                '''
            }
        }
        stage('Wait for Deployment') {
            steps {
                sleep time: 2, unit: 'MINUTES'
            }
        }
    }
}
```

### Question 36
**Q:** Implement Laravel Envoyer deployment with zero downtime.

**A:**
```groovy
pipeline {
    agent any
    environment {
        ENVOYER_API_TOKEN = credentials('envoyer-api-token')
    }
    stages {
        stage('Deploy via Envoyer') {
            steps {
                sh '''
                    curl -X POST https://envoyer.io/api/deployments \\
                    -H \"Authorization: Bearer ${ENVOYER_API_TOKEN}\" \\
                    -H \"Content-Type: application/json\" \\
                    -d '{\"project_id\": \"${PROJECT_ID}\"}'
                '''
            }
        }
    }
}
```

### Question 37
**Q:** Configure Laravel Sail development container build.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Build Sail Container') {
            steps {
                sh './vendor/bin/sail build --no-cache'
            }
        }
        stage('Run Sail Tests') {
            steps {
                sh './vendor/bin/sail up -d'
                sh './vendor/bin/sail artisan test'
                sh './vendor/bin/sail down'
            }
        }
    }
}
```

### Question 38
**Q:** Implement Laravel Socialite provider deployment.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Configure Socialite') {
            steps {
                sh 'php artisan config:clear'
                sh 'php artisan cache:clear'
            }
        }
        stage('Test Socialite') {
            steps {
                sh 'vendor/bin/phpunit --filter SocialiteTest'
            }
        }
    }
}
```

### Question 39
**Q:** Configure Laravel Cashier subscription billing deployment.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Publish Cashier') {
            steps {
                sh 'php artisan vendor:publish --tag=\"cashier-migrations\"'
                sh 'php artisan migrate --force'
            }
        }
        stage('Sync Stripe Plans') {
            steps {
                sh 'php artisan cashier:webhook'
            }
        }
    }
}
```

### Question 40
**Q:** Implement Laravel Scout search index deployment.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Import Scout Indexes') {
            steps {
                sh 'php artisan scout:import \"App\\\\Models\\\\Product\"'
                sh 'php artisan scout:import \"App\\\\Models\\\\Article\"'
            }
        }
        stage('Flush Scout Indexes') {
            when {
                environment name: 'REBUILD_INDEX', value: 'true'
            }
            steps {
                sh 'php artisan scout:flush \"App\\\\Models\\\\Product\"'
                sh 'php artisan scout:import \"App\\\\Models\\\\Product\"'
            }
        }
    }
}
```

## Questions 41-60: Docker Multi-Stage Builds

### Question 41
**Q:** Create multi-stage Dockerfile for Laravel with optimized layers.

**A:**
```dockerfile
# Build stage
FROM php:8.2-cli AS builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist
COPY . .
RUN composer dump-autoload --optimize

# Node build stage
FROM node:18 AS node-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run production

# Production stage
FROM php:8.2-fpm-alpine
WORKDIR /var/www
RUN apk add --no-cache nginx supervisor
COPY --from=builder /app /var/www
COPY --from=node-builder /app/public /var/www/public
EXPOSE 80
CMD [\"supervisord\", \"-c\", \"/etc/supervisor/supervisord.conf\"]
```

### Question 42
**Q:** Implement Jenkinsfile for Docker multi-stage build and push.

**A:**
```groovy
pipeline {
    agent any
    environment {
        DOCKER_REGISTRY = 'registry.company.com'
        IMAGE_NAME = 'laravel-app'
        IMAGE_TAG = \"${BUILD_NUMBER}\"
    }
    stages {
        stage('Build Docker Image') {
            steps {
                sh \"docker build -t ${DOCKER_REGISTRY}/${IMAGE_NAME}:${IMAGE_TAG} .\"
                sh \"docker tag ${DOCKER_REGISTRY}/${IMAGE_NAME}:${IMAGE_TAG} ${DOCKER_REGISTRY}/${IMAGE_NAME}:latest\"
            }
        }
        stage('Push to Registry') {
            steps {
                withCredentials([usernamePassword(credentialsId: 'docker-registry', usernameVariable: 'USER', passwordVariable: 'PASS')]) {
                    sh \"echo ${PASS} | docker login -u ${USER} --password-stdin ${DOCKER_REGISTRY}\"
                    sh \"docker push ${DOCKER_REGISTRY}/${IMAGE_NAME}:${IMAGE_TAG}\"
                    sh \"docker push ${DOCKER_REGISTRY}/${IMAGE_NAME}:latest\"
                }
            }
        }
    }
}
```

### Question 43
**Q:** Configure Docker build with BuildKit and cache optimization.

**A:**
```groovy
pipeline {
    agent any
    environment {
        DOCKER_BUILDKIT = '1'
    }
    stages {
        stage('Build with Cache') {
            steps {
                sh '''
                    docker build \\
                    --cache-from ${REGISTRY}/app:cache \\
                    --build-arg BUILDKIT_INLINE_CACHE=1 \\
                    -t ${REGISTRY}/app:${BUILD_NUMBER} \\
                    -t ${REGISTRY}/app:cache \\
                    .
                '''
            }
        }
    }
}
```

### Question 44
**Q:** Implement Docker Compose deployment pipeline.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy with Docker Compose') {
            steps {
                sh 'docker-compose -f docker-compose.prod.yml pull'
                sh 'docker-compose -f docker-compose.prod.yml up -d'
            }
        }
        stage('Run Migrations') {
            steps {
                sh 'docker-compose exec -T app php artisan migrate --force'
            }
        }
        stage('Health Check') {
            steps {
                retry(5) {
                    sh 'curl -f http://localhost/health || exit 1'
                    sleep 5
                }
            }
        }
    }
}
```

### Question 45
**Q:** Configure Docker image security scanning with Trivy.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Build Image') {
            steps {
                sh 'docker build -t app:${BUILD_NUMBER} .'
            }
        }
        stage('Scan Image') {
            steps {
                sh 'trivy image --severity HIGH,CRITICAL app:${BUILD_NUMBER}'
            }
        }
        stage('Fail on Vulnerabilities') {
            steps {
                sh 'trivy image --exit-code 1 --severity CRITICAL app:${BUILD_NUMBER}'
            }
        }
    }
}
```

### Question 46
**Q:** Implement Docker layer caching for faster builds.

**A:**
```dockerfile
FROM php:8.2-fpm-alpine

# Cache dependencies layer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts

# Copy application last
COPY . .
RUN composer dump-autoload --optimize
```

```groovy
pipeline {
    agent any
    stages {
        stage('Build') {
            steps {
                sh 'docker build --cache-from app:cache -t app:${BUILD_NUMBER} .'
            }
        }
    }
}
```

### Question 47
**Q:** Configure Docker build with secrets management.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Build with Secrets') {
            steps {
                withCredentials([file(credentialsId: 'composer-auth', variable: 'COMPOSER_AUTH')]) {
                    sh '''
                        docker build \\
                        --secret id=composer,src=${COMPOSER_AUTH} \\
                        -t app:${BUILD_NUMBER} \\
                        .
                    '''
                }
            }
        }
    }
}
```

### Question 48
**Q:** Implement Docker image tagging strategy.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Tag Images') {
            steps {
                script {
                    def tags = [
                        \"${BUILD_NUMBER}\",
                        \"${GIT_COMMIT.take(7)}\",
                        \"${BRANCH_NAME}\",
                        \"latest\"
                    ]
                    tags.each { tag ->
                        sh \"docker tag app:${BUILD_NUMBER} ${REGISTRY}/app:${tag}\"
                        sh \"docker push ${REGISTRY}/app:${tag}\"
                    }
                }
            }
        }
    }
}
```

### Question 49
**Q:** Configure Docker build for multiple architectures.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Build Multi-Arch') {
            steps {
                sh '''
                    docker buildx create --use
                    docker buildx build \\
                    --platform linux/amd64,linux/arm64 \\
                    -t ${REGISTRY}/app:${BUILD_NUMBER} \\
                    --push \\
                    .
                '''
            }
        }
    }
}
```

### Question 50
**Q:** Implement Docker cleanup for disk space management.

**A:**
```groovy
pipeline {
    agent any
    post {
        always {
            sh 'docker system prune -af --volumes'
            sh 'docker builder prune -af'
        }
    }
}
```

### Question 51
**Q:** Configure Docker health checks in deployment.

**A:**
```dockerfile
FROM php:8.2-fpm-alpine
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \\
    CMD curl -f http://localhost/health || exit 1
```

### Question 52
**Q:** Implement Docker registry promotion (dev -> staging -> prod).

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Promote to Staging') {
            steps {
                sh 'docker pull ${REGISTRY}/app:dev-${BUILD_NUMBER}'
                sh 'docker tag ${REGISTRY}/app:dev-${BUILD_NUMBER} ${REGISTRY}/app:staging-${BUILD_NUMBER}'
                sh 'docker push ${REGISTRY}/app:staging-${BUILD_NUMBER}'
            }
        }
        stage('Promote to Prod') {
            when {
                branch 'main'
            }
            steps {
                input 'Promote to production?'
                sh 'docker tag ${REGISTRY}/app:staging-${BUILD_NUMBER} ${REGISTRY}/app:prod-${BUILD_NUMBER}'
                sh 'docker push ${REGISTRY}/app:prod-${BUILD_NUMBER}'
            }
        }
    }
}
```

### Question 53
**Q:** Configure distroless Docker images for security.

**A:**
```dockerfile
FROM php:8.2-cli AS builder
WORKDIR /app
COPY . .
RUN composer install --no-dev

FROM gcr.io/distroless/php8.2-fpm
COPY --from=builder /app /app
WORKDIR /app
CMD [\"php-fpm\"]
```

### Question 54
**Q:** Implement Docker image signing with Cosign.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Sign Image') {
            steps {
                sh 'docker push ${REGISTRY}/app:${BUILD_NUMBER}'
                sh 'cosign sign ${REGISTRY}/app:${BUILD_NUMBER}'
            }
        }
    }
}
```

### Question 55
**Q:** Configure Docker SBOM generation for compliance.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Generate SBOM') {
            steps {
                sh 'syft ${REGISTRY}/app:${BUILD_NUMBER} -o json > sbom.json'
                archiveArtifacts 'sbom.json'
            }
        }
    }
}
```

### Question 56
**Q:** Implement Docker content trust with Notary.

**A:**
```groovy
pipeline {
    agent any
    environment {
        DOCKER_CONTENT_TRUST = '1'
    }
    stages {
        stage('Push Signed Image') {
            steps {
                sh 'docker push ${REGISTRY}/app:${BUILD_NUMBER}'
            }
        }
    }
}
```

### Question 57
**Q:** Configure Docker build arguments for environment configuration.

**A:**
```dockerfile
FROM php:8.2-fpm-alpine
ARG APP_ENV=production
ARG APP_DEBUG=false
ENV APP_ENV=${APP_ENV}
ENV APP_DEBUG=${APP_DEBUG}
```

```groovy
pipeline {
    agent any
    stages {
        stage('Build') {
            steps {
                sh '''
                    docker build \\
                    --build-arg APP_ENV=production \\
                    --build-arg APP_DEBUG=false \\
                    -t app:${BUILD_NUMBER} \\
                    .
                '''
            }
        }
    }
}
```

### Question 58
**Q:** Implement Docker volume management for persistent data.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy with Volumes') {
            steps {
                sh '''
                    docker run -d \\
                    --name app \\
                    -v app-storage:/var/www/storage \\
                    -v app-logs:/var/www/storage/logs \\
                    app:${BUILD_NUMBER}
                '''
            }
        }
    }
}
```

### Question 59
**Q:** Configure Docker network isolation for microservices.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Create Network') {
            steps {
                sh 'docker network create --driver bridge app-network'
            }
        }
        stage('Deploy Services') {
            steps {
                sh 'docker run -d --network app-network --name web app:${BUILD_NUMBER}'
                sh 'docker run -d --network app-network --name db mysql:8'
            }
        }
    }
}
```

### Question 60
**Q:** Implement Docker resource limits for stability.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy with Limits') {
            steps {
                sh '''
                    docker run -d \\
                    --name app \\
                    --memory=2g \\
                    --memory-swap=2g \\
                    --cpus=2 \\
                    --restart=unless-stopped \\
                    app:${BUILD_NUMBER}
                '''
            }
        }
    }
}
```

## Questions 61-80: Advanced CI/CD Patterns

### Question 61
**Q:** Implement blue-green deployment with Jenkins.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy to Green') {
            steps {
                sh 'kubectl apply -f k8s/green-deployment.yaml'
                sh 'kubectl wait --for=condition=available deployment/app-green'
            }
        }
        stage('Test Green') {
            steps {
                sh 'curl -f http://green.internal/health'
            }
        }
        stage('Switch Traffic') {
            steps {
                input 'Switch to green?'
                sh 'kubectl patch service app -p \\'{\"spec\":{\"selector\":{\"version\":\"green\"}}}\\''
            }
        }
        stage('Delete Blue') {
            steps {
                sh 'kubectl delete deployment app-blue'
            }
        }
    }
}
```

### Question 62
**Q:** Configure canary deployment with gradual traffic shift.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy Canary') {
            steps {
                sh 'kubectl apply -f k8s/canary-deployment.yaml'
                sh 'kubectl patch service app -p \\'{\"spec\":{\"selector\":{\"track\":\"canary\"}}}\\''
            }
        }
        stage('10% Traffic') {
            steps {
                sh 'kubectl scale deployment app-canary --replicas=1'
                sh 'kubectl scale deployment app-stable --replicas=9'
                sleep time: 5, unit: 'MINUTES'
            }
        }
        stage('50% Traffic') {
            steps {
                sh 'kubectl scale deployment app-canary --replicas=5'
                sh 'kubectl scale deployment app-stable --replicas=5'
                sleep time: 5, unit: 'MINUTES'
            }
        }
        stage('100% Traffic') {
            steps {
                sh 'kubectl scale deployment app-canary --replicas=10'
                sh 'kubectl scale deployment app-stable --replicas=0'
            }
        }
    }
}
```

### Question 63
**Q:** Implement feature flag deployment pipeline.

**A:**
```groovy
pipeline {
    agent any
    parameters {
        booleanParam(name: 'ENABLE_NEW_FEATURE', defaultValue: false)
    }
    stages {
        stage('Deploy with Feature Flag') {
            steps {
                sh \"\"\"
                    kubectl set env deployment/app \\
                    FEATURE_NEW_UI=${params.ENABLE_NEW_FEATURE}
                \"\"\"
                sh 'kubectl rollout status deployment/app'
            }
        }
    }
}
```

### Question 64
**Q:** Configure smoke tests after deployment.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy') {
            steps {
                sh 'kubectl apply -f k8s/'
                sh 'kubectl wait --for=condition=available deployment/app'
            }
        }
        stage('Smoke Tests') {
            steps {
                script {
                    def tests = [
                        'Health Check': 'curl -f https://app.com/health',
                        'API Test': 'curl -f https://app.com/api/status',
                        'Database': 'kubectl exec deployment/app -- php artisan tinker --execute=\"DB::connection()->getPdo()\"'
                    ]
                    tests.each { name, command ->
                        echo \"Running: ${name}\"
                        sh command
                    }
                }
            }
        }
    }
}
```

### Question 65
**Q:** Implement rollback on failure with automatic detection.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy') {
            steps {
                script {
                    try {
                        sh 'kubectl apply -f k8s/'
                        sh 'kubectl rollout status deployment/app --timeout=5m'
                        sh 'curl -f https://app.com/health'
                    } catch (Exception e) {
                        echo 'Deployment failed, rolling back'
                        sh 'kubectl rollout undo deployment/app'
                        error('Deployment failed and rolled back')
                    }
                }
            }
        }
    }
}
```

### Question 66
**Q:** Configure database backup before deployment.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Backup Database') {
            steps {
                sh '''
                    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
                    kubectl exec deployment/mysql -- mysqldump -u root -p${DB_PASS} app > backup_${TIMESTAMP}.sql
                    aws s3 cp backup_${TIMESTAMP}.sql s3://backups/
                '''
            }
        }
        stage('Deploy') {
            steps {
                sh 'kubectl apply -f k8s/'
            }
        }
    }
}
```

### Question 67
**Q:** Implement deployment windows with scheduled builds.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Check Deployment Window') {
            steps {
                script {
                    def hour = new Date().format('HH') as Integer
                    if (hour < 9 || hour > 17) {
                        error('Deployment only allowed between 9 AM and 5 PM')
                    }
                }
            }
        }
        stage('Deploy') {
            steps {
                sh 'kubectl apply -f k8s/'
            }
        }
    }
}
```

### Question 68
**Q:** Configure multi-environment deployment pipeline.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy to Dev') {
            steps {
                sh 'kubectl apply -f k8s/dev/ --context=dev-cluster'
            }
        }
        stage('Deploy to Staging') {
            when {
                branch 'develop'
            }
            steps {
                sh 'kubectl apply -f k8s/staging/ --context=staging-cluster'
            }
        }
        stage('Deploy to Production') {
            when {
                branch 'main'
            }
            steps {
                input 'Deploy to production?'
                sh 'kubectl apply -f k8s/prod/ --context=prod-cluster'
            }
        }
    }
}
```

### Question 69
**Q:** Implement deployment metrics collection.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy') {
            steps {
                script {
                    def startTime = System.currentTimeMillis()
                    sh 'kubectl apply -f k8s/'
                    def duration = System.currentTimeMillis() - startTime
                    
                    sh \"\"\"
                        curl -X POST https://metrics.company.com/api/deployments \\
                        -d '{\"duration\": ${duration}, \"status\": \"success\", \"build\": \"${BUILD_NUMBER}\"}'
                    \"\"\"
                }
            }
        }
    }
}
```

### Question 70
**Q:** Configure deployment approval workflow.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Build') {
            steps {
                sh 'docker build -t app:${BUILD_NUMBER} .'
            }
        }
        stage('Security Approval') {
            steps {
                input message: 'Security scan passed?', submitter: 'security-team'
            }
        }
        stage('Manager Approval') {
            steps {
                input message: 'Approve production deployment?', submitter: 'managers'
            }
        }
        stage('Deploy') {
            steps {
                sh 'kubectl apply -f k8s/prod/'
            }
        }
    }
}
```

### Question 71
**Q:** Implement deployment notification system.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy') {
            steps {
                sh 'kubectl apply -f k8s/'
            }
        }
    }
    post {
        success {
            slackSend(
                color: 'good',
                message: \"Deployment successful: ${env.JOB_NAME} #${env.BUILD_NUMBER}\"
            )
            emailext(
                subject: \"Deployment Success: ${env.JOB_NAME}\",
                body: \"Build ${env.BUILD_NUMBER} deployed successfully\",
                to: 'team@company.com'
            )
        }
        failure {
            slackSend(
                color: 'danger',
                message: \"Deployment failed: ${env.JOB_NAME} #${env.BUILD_NUMBER}\"
            )
        }
    }
}
```

### Question 72
**Q:** Configure deployment rate limiting.

**A:**
```groovy
@Library('rate-limiter') _

pipeline {
    agent any
    stages {
        stage('Check Rate Limit') {
            steps {
                script {
                    if (!checkDeploymentRate(limit: 5, period: '1h')) {
                        error('Deployment rate limit exceeded')
                    }
                }
            }
        }
        stage('Deploy') {
            steps {
                sh 'kubectl apply -f k8s/'
            }
        }
    }
}
```

### Question 73
**Q:** Implement deployment chaos testing integration.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy') {
            steps {
                sh 'kubectl apply -f k8s/'
            }
        }
        stage('Chaos Testing') {
            when {
                environment name: 'RUN_CHAOS', value: 'true'
            }
            steps {
                sh 'kubectl apply -f chaos-mesh/pod-kill.yaml'
                sleep time: 5, unit: 'MINUTES'
                sh 'curl -f https://app.com/health'
            }
        }
    }
}
```

### Question 74
**Q:** Configure deployment cost estimation.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Estimate Cost') {
            steps {
                script {
                    sh 'infracost breakdown --path=k8s/ --format=json > cost.json'
                    def cost = readJSON file: 'cost.json'
                    if (cost.totalMonthlyCost > 10000) {
                        input \"Monthly cost: \\$${cost.totalMonthlyCost}. Continue?\"
                    }
                }
            }
        }
        stage('Deploy') {
            steps {
                sh 'kubectl apply -f k8s/'
            }
        }
    }
}
```

### Question 75
**Q:** Implement deployment dependency checking.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Check Dependencies') {
            steps {
                script {
                    def required = ['database', 'redis', 'elasticsearch']
                    required.each { service ->
                        def status = sh(
                            script: \"kubectl get service ${service} -o jsonpath='{.status}'\",
                            returnStatus: true
                        )
                        if (status != 0) {
                            error(\"Required service ${service} not found\")
                        }
                    }
                }
            }
        }
        stage('Deploy') {
            steps {
                sh 'kubectl apply -f k8s/'
            }
        }
    }
}
```

### Question 76
**Q:** Configure deployment with feature toggles.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy with Feature Flags') {
            steps {
                sh '''
                    kubectl create configmap feature-flags \\
                    --from-literal=new-ui=true \\
                    --from-literal=beta-api=false \\
                    --dry-run=client -o yaml | kubectl apply -f -
                '''
                sh 'kubectl rollout restart deployment/app'
            }
        }
    }
}
```

### Question 77
**Q:** Implement deployment progressive rollout.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy 25%') {
            steps {
                sh 'kubectl set image deployment/app app=app:${BUILD_NUMBER}'
                sh 'kubectl patch deployment app -p \\'{\"spec\":{\"strategy\":{\"type\":\"RollingUpdate\",\"rollingUpdate\":{\"maxSurge\":1,\"maxUnavailable\":0}}}}\\'  '
                sleep time: 5, unit: 'MINUTES'
            }
        }
        stage('Check Metrics') {
            steps {
                sh 'curl -f https://metrics.company.com/api/error-rate'
            }
        }
        stage('Deploy 100%') {
            steps {
                sh 'kubectl rollout status deployment/app'
            }
        }
    }
}
```

### Question 78
**Q:** Configure deployment with load testing.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Deploy') {
            steps {
                sh 'kubectl apply -f k8s/'
                sh 'kubectl wait --for=condition=available deployment/app'
            }
        }
        stage('Load Test') {
            steps {
                sh 'k6 run --vus 100 --duration 5m load-test.js'
            }
        }
    }
}
```

### Question 79
**Q:** Implement deployment audit logging.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Log Deployment') {
            steps {
                script {
                    def auditLog = [
                        timestamp: new Date().toString(),
                        user: env.BUILD_USER,
                        build: env.BUILD_NUMBER,
                        branch: env.BRANCH_NAME,
                        commit: env.GIT_COMMIT
                    ]
                    writeJSON file: 'audit.json', json: auditLog
                    sh 'aws s3 cp audit.json s3://audit-logs/'
                }
            }
        }
        stage('Deploy') {
            steps {
                sh 'kubectl apply -f k8s/'
            }
        }
    }
}
```

### Question 80
**Q:** Configure deployment validation checks.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Pre-Deploy Validation') {
            steps {
                sh 'kubectl apply --dry-run=server -f k8s/'
                sh 'kubeval k8s/*.yaml'
                sh 'kube-score score k8s/*.yaml'
            }
        }
        stage('Deploy') {
            steps {
                sh 'kubectl apply -f k8s/'
            }
        }
        stage('Post-Deploy Validation') {
            steps {
                sh 'kubectl get pods -l app=myapp'
                sh 'curl -f https://app.com/health'
            }
        }
    }
}
```

## Questions 81-100: CMMI Level 5 Compliance and Jira Integration

### Question 81
**Q:** Implement Jira integration for deployment tracking.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Update Jira') {
            steps {
                script {
                    def issueKey = env.BRANCH_NAME.tokenize('/')[1]
                    jiraTransitionIssue(
                        site: 'company.atlassian.net',
                        idOrKey: issueKey,
                        input: [transition: [id: '31']]  // In Progress
                    )
                }
            }
        }
        stage('Deploy') {
            steps {
                sh 'kubectl apply -f k8s/'
            }
        }
        stage('Complete Jira') {
            steps {
                script {
                    def issueKey = env.BRANCH_NAME.tokenize('/')[1]
                    jiraAddComment(
                        site: 'company.atlassian.net',
                        idOrKey: issueKey,
                        input: [body: \"Deployed in build ${env.BUILD_NUMBER}\"]
                    )
                    jiraTransitionIssue(
                        site: 'company.atlassian.net',
                        idOrKey: issueKey,
                        input: [transition: [id: '41']]  // Done
                    )
                }
            }
        }
    }
}
```

### Question 82
**Q:** Configure CMMI compliance metrics collection.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Collect Metrics') {
            steps {
                script {
                    def metrics = [
                        buildNumber: env.BUILD_NUMBER,
                        duration: currentBuild.duration,
                        testCoverage: sh(returnStdout: true, script: 'cat coverage/clover.xml | grep \\\"lines-covered\\\"'),
                        staticAnalysis: sh(returnStdout: true, script: 'cat phpstan-report.json'),
                        securityScan: sh(returnStdout: true, script: 'cat trivy-report.json')
                    ]
                    writeJSON file: 'cmmi-metrics.json', json: metrics
                    archiveArtifacts 'cmmi-metrics.json'
                }
            }
        }
    }
}
```

### Question 83
**Q:** Implement traceability matrix automation.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Generate Traceability') {
            steps {
                script {
                    def requirements = jiraJqlSearch(jql: 'project=PROJ AND type=Requirement')
                    def tests = sh(returnStdout: true, script: 'grep -r @requirement tests/')
                    
                    def matrix = [:]
                    requirements.each { req ->
                        matrix[req.key] = tests.findAll { it.contains(req.key) }
                    }
                    writeJSON file: 'traceability-matrix.json', json: matrix
                }
            }
        }
    }
}
```

### Question 84
**Q:** Configure change management workflow integration.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Check Change Request') {
            steps {
                script {
                    def changeRequest = input(
                        message: 'Enter Change Request ID',
                        parameters: [string(name: 'CR_ID')]
                    )
                    
                    def cr = jiraGetIssue(idOrKey: changeRequest)
                    if (cr.fields.status.name != 'Approved') {
                        error('Change Request not approved')
                    }
                }
            }
        }
        stage('Deploy') {
            steps {
                sh 'kubectl apply -f k8s/'
            }
        }
    }
}
```

### Question 85
**Q:** Implement defect density tracking.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Calculate Defect Density') {
            steps {
                script {
                    def loc = sh(returnStdout: true, script: 'cloc . --json').trim()
                    def bugs = jiraJqlSearch(jql: 'project=PROJ AND type=Bug AND created >= -30d')
                    
                    def density = bugs.size() / (loc.code / 1000)
                    echo \"Defect Density: ${density} bugs per KLOC\"
                    
                    if (density > 5) {
                        error('Defect density too high')
                    }
                }
            }
        }
    }
}
```

### Question 86
**Q:** Configure process compliance verification.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Verify Compliance') {
            steps {
                script {
                    def checks = [
                        'Code Review': { return env.CHANGE_ID != null },
                        'Tests Pass': { return currentBuild.result == 'SUCCESS' },
                        'Security Scan': { fileExists('trivy-report.json') },
                        'Documentation': { fileExists('CHANGELOG.md') }
                    ]
                    
                    checks.each { name, check ->
                        if (!check()) {
                            error(\"Compliance check failed: ${name}\")
                        }
                    }
                }
            }
        }
    }
}
```

### Question 87
**Q:** Implement continuous improvement metrics.

**A:**
```groovy
pipeline {
    agent any
    post {
        always {
            script {
                def metrics = [
                    deploymentFrequency: '1',
                    leadTime: currentBuild.duration / 1000 / 60,  // minutes
                    mttr: 0,  // Calculate from incident data
                    changeFailureRate: currentBuild.result == 'FAILURE' ? 1 : 0
                ]
                
                sh \"\"\"
                    curl -X POST https://metrics.company.com/api/dora \\
                    -d '${groovy.json.JsonOutput.toJson(metrics)}'
                \"\"\"
            }
        }
    }
}
```

### Question 88
**Q:** Configure peer review automation with Jira.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Request Review') {
            steps {
                script {
                    def reviewers = ['john.doe', 'jane.smith']
                    def issue = jiraNewIssue(
                        site: 'company.atlassian.net',
                        issue: [
                            fields: [
                                project: [key: 'REVIEW'],
                                summary: \"Code Review: ${env.JOB_NAME} #${env.BUILD_NUMBER}\",
                                issuetype: [name: 'Task'],
                                assignee: [name: reviewers[0]]
                            ]
                        ]
                    )
                    echo \"Review ticket: ${issue.key}\"
                }
            }
        }
    }
}
```

### Question 89
**Q:** Implement risk assessment automation.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Assess Risk') {
            steps {
                script {
                    def risk = [
                        complexity: sh(returnStdout: true, script: 'lizard -l php').contains('High'),
                        coverage: sh(returnStdout: true, script: 'cat coverage/clover.xml') < 80,
                        vulnerabilities: sh(returnStdout: true, script: 'cat trivy-report.json').contains('HIGH')
                    ]
                    
                    def riskLevel = risk.values().count { it } 
                    if (riskLevel > 1) {
                        input \"High risk deployment (${riskLevel}/3). Continue?\"
                    }
                }
            }
        }
    }
}
```

### Question 90
**Q:** Configure root cause analysis tracking.

**A:**
```groovy
pipeline {
    agent any
    post {
        failure {
            script {
                jiraNewIssue(
                    site: 'company.atlassian.net',
                    issue: [
                        fields: [
                            project: [key: 'RCA'],
                            summary: \"Build Failure: ${env.JOB_NAME} #${env.BUILD_NUMBER}\",
                            description: \"${env.BUILD_URL}console\",
                            issuetype: [name: 'Incident'],
                            priority: [name: 'High']
                        ]
                    ]
                )
            }
        }
    }
}
```

### Question 91
**Q:** Implement test effectiveness metrics.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Test Metrics') {
            steps {
                script {
                    def coverage = sh(returnStdout: true, script: 'cat coverage/clover.xml')
                    def mutations = sh(returnStdout: true, script: 'cat mutation-report.json')
                    
                    def metrics = [
                        lineCoverage: parseFloat(coverage),
                        mutationScore: parseFloat(mutations),
                        testCount: sh(returnStdout: true, script: 'grep -c \"test\" tests/')
                    ]
                    
                    writeJSON file: 'test-metrics.json', json: metrics
                }
            }
        }
    }
}
```

### Question 92
**Q:** Configure quality gates with Jira approval.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Quality Gate') {
            steps {
                script {
                    def coverage = 75.5  // From coverage report
                    def bugs = jiraJqlSearch(jql: 'project=PROJ AND type=Bug AND status=Open').size()
                    
                    if (coverage < 80 || bugs > 5) {
                        def approval = jiraNewIssue(
                            issue: [
                                fields: [
                                    summary: 'Quality Gate Override Required',
                                    description: \"Coverage: ${coverage}%, Open Bugs: ${bugs}\"
                                ]
                            ]
                        )
                        input \"Quality gate failed. See ${approval.key}. Continue?\"
                    }
                }
            }
        }
    }
}
```

### Question 93
**Q:** Implement release notes automation.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Generate Release Notes') {
            steps {
                script {
                    def issues = jiraJqlSearch(jql: \"project=PROJ AND fixVersion='${env.VERSION}'\")
                    def notes = \"# Release ${env.VERSION}\\n\\n\"
                    
                    ['Feature', 'Bug', 'Improvement'].each { type ->
                        notes += \"## ${type}s\\n\"
                        issues.findAll { it.fields.issuetype.name == type }.each {
                            notes += \"- [${it.key}] ${it.fields.summary}\\n\"
                        }
                        notes += \"\\n\"
                    }
                    
                    writeFile file: 'RELEASE_NOTES.md', text: notes
                }
            }
        }
    }
}
```

### Question 94
**Q:** Configure SLA compliance monitoring.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Check SLA') {
            steps {
                script {
                    def startTime = System.currentTimeMillis()
                    // Deploy
                    sh 'kubectl apply -f k8s/'
                    def duration = System.currentTimeMillis() - startTime
                    
                    def sla = 15 * 60 * 1000  // 15 minutes
                    if (duration > sla) {
                        jiraAddComment(
                            idOrKey: env.JIRA_TICKET,
                            comment: \"SLA violated: ${duration}ms > ${sla}ms\"
                        )
                    }
                }
            }
        }
    }
}
```

### Question 95
**Q:** Implement lessons learned documentation.

**A:**
```groovy
pipeline {
    agent any
    post {
        failure {
            script {
                def lessonsLearned = input(
                    message: 'Document lessons learned',
                    parameters: [text(name: 'LESSONS', description: 'What went wrong?')]
                )
                
                jiraNewIssue(
                    issue: [
                        fields: [
                            project: [key: 'LESSONS'],
                            summary: \"Build #${env.BUILD_NUMBER} Failure Analysis\",
                            description: lessonsLearned,
                            issuetype: [name: 'Documentation']
                        ]
                    ]
                )
            }
        }
    }
}
```

### Question 96
**Q:** Configure process performance baselines.

**A:**
```groovy
pipeline {
    agent any
    post {
        always {
            script {
                def baseline = [
                    buildDuration: currentBuild.duration,
                    testDuration: 0,  // Extract from test results
                    deployDuration: 0,  // Extract from deploy stage
                    timestamp: new Date().time
                ]
                
                sh \"\"\"
                    curl -X POST https://baseline.company.com/api/metrics \\
                    -d '${groovy.json.JsonOutput.toJson(baseline)}'
                \"\"\"
            }
        }
    }
}
```

### Question 97
**Q:** Implement causal analysis automation.

**A:**
```groovy
pipeline {
    agent any
    post {
        failure {
            script {
                def analysis = [
                    cause: currentBuild.getBuildCauses()[0],
                    failureStage: currentBuild.rawBuild.getExecution().getCurrentHeads()[0],
                    logs: currentBuild.rawBuild.getLog(100)
                ]
                
                jiraNewIssue(
                    issue: [
                        fields: [
                            summary: 'Failure Analysis',
                            description: groovy.json.JsonOutput.toJson(analysis)
                        ]
                    ]
                )
            }
        }
    }
}
```

### Question 98
**Q:** Configure organizational process assets update.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Update Process Assets') {
            steps {
                script {
                    def assets = [
                        pipelineTemplate: readFile('Jenkinsfile'),
                        bestPractices: readFile('BEST_PRACTICES.md'),
                        metrics: readJSON file: 'metrics.json'
                    ]
                    
                    writeJSON file: 'process-assets.json', json: assets
                    sh 'git add process-assets.json'
                    sh 'git commit -m \"Update process assets\"'
                    sh 'git push origin main'
                }
            }
        }
    }
}
```

### Question 99
**Q:** Implement quantitative management dashboard.

**A:**
```groovy
pipeline {
    agent any
    stages {
        stage('Update Dashboard') {
            steps {
                script {
                    def metrics = [
                        deployment_frequency: 1,
                        lead_time: currentBuild.duration / 1000 / 60,
                        change_failure_rate: 0.05,
                        mttr: 15,
                        code_coverage: 85.5,
                        defect_density: 2.3
                    ]
                    
                    sh \"\"\"
                        curl -X POST https://dashboard.company.com/api/metrics \\
                        -H 'Content-Type: application/json' \\
                        -d '${groovy.json.JsonOutput.toJson(metrics)}'
                    \"\"\"
                }
            }
        }
    }
}
```

### Question 100
**Q:** Configure innovation and deployment optimization feedback.

**A:**
```groovy
pipeline {
    agent any
    post {
        always {
            script {
                def feedback = [
                    buildNumber: env.BUILD_NUMBER,
                    duration: currentBuild.duration,
                    result: currentBuild.result,
                    suggestions: []
                ]
                
                if (currentBuild.duration > 600000) {  // 10 minutes
                    feedback.suggestions << 'Consider optimizing build time'
                }
                
                jiraNewIssue(
                    issue: [
                        fields: [
                            project: [key: 'IMPROVE'],
                            summary: \"Pipeline Optimization Opportunity\",
                            description: groovy.json.JsonOutput.toJson(feedback),
                            issuetype: [name: 'Improvement']
                        ]
                    ]
                )
            }
        }
    }
}
```
