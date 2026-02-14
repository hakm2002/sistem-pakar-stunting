pipeline {
    agent any

    environment {
        // --- CONFIG ---
        DOCKER_USER  = "dockerdevopsethos"
        APP_NAME     = "sistem-pakar-stunting"
        IMAGE_TAG    = "${DOCKER_USER}/${APP_NAME}:${BUILD_NUMBER}"
        LATEST_TAG   = "${DOCKER_USER}/${APP_NAME}:latest"
        
        // --- CREDENTIALS ID ---
        DOCKER_CREDS = credentials('dockerhub-id-hakm')
        
        // --- DEPLOY CONFIG (DI-SKIP DULU UNTUK LOCAL TEST) ---
        // DEPLOY_USER  = "root"
        // DEPLOY_HOST  = "192.168.68.200" 
        // DEPLOY_DIR   = "/var/www/sistem-pakar-stunting"
        // ENV_SECRET   = credentials('sistem-pakar-stunting') 
        // SSH_CREDS_ID = 'ssh-server-deploy' 
    }

    stages {
        stage('1. Checkout') {
            steps {
                cleanWs()
                checkout([
                    $class: 'GitSCM', 
                    branches: [[name: '*/main']], 
                    userRemoteConfigs: [[
                        url: 'https://github.com/hakm2002/sistem-pakar-stunting.git'
                    ]]
                ])
            }
        }

        stage('2. Install Dependencies & Test') {
            steps {
                sh 'php -v'
                sh 'composer -V'
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader'
                // Gunakan "|| true" agar pipeline tidak berhenti total jika unit test gagal saat development
                sh './vendor/bin/phpunit --coverage-clover=coverage.xml --log-junit=test-report.xml || true' 
            }
        }

        stage('3. SonarQube Analysis') {
            steps {
                script {
                    def scannerHome = tool 'SonarScanner' 
                    withSonarQubeEnv('SonarQube') { 
                        sh "${scannerHome}/bin/sonar-scanner"
                    }
                }
            }
        }

        stage('4. Quality Gate') {
            steps {
                script {
                    timeout(time: 2, unit: 'MINUTES') {
                        waitForQualityGate abortPipeline: true
                    }
                }
            }
        }

        stage('5. Build & Push Docker') {
            steps {
                script {
                    echo "🐳 Building Docker Image..."
                    sh "docker build -t ${IMAGE_TAG} ."
                    sh "docker tag ${IMAGE_TAG} ${LATEST_TAG}"
                    
                    echo "🚀 Pushing to Docker Hub..."
                    withCredentials([usernamePassword(credentialsId: 'dockerhub-id-hakm', passwordVariable: 'PASS', usernameVariable: 'USER')]) {
                        sh "echo $PASS | docker login -u $USER --password-stdin"
                        sh "docker push ${IMAGE_TAG}"
                        sh "docker push ${LATEST_TAG}"
                    }
                }
            }
        }
        
        // Stage 6 Deploy di-skip dulu sesuai request
    }
        
    post {
        always {
            // --- FIX UTAMA DISINI ---
            // Kita bungkus dengan 'node' agar sh command punya konteks tempat berjalan
            node {
                script {
                    echo "🧹 Cleaning up..."
                    try {
                        // Cek apakah image tag ada isinya sebelum menghapus
                        if (env.IMAGE_TAG) {
                            sh "docker rmi ${IMAGE_TAG} || true"
                        }
                        sh "docker image prune -f"
                    } catch (Exception e) {
                        echo "⚠️ Cleanup warning: ${e.getMessage()}"
                    }
                }
                // cleanWs() juga butuh node context
                cleanWs()
            }
        }
        success {
            echo "✅ Build & Push Docker Berhasil!"
        }
        failure {
            echo "❌ Pipeline Gagal."
        }
    }
}
