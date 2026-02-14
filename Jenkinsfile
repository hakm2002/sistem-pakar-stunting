pipeline {
    agent any

    // --- SETUP TOOLS ---
    // Hapus bagian 'tools' jika server Jenkins (Agent) sudah terinstall PHP & Composer secara native.
    // Atau gunakan image docker sebagai agent jika ingin isolasi total.
    environment {
        // --- CONFIG ---
        DOCKER_USER  = "dockerdevopsethos"
        APP_NAME     = "sistem-pakar-stunting" // Sesuaikan nama app
        IMAGE_TAG    = "${DOCKER_USER}/${APP_NAME}:${BUILD_NUMBER}"
        LATEST_TAG   = "${DOCKER_USER}/${APP_NAME}:latest"
        
        // --- SERVER TUJUAN ---
        DEPLOY_USER  = "root"
        DEPLOY_HOST  = "192.168.68.200" 
        //DEPLOY_DIR   = "/var/www/Datapooling.ethos.co.id/DatapoolingPHP"
        
        // --- CREDENTIALS ID ---
        DOCKER_CREDS = credentials('dockerhub-id-hakm')
        ENV_SECRET   = credentials('sistem-pakar-stunting') // Pastikan ID ini ada di Jenkins
        // SSH_CREDS_ID = 'ssh-server-deploy'
    }

    stages {
        stage('1. Checkout') {
            steps {
                cleanWs()
                checkout([
                    $class: 'GitSCM', 
                    branches: [[name: '*/develop']], 
                    userRemoteConfigs: [[
                        url: 'git@github.com:Datapooling-Ethos/DatapoolingPHP.git',
                        credentialsId: 'ssh-server-deploy'
                    ]]
                ])
            }
        }

        stage('2. Install Dependencies & Test') {
            steps {
                // Pastikan PHP dan Composer terinstall di Agent Jenkins
                sh 'php -v'
                sh 'composer -V'

                // Install dependencies untuk kebutuhan testing/build
                // --no-interaction: Jangan tanya yes/no
                // --prefer-dist: Download zip (lebih cepat)
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader'

                // (Opsional) Cek Syntax Error sebelum test
                // sh 'find . -name "*.php" -print0 | xargs -0 -n1 -P8 php -l'

                // Jalankan PHPUnit (Pastikan ada file phpunit.xml di root project)
                // Menghasilkan report coverage clover.xml untuk SonarQube
                sh './vendor/bin/phpunit --coverage-clover=coverage.xml --log-junit=test-report.xml'
            }
        }

        stage('3. SonarQube Analysis') {
            steps {
                script {
                    def scannerHome = tool 'SonarScanner' 
                    withSonarQubeEnv('SonarQube-Server') { 
                        // Di PHP, pastikan sonar-project.properties sudah menunjuk ke coverage.xml
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
                    // Build Docker Image
                    // Pastikan Dockerfile di PHP project sudah meng-copy folder 'vendor' 
                    // atau melakukan 'composer install' di dalam Dockerfile
                    sh "docker build -t ${IMAGE_TAG} ."
                    sh "docker tag ${IMAGE_TAG} ${LATEST_TAG}"
                    
                    withCredentials([usernamePassword(credentialsId: 'docker-hub-login', passwordVariable: 'PASS', usernameVariable: 'USER')]) {
                        sh "echo $PASS | docker login -u $USER --password-stdin"
                        sh "docker push ${IMAGE_TAG}"
                        sh "docker push ${LATEST_TAG}"
                    }
                }
            }
        }

        stage('6. Deploy Production (SSH)') {
            steps {
                sshagent([SSH_CREDS_ID]) {
                    script {
                        // ---------------------------------------------------------
                        // LANGKAH 1: PERSIAPAN FILE .ENV
                        // ---------------------------------------------------------
                        def secretContent = readFile(file: ENV_SECRET)
                        
                        // Inject Image Tag terbaru ke .env agar docker-compose membacanya
                        def finalEnvContent = "${secretContent}\nFULL_IMAGE_NAME=${LATEST_TAG}"

                        writeFile file: '.env', text: finalEnvContent

                        echo "--- PREVIEW .ENV ---"
                        sh "head -n 3 .env"

                        // ---------------------------------------------------------
                        // LANGKAH 2: KIRIM FILE KE SERVER
                        // ---------------------------------------------------------
                        // Kirim docker-compose.yml dan .env
                        sh "scp -o StrictHostKeyChecking=no docker-compose.prod.yml .env ${DEPLOY_USER}@${DEPLOY_HOST}:${DEPLOY_DIR}/"
                        
                        // ---------------------------------------------------------
                        // LANGKAH 3: EKSEKUSI DOCKER & POST-DEPLOY PHP TASKS
                        // ---------------------------------------------------------
                        sh """
                            ssh -o StrictHostKeyChecking=no ${DEPLOY_USER}@${DEPLOY_HOST} '
                                cd ${DEPLOY_DIR}
                                echo "🚀 Connected to Server..."
                                
                                # 1. Restart Container
                                docker compose -f docker-compose.prod.yml down --remove-orphans
                                docker compose -f docker-compose.prod.yml pull
                                docker compose -f docker-compose.prod.yml up -d
                                
                                # 2. PHP Specific Tasks (Sangat Penting untuk PHP Framework seperti Laravel/Symfony)
                                echo "⚙️ Running Post-Deployment Tasks..."
                                
                                # Contoh: Fix Permission folder storage (jika Laravel)
                                # docker compose -f docker-compose.prod.yml exec -T app chown -R www-data:www-data /var/www/html/storage
                                
                                # Contoh: Jalankan Migrasi Database
                                # docker compose -f docker-compose.prod.yml exec -T app php artisan migrate --force
                                
                                # Contoh: Clear & Cache Config
                                # docker compose -f docker-compose.prod.yml exec -T app php artisan optimize:clear
                                # docker compose -f docker-compose.prod.yml exec -T app php artisan config:cache
                                
                                echo "✅ Deployment PHP Selesai!"
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
                sh "docker rmi ${IMAGE_TAG} || true"
                sh "docker image prune -f"
            }
            cleanWs()
        }
        success {
            echo "✅ Deployment Sukses di Server Host: ${DEPLOY_DIR}"
        }
        failure {
            echo "❌ Deployment Gagal."
        }
    }
}
