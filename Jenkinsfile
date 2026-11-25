// =============================================================================
// Pipeline de la API (Laravel) de Buenaventura Reporta
//
//   Checkout → Build → Test → Docker Build → Delivery
//
// Todas las ramas y pull requests pasan por Checkout, Build y Test (CI).
// Solo `main` construye la imagen de producción y la despliega (CD).
//
// Jenkins corre en un contenedor que usa el Docker del servidor (docker.sock).
// Por eso nada se monta desde el workspace: los reportes se copian con `docker cp`.
// =============================================================================
pipeline {
  agent any

  options {
    skipDefaultCheckout()                 // el checkout es la etapa "Checkout"
    disableConcurrentBuilds()
    timestamps()
    buildDiscarder(logRotator(numToKeepStr: '20'))
    timeout(time: 30, unit: 'MINUTES')
  }

  environment {
    APP_DIR = '/opt/buenaventura'         // docker-compose.yml + .env de producción
    COMPOSE = 'docker compose -f docker-compose.yml -f docker-compose.prod.yml'
    IMAGEN  = 'buenaventura/api'
  }

  stages {
    stage('Checkout') {
      steps {
        checkout scm
        script {
          env.TAG = sh(script: 'git rev-parse --short HEAD', returnStdout: true).trim()
          env.PRUEBAS = "api-pruebas-${env.BUILD_TAG}".replaceAll('[^A-Za-z0-9_.-]', '-')
        }
        echo "Commit ${env.TAG} de la rama ${env.BRANCH_NAME}"
      }
    }

    stage('Build') {
      // Etapa "test" del Dockerfile: PHP 8.2 + extensiones + Composer (con dev) + código
      steps {
        sh 'docker build --target test -t $IMAGEN:test-$TAG .'
      }
    }

    stage('Test') {
      // PHPUnit con SQLite en memoria: no toca la base de datos de producción
      steps {
        sh '''
          mkdir -p reportes
          docker run --name "$PRUEBAS" $IMAGEN:test-$TAG php artisan test --log-junit /tmp/phpunit.xml
        '''
      }
      post {
        always {
          sh '''
            docker cp "$PRUEBAS:/tmp/phpunit.xml" reportes/phpunit.xml || true
            docker rm -f "$PRUEBAS" >/dev/null 2>&1 || true
          '''
          junit allowEmptyResults: true, testResults: 'reportes/phpunit.xml'
        }
      }
    }

    stage('Docker Build') {
      when { branch 'main' }
      // Imagen final: sin dependencias de desarrollo, usuario sin privilegios
      steps {
        sh 'docker build --target prod -t $IMAGEN:$TAG .'
      }
    }

    stage('Delivery') {
      when { branch 'main' }
      steps {
        // Evita que la API y el frontend se desplieguen a la vez
        lock('despliegue-produccion') {
          sh '''
            cd "$APP_DIR"

            # Conserva la versión en producción para poder volver a ella
            docker image tag $IMAGEN:latest $IMAGEN:anterior 2>/dev/null || true
            docker image tag $IMAGEN:$TAG $IMAGEN:latest

            $COMPOSE up -d backend

            if ! ./scripts/esperar-salud.sh backend 180; then
              echo "La nueva versión no quedó healthy: se restaura la anterior"
              $COMPOSE logs --tail=80 backend || true
              if docker image inspect $IMAGEN:anterior >/dev/null 2>&1; then
                docker image tag $IMAGEN:anterior $IMAGEN:latest
                $COMPOSE up -d backend
              fi
              exit 1
            fi
            echo "API $TAG desplegada"
          '''
        }
      }
    }
  }

  post {
    always {
      sh 'docker image rm $IMAGEN:test-$TAG >/dev/null 2>&1 || true'
      sh 'docker image prune -f >/dev/null 2>&1 || true'
      deleteDir()
    }
  }
}
