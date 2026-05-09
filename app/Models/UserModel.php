<?php

namespace App\Models;

use PDO;
use Flight;

class UserModel {
    private PDO $db;

    public function __construct() {
        $this->db = Flight::get('db');
    }

    public function findByUsername(string $username) {
        $stmt = $this->db->prepare("
            SELECT u.id, u.usuario, u.contrasena, u.activo, p.nombre, p.apellidos, p.sexo 
            FROM usuarios u
            JOIN personas p ON u.persona_id = p.id
            WHERE u.usuario = :username AND u.activo = TRUE
        ");
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }

    public function getUserRoles(int $userId) {
        $stmt = $this->db->prepare("
            SELECT r.nombre 
            FROM roles r
            JOIN usuario_roles ur ON r.id = ur.rol_id
            WHERE ur.usuario_id = :userId AND ur.activo = TRUE
        ");
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
