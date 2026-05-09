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

        // TODO: Implement refresh token logic later

        ApiResponse::success([
            "access_token" => $jwt,
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
        
        if ($token && $payload) {
            $listaNegra = new \App\Models\ListaNegraModel();
            // Agregar a la lista negra con su fecha original de expiración real
            $listaNegra->agregar($token, $payload->exp);
            
            ApiResponse::success([
                "message" => "Sesión cerrada correctamente. El token ha sido añadido a la lista negra."
            ]);
        }
        
        ApiResponse::error("LOGOUT_ERROR", "No se pudo cerrar la sesión", null, 500);
    }
}
