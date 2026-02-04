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
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs'
                sh 'cp .env.example .env || true'
                sh 'chmod -R 777 .'
            }
        }

        stage('SonarQube Analysis') {
            steps {
                script {
                    def scannerHome = tool 'sonar-scanner' 
                    // Pastikan nama 'SonarQube' di bawah ini sama dengan nama di Manage Jenkins > System
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
                    sh "docker stop stunting-app || true"
                    sh "docker rm stunting-app || true"

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

                    sh "sleep 10"
                    sh "docker exec stunting-app php artisan key:generate"
                    sh "docker exec stunting-app php artisan config:cache"
                    sh "docker exec stunting-app php artisan migrate --force"
                }
            }
        }
    }
    
    post {
        always {
            sh 'docker logout || true'
        }
        success { echo 'Deployment Berhasil!' }
        failure { echo 'Deployment Gagal, periksa log di atas.' }
    }
}
