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
            steps {
                // Pastikan PHP & Composer terinstall di server Jenkins (Host)
                // Langkah ini diperlukan agar SonarQube bisa membaca codingan lengkap
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader'
                sh 'cp .env.example .env || true'
                sh 'php artisan key:generate'
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
                // Menggunakan sh biasa (lebih aman dari error plugin)
                sh "docker build -t ${DOCKER_IMAGE}:latest ."
            }
        }

        stage('Docker Push') {
            steps {
                // PERBAIKAN UTAMA DI SINI:
                // Mengganti docker.withRegistry dengan withCredentials + sh
                script {
                    withCredentials([usernamePassword(credentialsId: 'docker-hub', usernameVariable: 'DOCKER_USER', passwordVariable: 'DOCKER_PASS')]) {
                        // Login manual lewat shell
                        sh 'echo $DOCKER_PASS | docker login -u $DOCKER_USER --password-stdin'
                        // Push image
                        sh "docker push ${DOCKER_IMAGE}:latest"
                    }
                }
            }
        }

        stage('Docker Run') {
            steps {
                // Hentikan container lama jika ada, lalu jalankan yang baru
                sh "docker stop stunting-app || true"
                sh "docker rm stunting-app || true"

                sh """
                sh "docker run -d --name stunting-app -p 8081:80 ${DOCKER_IMAGE}:latest"
                --network docker-laravel-mysql-nginx-starter_laravel \
                -e DB_HOST=mysql \
                -e DB_PORT=3306 \
                -e DB_DATABASE=sistem_pakar \
                -e DB_USERNAME=root \
                -e DB_PASSWORD=root \
                ${DOCKER_IMAGE}:latest
                """
                "sh sleep 5"
                sh "docker exec -i stunting-app php artisan key:generate"
                sh "docker exec -i stunting-app php artisan config:cache"
                sh "docker exec -i stunting-app php artisan migrate
            }
        }
    }
    
    post {
        always {
            // Logout agar kredensial aman
            sh 'docker logout || true'
        }
    }
}
