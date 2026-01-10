pipeline {
    agent any

    environment {
        DOCKER_IMAGE = "hakm2002/sistem-pakar-stunting" 
    }

    stages {
        stage('Git Checkout') {
            steps {
                // ambil kode dari repo Laravel
                checkout scm
            }
        }

        stage('Install Dependencies') {
            steps {
                // Menggunakan Composer untuk Laravel
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader'
                sh 'cp .env.example .env || true'
                sh 'php artisan key:generate'
            }
        }

        stage('SonarQube Analysis') {
            steps {
                script {
                    // SonarScanner untuk PHP
                    def scannerHome = tool 'SonarScanner' // nama sesuai di Global Tool Configuration
                    withSonarQubeEnv('SonarQube') {
                        sh "${scannerHome}/bin/sonar-scanner -Dsonar.projectKey=stunting-laravel -Dsonar.sources=."
                    }
                }
            }
        }

        stage('Build Docker Image') {
            steps {
                // Membangun image Dockerfile Laravel
                sh "docker build -t ${DOCKER_IMAGE}:latest ."
            }
        }

        stage('Docker Push') {
            steps {
                script {
                    docker.withRegistry('', 'dockerhub-pwd') {
                        sh "docker push ${DOCKER_IMAGE}:latest"
                    }
                }
            }
        }

        stage('Docker Run') {
            steps {
                // Menyesuaikan port Laravel port 80 atau 8000
                sh "docker stop stunting-app || true"
                sh "docker rm stunting-app || true"
                sh "docker run -d --name stunting-app -p 8080:80 ${DOCKER_IMAGE}:latest"
            }
        }
    }
    
    post {
        always {
            sh 'docker logout || true'
        }
    }
}
