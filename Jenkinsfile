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
                    echo "📡 Menyiapkan SonarScanner Universal..."
                    
                    // 1. Dapatkan User ID & Group ID asli dari Jenkins (Host)
                    def jenkinsUid = sh(returnStdout: true, script: 'id -u').trim()
                    def jenkinsGid = sh(returnStdout: true, script: 'id -g').trim()
                    
                    echo "👤 Jenkins User ID: ${jenkinsUid}, Group ID: ${jenkinsGid}"
                    
                    withSonarQubeEnv('SonarQube') { 
                        sh """
                            docker run --rm \
                            -v "${WORKSPACE}:/usr/src" \
                            -w /usr/src \
                            -e SONAR_HOST_URL="\${SONAR_HOST_URL}" \
                            -e SONAR_TOKEN="\${SONAR_AUTH_TOKEN}" \
                            eclipse-temurin:17-jdk \
                            sh -c "
                                # A. Install Dependencies
                                apt-get update && apt-get install -y unzip curl && \
                                
                                # B. Setup Scanner
                                curl -sSLo /tmp/sonar-scanner.zip https://binaries.sonarsource.com/Distribution/sonar-scanner-cli/sonar-scanner-cli-5.0.1.3006.zip && \
                                unzip -q /tmp/sonar-scanner.zip -d /opt && \
                                mv /opt/sonar-scanner-* /opt/sonar-scanner && \
                                
                                # C. Jalankan Scanner
                                echo '🚀 Starting Scan...' && \
                                /opt/sonar-scanner/bin/sonar-scanner \
                                -Dsonar.projectKey=${APP_NAME} \
                                -Dsonar.sources=. \
                                -Dsonar.host.url=\${SONAR_HOST_URL} \
                                -Dsonar.login=\${SONAR_TOKEN} && \
                                
                                # D. FIX PERMISSIONS (CRITICAL!)
                                # Kita ubah pemilik folder .scannerwork menjadi User ID Jenkins asli
                                # supaya Jenkins bisa membacanya setelah container mati.
                                echo '🔓 Fixing permissions for User ID ${jenkinsUid}...' && \
                                chown -R ${jenkinsUid}:${jenkinsGid} .scannerwork
                            "
                        """
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
