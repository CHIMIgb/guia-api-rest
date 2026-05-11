<?php

namespace App\Models;

use PDO;
use Flight;

class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Flight::get('db');
    }

    public function findByUsername(string $username)
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.usuario, u.contrasena, u.activo, p.nombre, p.apellidos, p.sexo 
            FROM usuarios u
            JOIN personas p ON u.persona_id = p.id
            WHERE u.usuario = :username AND u.activo = TRUE
        ");
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }
    public function findById(int $id)
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.usuario, u.contrasena, u.activo, p.nombre, p.apellidos, p.sexo 
            FROM usuarios u
            JOIN personas p ON u.persona_id = p.id
            WHERE u.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function findAnyById(int $id)
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.usuario, u.contrasena, u.activo, p.nombre, p.apellidos, p.sexo 
            FROM usuarios u
            JOIN personas p ON u.persona_id = p.id
            WHERE u.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    public function getUserRoles(int $userId)
    {
        $stmt = $this->db->prepare("
            SELECT r.nombre 
            FROM roles r
            JOIN usuario_roles ur ON r.id = ur.rol_id
            WHERE ur.usuario_id = :userId AND ur.activo = TRUE
        ");
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function roleExists(int $roleId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM roles WHERE id = :id");
        $stmt->execute(['id' => $roleId]);
        return $stmt->fetchColumn() > 0;
    }

    public function create(array $data)
    {
        $this->db->beginTransaction();
        try {
            // Insertar persona
            $stmtPersona = $this->db->prepare("INSERT INTO personas (nombre, apellidos, sexo) VALUES (:nombre, :apellidos, :sexo) RETURNING id");
            $stmtPersona->execute([
                'nombre' => $data['nombre'],
                'apellidos' => $data['apellidos'],
                'sexo' => $data['sexo'] ?? null
            ]);
            $personaId = $stmtPersona->fetchColumn();

            // Insertar usuario
            $stmtUsuario = $this->db->prepare("INSERT INTO usuarios (persona_id, usuario, contrasena) VALUES (:persona_id, :usuario, :contrasena) RETURNING id");
            $stmtUsuario->execute([
                'persona_id' => $personaId,
                'usuario' => $data['usuario'],
                'contrasena' => password_hash($data['contrasena'], PASSWORD_BCRYPT)
            ]);
            $usuarioId = $stmtUsuario->fetchColumn();

            // Insertar rol usando rol_id provisto
            if (isset($data['rol_id'])) {
                $stmtUsuarioRol = $this->db->prepare("INSERT INTO usuario_roles (usuario_id, rol_id) VALUES (:usuario_id, :rol_id)");
                $stmtUsuarioRol->execute([
                    'usuario_id' => $usuarioId,
                    'rol_id' => $data['rol_id']
                ]);
            }

            $this->db->commit();
            return $usuarioId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data)
    {
        $user = $this->findById($id);
        if (!$user)
            return false;

        $this->db->beginTransaction();
        try {
            // Actualizar persona
            $stmtPersona = $this->db->prepare("UPDATE personas SET nombre = :nombre, apellidos = :apellidos, sexo = :sexo WHERE id = (SELECT persona_id FROM usuarios WHERE id = :usuario_id)");
            $stmtPersona->execute([
                'nombre' => $data['nombre'] ?? $user['nombre'],
                'apellidos' => $data['apellidos'] ?? $user['apellidos'],
                'sexo' => $data['sexo'] ?? $user['sexo'],
                'usuario_id' => $id
            ]);

            // Actualizar usuario (usuario o contraseña)
            if (isset($data['usuario']) || isset($data['contrasena'])) {
                $updates = [];
                $params = ['id' => $id];

                if (isset($data['usuario'])) {
                    $updates[] = "usuario = :usuario";
                    $params['usuario'] = $data['usuario'];
                }

                if (isset($data['contrasena'])) {
                    $updates[] = "contrasena = :contrasena";
                    $params['contrasena'] = password_hash($data['contrasena'], PASSWORD_BCRYPT);
                }

                $sql = "UPDATE usuarios SET " . implode(', ', $updates) . " WHERE id = :id";
                $stmtUsuario = $this->db->prepare($sql);
                $stmtUsuario->execute($params);
            }

            // Actualizar rol si se envía rol_id
            if (isset($data['rol_id']) && is_numeric($data['rol_id'])) {
                // Borrar roles anteriores y poner el nuevo (simplificado)
                $this->db->prepare("DELETE FROM usuario_roles WHERE usuario_id = :id")->execute(['id' => $id]);
                $this->db->prepare("INSERT INTO usuario_roles (usuario_id, rol_id) VALUES (:usuario_id, :rol_id)")
                    ->execute(['usuario_id' => $id, 'rol_id' => $data['rol_id']]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateStatus(int $id, bool $activo)
    {
        $this->db->beginTransaction();
        try {
            $val = $activo ? 'true' : 'false';

            $stmt1 = $this->db->prepare("UPDATE usuarios SET activo = :activo WHERE id = :id");
            $stmt1->execute(['activo' => $val, 'id' => $id]);

            $stmt2 = $this->db->prepare("UPDATE personas SET activo = :activo WHERE id = (SELECT persona_id FROM usuarios WHERE id = :id)");
            $stmt2->execute(['activo' => $val, 'id' => $id]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id)
    {
        // Soft delete: no borramos de la BD, solo desactivamos el usuario y su persona.
        $this->db->beginTransaction();
        try {
            $stmt1 = $this->db->prepare("UPDATE usuarios SET activo = false WHERE id = :id");
            $stmt1->execute(['id' => $id]);

            $stmt2 = $this->db->prepare("UPDATE personas SET activo = false WHERE id = (SELECT persona_id FROM usuarios WHERE id = :id)");
            $stmt2->execute(['id' => $id]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
