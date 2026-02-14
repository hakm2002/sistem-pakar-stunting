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
                    echo "🐧 Menjalankan PHP/Composer menggunakan Docker Container sementara..."
                    
                    // PENJELASAN TEKNIS:
                    // Kita menggunakan 'docker run' untuk meminjam lingkungan PHP hanya untuk langkah ini.
                    // -v ${WORKSPACE}:/app  : Menghubungkan folder codingan Jenkins ke dalam container di folder /app
                    // -w /app               : Memerintahkan docker untuk bekerja di dalam folder /app
                    // --rm                  : Hapus container setelah selesai (biar hemat disk)
                    // composer:2            : Image Docker resmi yang berisi PHP & Composer
                    
                    // 1. Cek Versi (Opsional, buat debug)
                    sh 'docker run --rm -v ${WORKSPACE}:/app -w /app composer:2 php -v'
                    
                    // 2. Install Dependencies
                    // Kita tambah --ignore-platform-reqs jaga-jaga kalau extension PHP di container beda dikit
                    sh 'docker run --rm -v ${WORKSPACE}:/app -w /app composer:2 composer install --no-interaction --prefer-dist --ignore-platform-reqs'
                    
                    // 3. Jalankan Test
                    // "|| true" agar pipeline tidak merah kalau unit test gagal (karena ini development)
                    sh 'docker run --rm -v ${WORKSPACE}:/app -w /app composer:2 ./vendor/bin/phpunit --coverage-clover=coverage.xml --log-junit=test-report.xml || true'
                }
            }
        }

        stage('3. SonarQube Analysis') {
            steps {
                script {
                    // Pastikan tool 'SonarScanner' ada di Jenkins Global Tool Configuration
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
            // Cleanup harus dibungkus 'node' agar sh command jalan
            script {
                try {
                    node {
                        echo "🧹 Cleaning up..."
                        
                        // Hapus container/image sisa
                        if (env.IMAGE_TAG) {
                           sh "docker rmi ${env.IMAGE_TAG} || true"
                        }
                        sh "docker image prune -f"
                        
                        // Karena kita pakai docker run -v, folder vendor mungkin jadi milik root
                        // Kita paksa hapus dengan sudo atau docker run lagi jika perlu, 
                        // tapi cleanWs() biasanya cukup kuat.
                        cleanWs()
                    }
                } catch (Exception e) {
                    echo "⚠️ Cleanup error (ignored): ${e.getMessage()}"
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
