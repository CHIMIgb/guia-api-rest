<?php

namespace App\Controllers;

use Flight;
use App\Models\UserModel;
use App\Helpers\ApiResponse;

class UserController {
    
    public function index() {
        // Implementación básica para listar usuarios (con paginación recomendada)
        $db = Flight::get('db');
        $stmt = $db->query("
            SELECT u.id, u.usuario, u.activo, p.nombre, p.apellidos, p.sexo 
            FROM usuarios u
            JOIN personas p ON u.persona_id = p.id
        ");
        $users = $stmt->fetchAll();
        
        ApiResponse::success(['items' => $users]);
    }
    
    public function show(int $id) {
        $db = Flight::get('db');
        $stmt = $db->prepare("
            SELECT u.id, u.usuario, u.activo, p.nombre, p.apellidos, p.sexo 
            FROM usuarios u
            JOIN personas p ON u.persona_id = p.id
            WHERE u.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            ApiResponse::error("RESOURCE_NOT_FOUND", "El usuario con ID $id no existe", null, 404);
        }
        
        $userModel = new UserModel();
        $user['roles'] = $userModel->getUserRoles($id);
        
        ApiResponse::success($user);
    }
}
