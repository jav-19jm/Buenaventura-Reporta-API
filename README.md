<div align="center">

<picture>
  <source media="(prefers-color-scheme: dark)" srcset=".github/assets/logo-completo-blanco.svg">
  <img src=".github/assets/logo-completo.svg" alt="Buenaventura Reporta" width="420">
</picture>

### API REST de la plataforma ciudadana Buenaventura Reporta

Autenticación, reportes, mensajería, notificaciones en tiempo real y paneles por rol para la plataforma de incidencias urbanas de Buenaventura.

[![Producción](https://img.shields.io/badge/API-buenaventura--reporta.tech%2Fapi-0a5caa?style=flat-square)](https://buenaventura-reporta.tech/api/prueba)
![PHP](https://img.shields.io/badge/PHP-8.2-137cc8?style=flat-square&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-137cc8?style=flat-square&logo=laravel&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-17-137cc8?style=flat-square&logo=postgresql&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-7-137cc8?style=flat-square&logo=redis&logoColor=white)
![Pruebas](https://img.shields.io/badge/pruebas-PHPUnit-398656?style=flat-square&logo=php&logoColor=white)
![CI/CD](https://img.shields.io/badge/CI%2FCD-Jenkins-f8b71d?style=flat-square&logo=jenkins&logoColor=white)

[**Aplicación**](https://buenaventura-reporta.tech) ·
[**Frontend**](https://github.com/jav-19jm/Buenaventura-Reporta) ·
[**Documentación y referencia OpenAPI**](https://github.com/jav-19jm/Documentacion-Buenaventura-Reporta) ·
[**Tablero (Trello)**](https://trello.com/b/llfywpfw/bunaventura-reporta)

</div>

---

## Contenido

- [Sobre el proyecto](#sobre-el-proyecto)
- [Tecnologías](#tecnologías)
- [Arquitectura](#arquitectura)
- [Puesta en marcha](#puesta-en-marcha)
- [Endpoints principales](#endpoints-principales)
- [Seguridad](#seguridad)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Pruebas y CI/CD](#pruebas-y-cicd)
- [Cómo contribuir](#cómo-contribuir)
- [Equipo](#equipo)

---

## Sobre el proyecto

**Buenaventura Reporta** es una plataforma ciudadana y académica, independiente de la Alcaldía, para reportar incidencias urbanas en Buenaventura (Valle del Cauca), verlas en un mapa y seguir su atención.

Este repositorio contiene la **API**, construida con Laravel 12. Atiende al [frontend en React](https://github.com/jav-19jm/Buenaventura-Reporta) y gestiona estos roles:

| Rol | Alcance en la API |
|---|---|
| **Ciudadano** | Crear, consultar y eliminar sus reportes; subir fotos; votar; conversar en cada reporte; notificaciones e insignias |
| **Entidad** | Panel propio: reportes asignados, cambio de estado, perfil, logo, estadísticas y actividad |
| **Administrador** | Usuarios, roles, entidades, noticias, servicios, asignación de reportes y estadísticas globales |

## Tecnologías

| Área | Herramientas |
|---|---|
| Framework | Laravel 12 sobre PHP 8.2 |
| Autenticación | JWT (`tymon/jwt-auth`) con renovación de token, verificación de correo y recuperación de contraseña |
| Base de datos | PostgreSQL 17 (Eloquent ORM y migraciones) |
| Caché, colas y límites | Redis 7 (`phpredis`) |
| Tiempo real | Laravel Reverb (WebSockets) + eventos `ShouldBroadcast` y canales privados autenticados con JWT |
| Correo | SMTP, enviado en segundo plano por el worker de colas |
| Pruebas | PHPUnit (SQLite en memoria) |
| Despliegue | Docker (PHP-FPM + Nginx + Supervisor), Jenkins, Caddy (HTTPS) |

## Arquitectura

```
                      ┌──────────── contenedor backend ────────────┐
frontend (Nginx) ───► │ Nginx :8000 ──► PHP-FPM (Laravel)          │
   /api, /storage     │ Reverb :8080   (WebSockets)                │
   /app (WebSocket)──►│ queue:work     (correos y notificaciones)  │
                      └───────────────┬───────────────┬────────────┘
                                      │ red interna (sin Internet)
                               PostgreSQL 17       Redis 7
```

Un solo contenedor ejecuta, bajo **Supervisor**, los cuatro procesos de la API: Nginx, PHP-FPM, el servidor Reverb y el worker de colas. Al arrancar, aplica las migraciones y, si la base está vacía, crea el catálogo inicial y el administrador. PostgreSQL y Redis viven en una red sin salida a Internet.

## Puesta en marcha

### Opción A · Todo con Docker (recomendada)

Levanta API, frontend, PostgreSQL y Redis con un solo comando, desde el repositorio `Buenaventura-Reporta-Infra`. La guía completa está en la [documentación](https://github.com/jav-19jm/Documentacion-Buenaventura-Reporta/blob/main/docker/GUIA-DE-INSTALACION.md).

### Opción B · Instalación local (desarrollo)

Requisitos: **PHP 8.2** (extensiones `pdo_pgsql`, `redis`, `gd`, `intl`), **Composer 2** y **PostgreSQL**. Redis es opcional en desarrollo.

```bash
git clone https://github.com/jav-19jm/Buenaventura-Reporta-API.git
cd Buenaventura-Reporta-API
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
# Configura DB_* en .env y luego:
php artisan migrate --seed
php artisan storage:link
php artisan serve                 # API en http://localhost:8000/api
```

Para el tiempo real, en otras terminales: `php artisan reverb:start` y `php artisan queue:work`.

La guía detallada (XAMPP, PostgreSQL, usuarios de prueba) está en la [documentación del backend](https://github.com/jav-19jm/Documentacion-Buenaventura-Reporta/tree/main/backend).

## Endpoints principales

Todas las rutas cuelgan de `/api`. La referencia completa, interactiva y con ejemplos, está en el repositorio de [documentación](https://github.com/jav-19jm/Documentacion-Buenaventura-Reporta) (OpenAPI + Scalar).

| Grupo | Rutas | Acceso |
|---|---|---|
| **Autenticación** | `POST auth/login`, `auth/register`, `auth/refresh`, `auth/logout`, `GET auth/me`, recuperación de contraseña y verificación de correo | Público / JWT |
| **Catálogo** | `GET report-categories`, `entities`, `services`, `news` | Público |
| **Reportes** | `GET/POST reports`, `GET/DELETE reports/{id}`, `POST reports/{id}/image`, `POST reports/{id}/votes`, `PATCH reports/{id}/status` | Público (lectura) / JWT |
| **Mensajes** | `GET/POST reports/{id}/messages`, `PATCH/DELETE messages/{id}` | JWT (participantes del reporte) |
| **Usuario** | `GET users/me/reports`, `users/me/notifications`, `POST users/me/avatar`, `GET users/{id}/badges` | JWT |
| **Administración** | `admin/stats`, `admin/users`, `admin/reports`, `admin/entities`, `admin/news`, `admin/services` | Rol `administrador` |
| **Panel de entidad** | `entity`, `entity/reports`, `entity/stats`, `entity/activity`, `entity/logo` | Usuario vinculado a una entidad |
| **Tiempo real** | `POST broadcasting/auth` | JWT |

## Seguridad

- **JWT** en el encabezado `Authorization: Bearer`, con expiración corta y renovación.
- **Middleware `activo`:** una cuenta suspendida pierde el acceso de inmediato, incluso con un token válido.
- **Autorización por rol** (`rol:administrador`, `con_entidad`) y **policy de reportes**: cada usuario solo modifica lo suyo.
- **Límites de peticiones** en login, registro, recuperación de contraseña, creación de reportes, votos y mensajes.
- **Canales privados de WebSocket** autorizados con el mismo JWT.
- **Sin secretos en el código ni en la imagen Docker:** todo entra por variables de entorno.

## Estructura del proyecto

```
app/
├── Console/Commands/   # app:inicializar-datos (siembra solo si la base está vacía)
├── Enums/              # Roles, estados, prioridades y tipos
├── Events/             # Eventos de tiempo real (notificaciones, chat, avisos)
├── Http/
│   ├── Controllers/    # Controladores por área (Admin/, auth, reportes, entidad…)
│   └── Middleware/     # activo, rol, con_entidad
├── Models/             # Eloquent: User, Reporte, Entidad, Mensaje, Notificacion…
├── Notifications/      # Correos (verificación, restablecer contraseña) en cola
├── Policies/           # Reglas de autorización por recurso
├── Services/           # Lógica de negocio: gestión de reportes, votos, insignias y notificaciones
└── Support/            # Caché del catálogo y archivos públicos
database/migrations/    # Esquema de PostgreSQL
routes/api.php          # Rutas de la API
routes/channels.php     # Canales privados de WebSocket
tests/                  # Pruebas de funcionalidad y unitarias (PHPUnit)
docker/                 # Nginx, PHP, Supervisor y script de arranque
Dockerfile              # Imagen multi-etapa: base → deps → test → prod
Jenkinsfile             # Pipeline de CI/CD
```

## Pruebas y CI/CD

```bash
php artisan test                                   # local
docker build --target test -t api:test . && docker run --rm api:test   # en Docker
```

Las pruebas usan SQLite en memoria, así que no tocan ninguna base de datos real. Cubren autenticación, los paneles de ciudadano, entidad y administrador, el flujo completo de un reporte y la autorización de los canales de tiempo real.

Cada push y cada pull request ejecuta en **Jenkins** el pipeline del [`Jenkinsfile`](Jenkinsfile):

| Etapa | Qué hace | Ramas |
|---|---|---|
| **Checkout** | Descarga el commit | Todas |
| **Build** | Imagen `test`: PHP 8.2, extensiones y dependencias de Composer | Todas |
| **Test** | Suite de PHPUnit, con reporte JUnit en Jenkins | Todas |
| **Docker Build** | Imagen de producción sin dependencias de desarrollo, con usuario sin privilegios | `main` |
| **Delivery** | Despliegue con Docker Compose, comprobación de salud y vuelta atrás automática si falla | `main` |

Un merge a `main` publica la nueva versión de la API en producción sin intervención manual.

## Cómo contribuir

Trabajamos con ramas personales que se integran en `develop` y luego en `main`. Lee la **[guía de contribución](CONTRIBUTING.md)** antes de abrir tu primer pull request. Cada PR usa una [plantilla](.github/pull_request_template.md) que se carga automáticamente.

## Equipo

- **Jose Manuel Bonilla Payan**
- **Jesus Eduardo Estupiñan Hernandez**
- **Sebastian Gomez Lerma**

Proyecto académico del curso **Desarrollo de Software II**.
