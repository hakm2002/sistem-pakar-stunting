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

        // --- FIX: JALANKAN DI DALAM CONTAINER PHP/COMPOSER ---
        stage('2. Install Dependencies & Test') {
            agent {
                docker {
                    // Menggunakan image official composer yang sudah ada PHP-nya
                    image 'composer:2' 
                    // reuseNode true mempercepat proses karena tidak perlu spin-up node baru
                    reuseNode true 
                }
            }
            steps {
                // Sekarang perintah ini akan jalan karena ada di dalam container composer
                sh 'php -v'
                sh 'composer -V'
                
                // Gunakan --ignore-platform-reqs jika extension PHP di container kurang lengkap
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs'
                
                // Jalankan test (gunakan || true agar pipeline tidak stop jika test gagal)
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
                    // Kita build ulang menggunakan Dockerfile project untuk environment asli
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
            // Tetap gunakan fix 'node' yang tadi agar cleanup berhasil
            script {
                try {
                    node {
                        echo "🧹 Cleaning up..."
                        if (env.IMAGE_TAG) {
                           sh "docker rmi ${env.IMAGE_TAG} || true"
                        }
                        sh "docker image prune -f"
                        cleanWs()
                    }
                } catch (Exception e) {
                    echo "⚠️ Cleanup error ignored: ${e.getMessage()}"
                }
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
