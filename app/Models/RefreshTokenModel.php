<?php

namespace App\Models;

use PDO;
use Flight;

class RefreshTokenModel {
    private PDO $db;

    public function __construct() {
        $this->db = Flight::get('db');
    }

    public function create(int $usuario_id, string $token, int $expires_in_seconds) {
        $stmt = $this->db->prepare("
            INSERT INTO tokens_actualizacion (usuario_id, token, fecha_expiracion) 
            VALUES (:usuario_id, :token, CURRENT_TIMESTAMP + (:expires_in || ' seconds')::interval)
        ");
        $stmt->execute([
            'usuario_id' => $usuario_id,
            'token' => $token,
            'expires_in' => $expires_in_seconds
        ]);
    }

    public function findByToken(string $token) {
        $stmt = $this->db->prepare("
            SELECT * FROM tokens_actualizacion 
            WHERE token = :token 
            AND activo = true 
            AND fecha_revocacion IS NULL 
            AND fecha_expiracion > CURRENT_TIMESTAMP
        ");
        $stmt->execute(['token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function revoke(string $token) {
        $stmt = $this->db->prepare("
            UPDATE tokens_actualizacion 
            SET activo = false, fecha_revocacion = CURRENT_TIMESTAMP 
            WHERE token = :token
        ");
        $stmt->execute(['token' => $token]);
    }
    
    public function revokeAllForUser(int $usuario_id) {
        $stmt = $this->db->prepare("
            UPDATE tokens_actualizacion 
            SET activo = false, fecha_revocacion = CURRENT_TIMESTAMP 
            WHERE usuario_id = :usuario_id AND activo = true
        ");
        $stmt->execute(['usuario_id' => $usuario_id]);
    }
}
