pipeline {
    agent any

    environment {
        // --- CONFIG ---
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
                    echo "🚀 Memaksa Docker Run secara Manual..."
                    
                    // --- PENJELASAN TEKNIS ---
                    // Kita tidak menggunakan 'php -v' langsung.
                    // Kita menggunakan 'docker run' untuk meminjam environment composer sebentar.
                    // --rm : Hapus container setelah selesai (biar bersih)
                    // -v ${WORKSPACE}:/app : Masukkan kodingan kita ke dalam container
                    // -w /app : Masuk ke folder kodingan di dalam container
                    // composer:2 : Nama image docker yang punya PHP
                    
                    sh """
                        docker run --rm --user \$(id -u):\$(id -g) \
                        -v ${WORKSPACE}:/app \
                        -w /app \
                        composer:2 \
                        sh -c "php -v && composer install --ignore-platform-reqs --no-interaction --prefer-dist && ./vendor/bin/phpunit || true"
                    """
                }
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
            script {
                try {
                    // Node block wajib ada untuk cleanup
                    node {
                        echo "🧹 Cleaning up..."
                        if (env.IMAGE_TAG) {
                           sh "docker rmi ${env.IMAGE_TAG} || true"
                        }
                        sh "docker image prune -f"
                        
                        // Perintah ini mungkin butuh sudo jika permission file dari docker run tadi salah
                        // Tapi kita sudah pakai --user $(id -u) di atas, jadi aman.
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
