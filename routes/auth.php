<?php
use App\Helpers\ApiResponse;

use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;

$authController = new AuthController();
$apiPrefix = $_ENV['API_PREFIX'] ?? '/api/v1';

Flight::group($apiPrefix . '/auth', function() use ($authController) {
    Flight::route('POST /login', [$authController, 'login']);
    
    // Ruta protegida de prueba para verificar AuthMiddleware
    Flight::route('GET /me', function() {
        $user = Flight::get('user');
        ApiResponse::success(['user' => $user]);
    })->addMiddleware([new AuthMiddleware()]);

    // Ruta para validar token explícitamente y ver expiración
    Flight::route('GET /validate', [$authController, 'validateToken'])->addMiddleware([new AuthMiddleware()]);

    // Ruta para hacer logout e invalidar el token
    Flight::route('POST /logout', [$authController, 'logout'])->addMiddleware([new AuthMiddleware()]);

    // Ruta para refrescar el token (ahora es "pública" porque usa el refresh_token del body)
    Flight::route('POST /refresh', [$authController, 'refreshToken']);

    // Ruta temporal para probar el interceptor de errores
    Flight::route('GET /forzar-error', function() {
        throw new \PDOException("Mensaje técnico feo de SQL que el usuario no debe ver", 23505);
    });
});
