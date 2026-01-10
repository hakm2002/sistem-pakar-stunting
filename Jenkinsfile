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
                // Laravel butuh composer install sebelum di-scan atau di-build
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader'
                sh 'cp .env.example .env || true'
                sh 'php artisan key:generate'
            }
        }

        stage('SonarQube Analysis') {
            steps {
                script {
                    // Pastikan di Manage Jenkins > Tools namanya adalah 'sonar'
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
                    docker.withRegistry('', 'dockerhub-pwd') {
                        sh "docker push ${DOCKER_IMAGE}:latest"
                    }
                }
            }
        }

        stage('Docker Run') {
            steps {
                // HANYA SATU STAGE RUN DI AKHIR
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
