# Guía API REST Boilerplate (PHP Flight + PostgreSQL)

Este proyecto es una API REST moderna y robusta, construida para servir como **Plantilla Base (Boilerplate)** y material educativo. Utiliza [PHP Flight Framework](https://flightphp.com/), arquitectura estricta MVC, y está orientada a resolver problemas empresariales reales usando buenas prácticas de la industria.

## ✨ Características Principales (Lo que aprenderás / usarás)

- **Arquitectura MVC Limpia:** Separación estricta de Rutas, Controladores, Modelos y Middleware. Uso de Autoloader PSR-4.
- **Autenticación Doble Token (OAuth2-style):**
  - **Access Token (JWT):** De corta duración para acceder a rutas protegidas.
  - **Refresh Token:** Cadena opaca de larga duración, almacenada en Base de Datos, con **rotación de tokens** y soporte para **revocación/lista negra** (Blacklisting).
- **CRUD de Usuarios Empresarial:**
  - Implementación completa de métodos HTTP (`GET`, `POST`, `PUT`, `PATCH`, `DELETE`).
  - Uso del patrón **Soft Delete** (desactivación lógica).
  - Sincronización de múltiples tablas (`personas`, `usuarios`, `usuario_roles`) mediante **Transacciones SQL** (`BEGIN`, `COMMIT`, `ROLLBACK`).
- **Seguridad y Autorización:**
  - Control de acceso por Roles (`admin`, `editor`, `viewer`).
  - Prevención de fugas de datos sensibles (ocultamiento de hashes y PII).
  - Consultas seguras previniendo Inyección SQL (PDO Prepared Statements).
- **Estandarización JSON:** Respuesta universal manejada por el helper `ApiResponse` (`{success, data, error}`).

---

## 📋 Requisitos

Para ejecutar este proyecto necesitas tener instalado localmente:
- **PHP 8.1** o superior.
- **Composer** (Gestor de dependencias de PHP).
- **PostgreSQL** (Servidor de base de datos).

---

## 🚀 Instalación y Ejecución

Sigue estos pasos para levantar el entorno de desarrollo:

### 1. Instalar las dependencias
Asegúrate de estar en la raíz del proyecto y ejecuta el siguiente comando para descargar las librerías necesarias:
```bash
composer install
```

### 2. Configurar el entorno
Copia la plantilla `.env.example` y renómbrala a `.env` (si estás en Windows, puedes hacerlo manual o con el siguiente comando):
```bash
cp .env.example .env
```
Abre el archivo `.env` recién creado y actualiza:
- Tus credenciales de PostgreSQL (`DB_USER`, `DB_PASS`, `DB_NAME`, etc.).
- Las variables de seguridad `JWT_SECRET` (una frase larga y segura), `JWT_ACCESS_EXPIRATION` (ej. 3600) y `JWT_REFRESH_EXPIRATION` (ej. 604800).

### 3. Preparar la Base de Datos
Crea una base de datos en PostgreSQL y posteriormente importa el esquema del proyecto (el cual ya incluye tablas, triggers y datos semilla de prueba):
```bash
# Opcional (por consola):
psql -U postgres -d tu_nombre_bd -f database/schema.sql
```

### 4. Iniciar el servidor
Levanta el proyecto utilizando el servidor integrado de PHP. Todo el tráfico debe apuntar a la carpeta `public`:
```bash
php -S localhost:8080 -t public/
```
Tu API ahora estará corriendo en `http://localhost:8080`.

---

## 🧪 Cómo probar la API (Endpoints principales)

Para facilitar las pruebas y el aprendizaje, se ha incluido en la raíz del proyecto una colección de Postman. 
Simplemente abre tu Postman, selecciona **"Import"** y carga el archivo `guia-api-rest.postman_collection.json`. Tendrás todos los endpoints listos para probar con variables de entorno preconfiguradas.

Si prefieres probar con cURL, Insomnia u otras herramientas, estos son los endpoints principales:

1. **Login:** `POST /api/v1/auth/login` (Obtienes access_token y refresh_token).
2. **Refrescar Token:** `POST /api/v1/auth/refresh` (Envías tu refresh token vigente).
3. **Validar Sesión:** `GET /api/v1/auth/validate` (Requiere Header: `Authorization: Bearer <access_token>`).
4. **Listar Usuarios:** `GET /api/v1/users` (Solo usuarios activos).
5. **Crear Usuario:** `POST /api/v1/users` (Requiere Rol Admin).
6. **Activar/Desactivar Usuario (Soft Delete):** `PATCH /api/v1/users/{id}/status` (Cambia el estado tanto en la tabla `usuarios` como `personas`).

---

## 📁 Estructura del Proyecto

El proyecto se diseñó de forma escalable separando responsabilidades:

```text
guia-api-rest/
├── app/
│   ├── Controllers/     # Lógica de negocio HTTP y autorización (User, Auth)
│   ├── Helpers/         # Clases de apoyo global (ApiResponse para estandarizar JSON)
│   ├── Middleware/      # Filtros intermedios (AuthMiddleware para validar JWT y lista negra)
│   └── Models/          # Capa de datos y Transacciones SQL (UserModel, RefreshTokenModel)
├── config/              # Inicializaciones globales (PDO, configuración JWT)
├── database/            # Script de inicialización (Tablas, Triggers, Semillas)
├── public/
│   └── index.php        # Entry Point principal que recibe y redirige todas las peticiones
├── routes/              # Definición de URLs separadas por módulo (auth.php, users.php)
├── scratch/             # Scripts de pruebas temporales / herramientas dev
├── .env.example         # Plantilla de variables de entorno
└── composer.json        # Declaración de dependencias y PSR-4 Autoload
```

## 🛠 Dependencias Base
* **Flight PHP** (`flightphp/core`): Microframework minimalista para control de rutas rápidas.
* **Dotenv** (`vlucas/phpdotenv`): Carga segura de variables de entorno.
* **PHP-JWT** (`firebase/php-jwt`): Implementación segura de JSON Web Tokens.
