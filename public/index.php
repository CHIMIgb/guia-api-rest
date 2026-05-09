<?php
// public/index.php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Helpers\ApiResponse;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Configurar conexión a BD
require __DIR__ . '/../config/database.php';

// Configuración global de Flight
Flight::map('notFound', function() {
    ApiResponse::error('RESOURCE_NOT_FOUND', 'Ruta no encontrada', null, 404);
});

Flight::map('error', function(\Throwable $ex) {
    ApiResponse::error('INTERNAL_ERROR', 'Error interno del servidor', $ex->getMessage(), 500);
});

// Middleware Global (CORS y cabeceras JSON)
Flight::before('start', function() {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    
    if (Flight::request()->method === 'OPTIONS') {
        exit;
    }
});

// Registrar Rutas
require __DIR__ . '/../routes/auth.php';
require __DIR__ . '/../routes/users.php';

// Iniciar aplicación
Flight::start();
