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
                sh 'curl -sS https://getcomposer.org/installer | php'
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
                sh "docker build -t ${DOCKER_IMAGE}:latest ."
            }
        }

        stage('Docker Push') {
            steps {
                script {
                    // Pastikan ID ini 'docker-hub' sesuai yang ada di menu Credentials Jenkins Anda
                    withCredentials([usernamePassword(credentialsId: 'docker-hub', usernameVariable: 'DOCKER_USER', passwordVariable: 'DOCKER_PASS')]) {
                        sh 'echo $DOCKER_PASS | docker login -u $DOCKER_USER --password-stdin'
                        sh "docker push ${DOCKER_IMAGE}:latest"
                    }
                }
            }
        }

        stage('Docker Run') {
            steps {
                // 1. Bersihkan container lama
                sh "docker stop stunting-app || true"
                sh "docker rm stunting-app || true"

                // 2. Jalankan container baru (Perhatikan struktur rapi ini)
                // Kita gunakan """ (titik tiga) agar bisa multi-line
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

                // 3. Tunggu sebentar agar database connect
                sh "sleep 5"

                // 4. Setup Laravel
                sh "docker exec -i stunting-app php artisan key:generate"
                sh "docker exec -i stunting-app php artisan config:cache"
                
                // 5. Migrate Database (Perbaikan: tanda kutip penutup sudah ditambahkan)
                sh "docker exec -i stunting-app php artisan migrate --force"
            }
        }
    }
    
    post {
        always {
            sh 'docker logout || true'
        }
    }
}
