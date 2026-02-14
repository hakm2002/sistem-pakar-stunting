pipeline {
    agent any

    environment {
        // --- CONFIG ---
        DOCKER_USER  = "hakm2002"
        APP_NAME     = "sistem-pakar-stunting"
        IMAGE_TAG    = "${DOCKER_USER}/${APP_NAME}:${BUILD_NUMBER}"
        LATEST_TAG   = "${DOCKER_USER}/${APP_NAME}:latest"
        
        // --- SERVER TUJUAN ---
        DEPLOY_USER  = "root"
        DEPLOY_HOST  = "192.168.68.200" 
        // WAJIB DI-UNCOMMENT & ISI: Folder project di server tujuan
        DEPLOY_DIR   = "/var/www/sistem-pakar-stunting"
        
        // --- CREDENTIALS ID ---
        DOCKER_CREDS = credentials('dockerhub-id-hakm')
        ENV_SECRET   = credentials('sistem-pakar-stunting') 
        
        // WAJIB DI-UNCOMMENT: ID Credential SSH untuk akses server deploy & Git (jika private)
        SSH_CREDS_ID = 'ssh-server-deploy' 
    }

    stages {
        stage('1. Checkout') {
            steps {
                cleanWs()
                checkout([
                    $class: 'GitSCM', 
                    branches: [[name: '*/main']], 
                    userRemoteConfigs: [[
                        url: 'git@github.com:hakm2002/sistem-pakar-stunting.git',
                        // Jika repo private, uncomment baris di bawah ini:
                        credentialsId: SSH_CREDS_ID
                    ]]
                ])
            }
        }

        stage('2. Install Dependencies & Test') {
            steps {
                // Cek versi PHP
                sh 'php -v'
                sh 'composer -V'

                // Install dependencies
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader'

                // Jalankan PHPUnit
                // Pastikan phpunit.xml ada di root project
                sh './vendor/bin/phpunit --coverage-clover=coverage.xml --log-junit=test-report.xml'
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
                    // Build
                    sh "docker build -t ${IMAGE_TAG} ."
                    sh "docker tag ${IMAGE_TAG} ${LATEST_TAG}"
                    
                    // Push
                    withCredentials([usernamePassword(credentialsId: 'dockerhub-id-hakm', passwordVariable: 'PASS', usernameVariable: 'USER')]) {
                        sh "echo $PASS | docker login -u $USER --password-stdin"
                        sh "docker push ${IMAGE_TAG}"
                        sh "docker push ${LATEST_TAG}"
                    }
                }
            }
        }

        stage('6. Deploy Production (SSH)') {
            steps {
                // Pastikan SSH_CREDS_ID sudah didefinisikan di environment
                sshagent([SSH_CREDS_ID]) {
                    script {
                        // 1. Buat file .env dari credential Jenkins
                        def secretContent = readFile(file: ENV_SECRET)
                        def finalEnvContent = "${secretContent}\nFULL_IMAGE_NAME=${LATEST_TAG}"
                        writeFile file: '.env', text: finalEnvContent

                        // 2. Kirim docker-compose dan .env ke server
                        // Pastikan folder tujuan sudah ada, atau tambahkan mkdir -p
                        sh "ssh -o StrictHostKeyChecking=no ${DEPLOY_USER}@${DEPLOY_HOST} 'mkdir -p ${DEPLOY_DIR}'"
                        sh "scp -o StrictHostKeyChecking=no docker-compose.prod.yml .env ${DEPLOY_USER}@${DEPLOY_HOST}:${DEPLOY_DIR}/"
                        
                        // 3. Eksekusi Remote
                        sh """
                            ssh -o StrictHostKeyChecking=no ${DEPLOY_USER}@${DEPLOY_HOST} '
                                cd ${DEPLOY_DIR}
                                echo "🚀 Connected to Server ${DEPLOY_HOST}..."
                                
                                # Restart Container
                                docker compose -f docker-compose.prod.yml down --remove-orphans
                                docker compose -f docker-compose.prod.yml pull
                                docker compose -f docker-compose.prod.yml up -d
                                
                                echo "✅ Deployment Selesai!"
                            '
                        """
                    }
                }
            }
        }
    }
        
    post {
        always {
            script {
                echo "🧹 Cleaning up..."
                // FIX ERROR: Pengecekan apakah variabel IMAGE_TAG ada isinya
                if (env.IMAGE_TAG) {
                    sh "docker rmi ${IMAGE_TAG} || true"
                }
                sh "docker image prune -f"
            }
            cleanWs()
        }
        success {
            echo "✅ Deployment Sukses!"
        }
        failure {
            echo "❌ Deployment Gagal."
        }
    }
}
