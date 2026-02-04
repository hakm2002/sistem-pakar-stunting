pipeline {
    agent any

    environment {
        DOCKER_IMAGE = "hakm2002/sistem-pakar-stunting"
    }

    stages {
        stage('Git Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Install Dependencies') {
            agent {
                docker { 
                    image 'composer:latest' 
                    args '-u root' 
                }
            }
            steps {
                // Kita tidak perlu curl/download composer lagi karena sudah ada di image
                // --ignore-platform-reqs digunakan agar build tidak gagal jika ekstensi PHP di container composer berbeda dengan prod
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs'
                sh 'cp .env.example .env || true'
                
                // Mengubah permission agar file yang dibuat root bisa dibaca oleh Jenkins saat build image
                sh 'chmod -R 777 .'
            }
        }

        stage('SonarQube Analysis') {
            steps {
                script {
                    def scannerHome = tool 'sonar' 
                    withSonarQubeEnv('SonarQube') {
                        sh "${scannerHome}/bin/sonar-scanner -Dsonar.projectKey=stunting-laravel -Dsonar.sources=."
                    }
                }
            }
        }

        stage('Build Docker Image') {
            steps {
                // Memastikan folder vendor hasil stage sebelumnya ikut masuk ke dalam build
                sh "docker build -t ${DOCKER_IMAGE}:latest ."
            }
        }

        stage('Docker Push') {
            steps {
                script {
                    withCredentials([usernamePassword(credentialsId: 'docker-hub', usernameVariable: 'DOCKER_USER', passwordVariable: 'DOCKER_PASS')]) {
                        sh 'echo $DOCKER_PASS | docker login -u $DOCKER_USER --password-stdin'
                        sh "docker push ${DOCKER_IMAGE}:latest"
                    }
                }
            }
        }

        stage('Docker Run') {
            steps {
                script {
                    // 1. Bersihkan container lama jika ada
                    sh "docker stop stunting-app || true"
                    sh "docker rm stunting-app || true"

                    // 2. Jalankan container baru
                    // Pastikan network 'docker-laravel-mysql-nginx-starter_laravel' sudah ada di server
                    sh """
                    docker run -d --name stunting-app -p 8081:80 \
                    --network docker-laravel-mysql-nginx-starter_laravel \
                    -e DB_HOST=mysql \
                    -e DB_PORT=3306 \
                    -e DB_DATABASE=sistem_pakar \
                    -e DB_USERNAME=root \
                    -e DB_PASSWORD=root \
                    ${DOCKER_IMAGE}:latest
                    """

                    // 3. Beri waktu container untuk up sempurna
                    sh "sleep 10"

                    // 4. Setup Laravel & Database
                    // Menjalankan perintah di dalam container yang baru jalan
                    sh "docker exec stunting-app php artisan key:generate"
                    sh "docker exec stunting-app php artisan config:cache"
                    sh "docker exec stunting-app php artisan migrate --force"
                }
            }
        }
    }
    
    post {
        always {
            // Logout untuk keamanan
            sh 'docker logout || true'
        }
        success {
            echo 'Deployment Berhasil!'
        }
        failure {
            echo 'Deployment Gagal, periksa log di atas.'
        }
    }
}
