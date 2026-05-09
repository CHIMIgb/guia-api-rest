# Guía API REST con PHP Flight y PostgreSQL

Este proyecto es una API REST base construida con [PHP Flight Framework](https://flightphp.com/), utilizando una arquitectura MVC, conexión a base de datos en PostgreSQL mediante PDO y un diseño preparado para autenticación con JSON Web Tokens (JWT).

## 📋 Requisitos

Para ejecutar este proyecto necesitas tener instalado localmente:
- **PHP 8.1** o superior.
- **Composer** (Gestor de dependencias de PHP).
- **PostgreSQL** (Servidor de base de datos).

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
Abre el archivo `.env` recién creado y actualiza tus credenciales de PostgreSQL (`DB_USER`, `DB_PASS`, `DB_NAME`, etc.) y asigna una cadena aleatoria al `JWT_SECRET`.

### 3. Preparar la Base de Datos
Crea una base de datos en PostgreSQL y posteriormente importa el esquema del proyecto (el cual ya incluye tablas y datos semilla de prueba):
```bash
# Opcional (por consola):
psql -U postgres -d tu_nombre_bd -f database/schema.sql
```

### 4. Iniciar el servidor
Levanta el proyecto utilizando el servidor integrado de PHP. Todo el tráfico debe apuntar a la carpeta `public`:
```bash
php -S localhost:8080 -t public/
```
Tu API ahora estará corriendo en `http://localhost:8080`. Para probar que todo funciona, realiza una petición `GET` a:
- `http://localhost:8080/api/v1/auth/test`

## 📁 Estructura del Proyecto

El proyecto se diseñó de forma escalable separando responsabilidades:

```text
guia-api-rest/
├── app/
│   ├── Controllers/     # Clases que reciben la petición HTTP, validan y llaman a los modelos
│   ├── Helpers/         # Clases de apoyo global (ej. ApiResponse para estandarizar JSON)
│   ├── Middleware/      # Filtros intermedios (ej. verificación de JWT antes del Controller)
│   └── Models/          # Capa de datos, encargada de realizar las consultas SQL a PostgreSQL
├── config/              # Inicializaciones globales (PDO, configuración JWT)
├── database/            # Scripts, diagramas o esquemas SQL (.sql)
├── public/
│   └── index.php        # Entry Point principal que recibe y redirige todas las peticiones
├── routes/              # Definición de URLs separadas por módulo (auth, users, etc.)
├── .env.example         # Archivo de muestra de las variables de entorno necesarias
└── composer.json        # Declaración de dependencias (flightphp, firebase/php-jwt, dotenv)
```

## 🛠 Dependencias Base
* **Flight PHP** (`flightphp/core`): Microframework minimalista para control de rutas.
* **Dotenv** (`vlucas/phpdotenv`): Carga segura de variables de entorno desde el archivo `.env`.
* **PHP-JWT** (`firebase/php-jwt`): Codificación y decodificación de tokens de sesión.
