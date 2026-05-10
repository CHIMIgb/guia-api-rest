<?php

namespace App\Helpers;

use Flight;
use Throwable;
use PDOException;
use App\Helpers\ApiResponse;
use App\Helpers\ErrorDictionary;

class ErrorHandler {
    
    /**
     * Registra los interceptores globales en Flight PHP.
     */
    public static function register() {
        
        // Interceptar errores 404 (Rutas no encontradas)
        Flight::map('notFound', function() {
            ApiResponse::error(
                "NOT_FOUND", 
                "El endpoint solicitado no existe o el método HTTP es incorrecto.", 
                null, 
                404
            );
        });

        // Interceptar excepciones y errores fatales (500, etc.)
        Flight::map('error', function(Throwable $ex) {
            
            $code = "INTERNAL_ERROR";
            $message = "Ocurrió un error interno en el servidor.";
            $statusCode = 500;
            $details = null;

            // Si estamos en un entorno de desarrollo, mostramos detalles del error original
            $isDevelopment = ($_ENV['APP_ENV'] ?? 'development') === 'development';
            if ($isDevelopment) {
                $details = [
                    'file' => $ex->getFile(),
                    'line' => $ex->getLine(),
                    'message' => $ex->getMessage()
                ];
            }

            // Si el error es de base de datos (PDO), usamos el Diccionario
            if ($ex instanceof PDOException) {
                $code = "DATABASE_ERROR";
                // Los códigos de error SQLSTATE se pueden extraer del mensaje o del método getCode()
                $sqlStateCode = $ex->getCode();
                
                // Algunos drivers envían el SQLSTATE dentro del mensaje, como en Postgres
                // Intentaremos extraer un posible SQLSTATE (5 caracteres) del mensaje si getCode() no es el esperado
                if (is_numeric($sqlStateCode) && preg_match('/SQLSTATE\[([0-9A-Z]{5})\]/', $ex->getMessage(), $matches)) {
                    $sqlStateCode = $matches[1];
                }

                $message = ErrorDictionary::translate((string) $sqlStateCode);
                
                // Podríamos usar 409 Conflict para duplicados o llaves foráneas, 
                // o dejarlo en 500 como error interno si es algo inesperado.
                if ($sqlStateCode === '23505' || $sqlStateCode === '23503') {
                    $statusCode = 409;
                }
            }

            ApiResponse::error($code, $message, $details, $statusCode);
        });
    }
}
