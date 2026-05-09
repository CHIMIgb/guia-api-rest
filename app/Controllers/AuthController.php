<?php

namespace App\Controllers;

use Flight;
use App\Models\UserModel;
use App\Helpers\ApiResponse;
use Firebase\JWT\JWT;
use Exception;

class AuthController
{

    public function login()
    {
        $data = Flight::request()->data;

        $usuario = $data->usuario ?? null;
        $contrasena = $data->contrasena ?? null;

        if (!$usuario || !$contrasena) {
            ApiResponse::error("VALIDATION_ERROR", "Usuario y contraseña son requeridos", null, 422);
        }

        $userModel = new UserModel();
        $user = $userModel->findByUsername($usuario);

        if (!$user || !password_verify($contrasena, $user['contrasena'])) {
            ApiResponse::error("INVALID_CREDENTIALS", "Usuario o contraseña incorrectos", null, 401);
        }

        $roles = $userModel->getUserRoles($user['id']);

        $payload = [
            "sub" => $user['id'],
            "user" => [
                "id" => $user['id'],
                "usuario" => $user['usuario'],
                "nombre" => $user['nombre'],
                "apellidos" => $user['apellidos'],
                "roles" => $roles
            ],
            "iat" => time(),
            "exp" => time() + $_ENV['JWT_ACCESS_EXPIRATION']
        ];

        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        // Generar Refresh Token Opaco (Aleatorio y seguro)
        $refreshTokenString = bin2hex(random_bytes(32));
        $refreshTokenModel = new \App\Models\RefreshTokenModel();
        
        // Revocar tokens anteriores por seguridad (opcional, pero buena práctica)
        $refreshTokenModel->revokeAllForUser($user['id']);
        
        // Guardar en la base de datos
        $refreshTokenModel->create($user['id'], $refreshTokenString, $_ENV['JWT_REFRESH_EXPIRATION']);

        ApiResponse::success([
            "access_token" => $jwt,
            "refresh_token" => $refreshTokenString,
            "user" => [
                "id" => $user['id'],
                "usuario" => $user['usuario'],
                "nombre" => $user['nombre'],
                "apellidos" => $user['apellidos'],
                "roles" => $roles
            ]
        ]);
    }

    public function validateToken()
    {
        // Obtenemos el payload decodificado que el AuthMiddleware guardó
        $payload = Flight::get('user');

        $currentTime = time();
        $expirationTime = $payload->exp ?? 0;
        $timeLeftSeconds = $expirationTime - $currentTime;

        ApiResponse::success([
            "expires_in_seconds" => $timeLeftSeconds,
            "payload" => $payload
        ]);
    }

    public function logout() {
        $token = Flight::get('token');
        $payload = Flight::get('user');
        
        // El access token actual va a la lista negra
        if ($token && $payload) {
            $listaNegra = new \App\Models\ListaNegraModel();
            $listaNegra->agregar($token, $payload->exp);
            
            // También revocamos todos sus refresh tokens de la base de datos
            $refreshTokenModel = new \App\Models\RefreshTokenModel();
            $refreshTokenModel->revokeAllForUser($payload->sub);
            
            ApiResponse::success([
                "message" => "Sesión cerrada correctamente. Access Token invalidado y Refresh Tokens revocados."
            ]);
        }
        
        ApiResponse::error("LOGOUT_ERROR", "No se pudo cerrar la sesión", null, 500);
    }

    public function refreshToken() {
        // En este patrón, ya NO leemos el Access Token viejo del middleware.
        // Esperamos recibir el "refresh_token" en el cuerpo de la petición.
        $data = Flight::request()->data;
        $providedRefreshToken = $data->refresh_token ?? null;

        if (!$providedRefreshToken) {
            ApiResponse::error("VALIDATION_ERROR", "El refresh_token es requerido", null, 422);
        }

        $refreshTokenModel = new \App\Models\RefreshTokenModel();
        $storedToken = $refreshTokenModel->findByToken($providedRefreshToken);

        if (!$storedToken) {
            ApiResponse::error("INVALID_REFRESH_TOKEN", "El refresh token es inválido, ha expirado o fue revocado", null, 401);
        }

        // Obtener datos del usuario para el nuevo Access Token
        $userModel = new UserModel();
        $user = $userModel->findById($storedToken['usuario_id']); // Necesitamos un método findById o simularlo

        if (!$user) {
            ApiResponse::error("USER_NOT_FOUND", "El usuario asociado a este token ya no existe", null, 404);
        }

        $roles = $userModel->getUserRoles($user['id']);

        // 1. Revocar el Refresh Token que acaban de usar (Rotación de Refresh Tokens para mayor seguridad)
        $refreshTokenModel->revoke($providedRefreshToken);

        // 2. Crear un NUEVO Refresh Token
        $newRefreshTokenString = bin2hex(random_bytes(32));
        $refreshTokenModel->create($user['id'], $newRefreshTokenString, $_ENV['JWT_REFRESH_EXPIRATION']);

        // 3. Crear el NUEVO Access Token
        $newPayload = [
            "sub" => $user['id'],
            "user" => [
                "id" => $user['id'],
                "usuario" => $user['usuario'],
                "nombre" => $user['nombre'],
                "apellidos" => $user['apellidos'],
                "roles" => $roles
            ],
            "iat" => time(),
            "exp" => time() + $_ENV['JWT_ACCESS_EXPIRATION']
        ];

        $newJwt = JWT::encode($newPayload, $_ENV['JWT_SECRET'], 'HS256');

        ApiResponse::success([
            "message" => "Tokens refrescados correctamente",
            "access_token" => $newJwt,
            "refresh_token" => $newRefreshTokenString,
            "user" => $newPayload['user']
        ]);
    }
}
