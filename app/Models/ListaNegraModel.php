<?php

namespace App\Models;

use PDO;
use Flight;

class ListaNegraModel {
    private PDO $db;

    public function __construct() {
        $this->db = Flight::get('db');
    }

    public function agregar(string $token, int $fecha_expiracion) {
        $stmt = $this->db->prepare("
            INSERT INTO lista_negra (token, fecha_expiracion) 
            VALUES (:token, to_timestamp(:fecha_expiracion))
            ON CONFLICT (token) DO NOTHING
        ");
        $stmt->execute([
            'token' => $token,
            'fecha_expiracion' => $fecha_expiracion
        ]);
    }

    public function isRevoked(string $token): bool {
        $stmt = $this->db->prepare("SELECT id FROM lista_negra WHERE token = :token");
        $stmt->execute(['token' => $token]);
        return $stmt->fetch() !== false;
    }
}
