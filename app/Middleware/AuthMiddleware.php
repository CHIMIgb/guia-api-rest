<?php

namespace App\Middleware;

use Flight;
use App\Helpers\ApiResponse;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class AuthMiddleware {
    
    public function before() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            ApiResponse::error("UNAUTHORIZED", "Token de acceso no proporcionado o formato inválido", null, 401);
        }
        
        $token = $matches[1];
        
        $listaNegra = new \App\Models\ListaNegraModel();
        if ($listaNegra->isRevoked($token)) {
            ApiResponse::error("TOKEN_REVOKED", "Este token ha sido invalidado (logout o expirado)", null, 401);
        }

        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            Flight::set('user', $decoded); // Guardamos el usuario en Flight para usarlo en los controladores
            Flight::set('token', $token); // Guardamos también el token original en crudo para el logout
        } catch (\Firebase\JWT\ExpiredException $e) {
            // El usuario pidió que al expirar también se añada a la lista negra automáticamente
            $listaNegra->agregar($token, time());
            ApiResponse::error("TOKEN_EXPIRED", "El token de acceso ha expirado", null, 401);
        } catch (Exception $e) {
            ApiResponse::error("INVALID_TOKEN", "El token de acceso es inválido", null, 401);
        }
    }
}
