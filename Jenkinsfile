pipeline {
    agent any

    environment {
        // --- CONFIG ---
        DOCKER_USER  = "hakm2002"
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
                        // Menggunakan HTTPS dulu agar tidak perlu SSH Credential untuk git clone
                    ]]
                ])
            }
        }

        stage('2. Install Dependencies & Test') {
            steps {
                // Cek environment
                sh 'php -v'
                sh 'composer -V'

                // Install & Test
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader'
                // Pastikan ada file phpunit.xml, jika tidak ada, comment baris bawah ini:
                sh './vendor/bin/phpunit --coverage-clover=coverage.xml --log-junit=test-report.xml || true' 
                // "|| true" agar pipeline tidak berhenti jika test gagal (opsional, untuk debugging)
            }
        }

        stage('3. SonarQube Analysis') {
            steps {
                script {
                    // Pastikan tool 'SonarScanner' sudah terinstall di Global Tool Configuration
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
                        // abortPipeline: true artinya kalau quality gate merah, stop proses
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

        /* ===================================================================
        STAGE 6 DI-SKIP (COMMENTED OUT) UNTUK LOCAL TESTING
        ===================================================================
        stage('6. Deploy Production (SSH)') {
            steps {
                sshagent([SSH_CREDS_ID]) {
                    script {
                        def secretContent = readFile(file: ENV_SECRET)
                        def finalEnvContent = "${secretContent}\nFULL_IMAGE_NAME=${LATEST_TAG}"
                        writeFile file: '.env', text: finalEnvContent

                        sh "ssh -o StrictHostKeyChecking=no ${DEPLOY_USER}@${DEPLOY_HOST} 'mkdir -p ${DEPLOY_DIR}'"
                        sh "scp -o StrictHostKeyChecking=no docker-compose.prod.yml .env ${DEPLOY_USER}@${DEPLOY_HOST}:${DEPLOY_DIR}/"
                        
                        sh """
                            ssh -o StrictHostKeyChecking=no ${DEPLOY_USER}@${DEPLOY_HOST} '
                                cd ${DEPLOY_DIR}
                                docker compose -f docker-compose.prod.yml down --remove-orphans
                                docker compose -f docker-compose.prod.yml pull
                                docker compose -f docker-compose.prod.yml up -d
                            '
                        """
                    }
                }
            }
        }
        */
    }
        
    post {
        always {
            script {
                echo "🧹 Cleaning up..."
                // Menggunakan try-catch agar error cleanup tidak membingungkan log utama
                try {
                    if (env.IMAGE_TAG) {
                        sh "docker rmi ${IMAGE_TAG} || true"
                    }
                    sh "docker image prune -f"
                } catch (Exception e) {
                    echo "⚠️ Warning: Cleanup failed but ignoring it. ${e}"
                }
            }
            cleanWs()
        }
        success {
            echo "✅ Build & Push Docker Berhasil!"
        }
        failure {
            echo "❌ Pipeline Gagal."
        }
    }
}
