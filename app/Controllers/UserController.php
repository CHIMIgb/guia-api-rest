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
            WHERE u.activo = TRUE
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
            WHERE u.id = :id AND u.activo = TRUE
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

    private function requireRole(array $allowedRoles) {
        $userPayload = Flight::get('user');
        $userRoles = $userPayload->user->roles ?? [];
        
        $hasRole = false;
        foreach ($allowedRoles as $role) {
            if (in_array($role, $userRoles)) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            ApiResponse::error("FORBIDDEN", "No tienes permisos suficientes para realizar esta acción", null, 403);
        }
    }

    private function canEditUser(int $targetId) {
        $userPayload = Flight::get('user');
        $userRoles = $userPayload->user->roles ?? [];
        $currentUserId = $userPayload->sub;

        if (in_array('admin', $userRoles)) {
            return true; // Admin puede editar a todos
        }

        if (in_array('editor', $userRoles) && $currentUserId == $targetId) {
            return true; // Editor solo puede editar su propio perfil
        }

        return false;
    }

    public function create() {
        $this->requireRole(['admin']); // Solo admin puede crear usuarios directamente

        $data = Flight::request()->data->getData();
        
        $requiredFields = ['usuario', 'contrasena', 'nombre', 'apellidos', 'sexo', 'rol_id'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            ApiResponse::error("VALIDATION_ERROR", "Faltan campos requeridos", ["campos_faltantes" => $missingFields], 422);
        }

        if (!is_numeric($data['rol_id'])) {
            ApiResponse::error("VALIDATION_ERROR", "El campo 'rol_id' debe ser un número", null, 422);
        }

        $userModel = new UserModel();

        if (!$userModel->roleExists((int)$data['rol_id'])) {
            ApiResponse::error("VALIDATION_ERROR", "El rol especificado no existe", null, 422);
        }
        
        // Verificar si el usuario ya existe
        if ($userModel->findByUsername($data['usuario'])) {
            ApiResponse::error("RESOURCE_ALREADY_EXISTS", "El nombre de usuario ya está registrado", null, 409);
        }

        try {
            $newId = $userModel->create($data);
            $newUser = $userModel->findById($newId);
            $newUser['roles'] = $userModel->getUserRoles($newId);
            
            // Eliminar datos sensibles o innecesarios de la respuesta
            unset($newUser['contrasena']);
            unset($newUser['id']);

            ApiResponse::success($newUser, 201);
        } catch (\Exception $e) {
            ApiResponse::error("INTERNAL_ERROR", "Error al crear el usuario", $e->getMessage(), 500);
        }
    }

    public function update(int $id) {
        if (!$this->canEditUser($id)) {
            ApiResponse::error("FORBIDDEN", "No tienes permisos para editar a este usuario", null, 403);
        }

        $data = Flight::request()->data->getData();
        
        $userModel = new UserModel();
        $user = $userModel->findById($id);

        if (!$user) {
            ApiResponse::error("RESOURCE_NOT_FOUND", "El usuario no existe", null, 404);
        }

        // Si es PUT, idealmente se validaría que vengan todos los campos. 
        // Si es PATCH, solo los enviados. Aquí reutilizamos `update` del modelo que hace un update parcial/total.

        if (isset($data['usuario']) && $data['usuario'] !== $user['usuario']) {
            if ($userModel->findByUsername($data['usuario'])) {
                ApiResponse::error("RESOURCE_ALREADY_EXISTS", "El nombre de usuario ya está en uso por otra persona", null, 409);
            }
        }

        if (isset($data['rol_id'])) {
            if (!is_numeric($data['rol_id'])) {
                ApiResponse::error("VALIDATION_ERROR", "El campo 'rol_id' debe ser un número", null, 422);
            }
            if (!$userModel->roleExists((int)$data['rol_id'])) {
                ApiResponse::error("VALIDATION_ERROR", "El rol especificado no existe", null, 422);
            }
        }

        try {
            $userModel->update($id, $data);
            $updatedUser = $userModel->findById($id);
            $updatedUser['roles'] = $userModel->getUserRoles($id);
            
            unset($updatedUser['contrasena']);
            
            ApiResponse::success($updatedUser);
        } catch (\Exception $e) {
            ApiResponse::error("INTERNAL_ERROR", "Error al actualizar el usuario", $e->getMessage(), 500);
        }
    }

    public function updateStatus(int $id) {
        $this->requireRole(['admin']);

        $data = Flight::request()->data->getData();
        if (!isset($data['activo'])) {
            ApiResponse::error("VALIDATION_ERROR", "El campo 'activo' (booleano) es requerido", null, 422);
        }

        $userModel = new UserModel();
        if (!$userModel->findAnyById($id)) {
            ApiResponse::error("RESOURCE_NOT_FOUND", "El usuario no existe", null, 404);
        }

        try {
            $userModel->updateStatus($id, (bool) $data['activo']);
            ApiResponse::success(["message" => "Estado actualizado correctamente"]);
        } catch (\Exception $e) {
            ApiResponse::error("INTERNAL_ERROR", "Error al cambiar el estado del usuario", $e->getMessage(), 500);
        }
    }

    public function delete(int $id) {
        $this->requireRole(['admin']);

        $userModel = new UserModel();
        if (!$userModel->findAnyById($id)) {
            ApiResponse::error("RESOURCE_NOT_FOUND", "El usuario no existe", null, 404);
        }

        try {
            $userModel->delete($id);
            ApiResponse::success(["message" => "Usuario eliminado correctamente"]);
        } catch (\Exception $e) {
            ApiResponse::error("INTERNAL_ERROR", "Error al eliminar el usuario", $e->getMessage(), 500);
        }
    }
}
