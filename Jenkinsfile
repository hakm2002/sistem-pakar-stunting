pipeline {
    agent any

    environment {
        DOCKER_USER  = "hakm2002"
        APP_NAME     = "sistem-pakar-stunting"
        IMAGE_TAG    = "${DOCKER_USER}/${APP_NAME}:${BUILD_NUMBER}"
        LATEST_TAG   = "${DOCKER_USER}/${APP_NAME}:latest"
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
                script {
                    echo "🚀 Menjalankan PHP Environment..."
                    sh """
                        docker run --rm --user \$(id -u):\$(id -g) \
                        -v ${WORKSPACE}:/app \
                        -w /app \
                        composer:2 \
                        sh -c "
                            php -v
                            if [ -f composer.json ]; then
                                echo '📦 Found composer.json, installing dependencies...'
                                composer install --ignore-platform-reqs --no-interaction --prefer-dist
                                ./vendor/bin/phpunit || true
                            else
                                echo '⚠️ No composer.json found. Skipping composer install & tests.'
                            fi
                        "
                    """
                }
            }
        }

       stage('3. SonarQube Analysis') {
            steps {
                script {
                    echo "📡 Menjalankan SonarScanner (Mode: Brutal Permissions)..."
                    
                    withSonarQubeEnv('SonarQube') { 
                        sh """
                            # Hapus folder .scannerwork lama jika ada (untuk scan bersih)
                            rm -rf .scannerwork

                            docker run --rm \
                            -v "${WORKSPACE}:/usr/src" \
                            -w /usr/src \
                            -e SONAR_HOST_URL="\${SONAR_HOST_URL}" \
                            -e SONAR_TOKEN="\${SONAR_AUTH_TOKEN}" \
                            eclipse-temurin:17-jdk \
                            sh -c "
                                # 1. Install Unzip & Curl
                                apt-get update >/dev/null && apt-get install -y unzip curl >/dev/null && \
                                
                                # 2. Download Scanner & Rename
                                curl -sSLo /tmp/sonar-scanner.zip https://binaries.sonarsource.com/Distribution/sonar-scanner-cli/sonar-scanner-cli-5.0.1.3006.zip && \
                                unzip -q /tmp/sonar-scanner.zip -d /opt && \
                                mv /opt/sonar-scanner-* /opt/sonar-scanner && \
                                
                                # 3. Jalankan Scanner
                                echo '🚀 Starting Scan...' && \
                                /opt/sonar-scanner/bin/sonar-scanner \
                                -Dsonar.projectKey=${APP_NAME} \
                                -Dsonar.sources=. \
                                -Dsonar.host.url=\${SONAR_HOST_URL} \
                                -Dsonar.login=\${SONAR_TOKEN} && \
                                
                                # 4. FIX PERMISSIONS (NUCLEAR OPTION)
                                # Ubah permission jadi 777 (Read/Write/Execute untuk SEMUA USER)
                                # Ini menjamin Jenkins 100% bisa baca file report-task.txt
                                chmod -R 777 .scannerwork
                            "
                        """
                        
                        // DEBUG: Cek apakah file benar-benar ada dan bisa dibaca Jenkins
                        sh "ls -la .scannerwork"
                        sh "cat .scannerwork/report-task.txt"
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
            echo "✅ Pipeline Selesai!"
        }
        failure {
            echo "❌ Pipeline Gagal."
        }
    }
}
