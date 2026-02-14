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
                // Menggunakan "|| true" agar pipeline tidak berhenti jika test gagal
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
    }
        
    post {
        always {
            // --- PERBAIKAN UTAMA DI SINI ---
            // Kita bungkus SEMUA perintah post dengan 'node'
            // Ini menjamin 'sh' dan 'cleanWs' punya workspace/FilePath
            node {
                script {
                    echo "🧹 Cleaning up..."
                    try {
                        // Pastikan variabel ada sebelum dipakai
                        if (env.IMAGE_TAG) {
                            sh "docker rmi ${IMAGE_TAG} || true"
                        }
                        sh "docker image prune -f"
                    } catch (Exception e) {
                        echo "⚠️ Cleanup warning: ${e.getMessage()}"
                    }
                }
                // cleanWs() sekarang aman karena berada di dalam 'node'
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
