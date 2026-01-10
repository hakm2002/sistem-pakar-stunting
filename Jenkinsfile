pipeline {
    agent any
    tools {
        maven 'jenkins-maven'
    }

    environment {
        BUILD_NUMBER_ENV = "${env.BUILD_NUMBER}"
        // Ganti username docker di sini
        DOCKER_IMAGE = "hakm2002/sistem-pakar-stunting" 
    }

    stages {
        stage('Git Checkout') {
            steps {
                // Gunakan repo hasil fork
                checkout scmGit(branches: [[name: '*/main']], userRemoteConfigs: [[url: 'https://github.com/hakm2002/sistem-pakar-stunting.git']])
                sh 'mvn clean install -DskipTests'
            }
        }

        stage('SonarQube Analysis') {
            steps {
                script {
                    // Nama 'SonarQube' harus sesuai dengan yang ada di Manage Jenkins > System
                    withSonarQubeEnv('SonarQube') {
                        sh "mvn sonar:sonar -Dsonar.projectKey=sistem-pakar-stunting -Dsonar.projectName='sistem-pakar-stunting'"
                    }
                }
            }
        }

        stage("Quality Gate") {
            steps {
                waitForQualityGate abortPipeline: true
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
                    // 'dockerhub-pwd' ID di Jenkins Credentials (Type: Username with password)
                    docker.withRegistry('', 'dockerhub-pwd') {
                        sh "docker push ${DOCKER_IMAGE}:latest"
                    }
                }
            }
        }

        stage('Docker Run') {
            steps {
                sh "docker stop sistem-pakar-stunting || true"
                sh "docker rm sistem-pakar-stunting || true"
                sh "docker run -d --name sistem-pakar-stunting -p 8099:8080 ${DOCKER_IMAGE}:latest"
            }
        }
    }
    
    post {
        always {
            sh 'docker logout || true'
        }
    }
}
