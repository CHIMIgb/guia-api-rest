-- Función genérica para actualizar el campo hora_edicion automáticamente
CREATE OR REPLACE FUNCTION actualizar_hora_edicion()
RETURNS TRIGGER AS $$
BEGIN
   NEW.hora_edicion = CURRENT_TIMESTAMP;
   RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TABLE personas (
  id              SERIAL PRIMARY KEY,
  nombre          VARCHAR(100) NOT NULL,
  apellidos       VARCHAR(150) NOT NULL,
  sexo            VARCHAR(20) NULL,
  activo          BOOLEAN DEFAULT TRUE,
  hora_registro   TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  hora_edicion    TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER trg_personas_edicion
BEFORE UPDATE ON personas
FOR EACH ROW EXECUTE FUNCTION actualizar_hora_edicion();

CREATE TABLE usuarios (
  id              SERIAL PRIMARY KEY,
  persona_id      INTEGER NOT NULL,
  usuario         VARCHAR(100) NOT NULL UNIQUE,
  contrasena      VARCHAR(255) NOT NULL,
  activo          BOOLEAN DEFAULT TRUE,
  hora_registro   TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  hora_edicion    TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuarios_persona FOREIGN KEY (persona_id) REFERENCES personas (id) ON DELETE CASCADE
);

CREATE TRIGGER trg_usuarios_edicion
BEFORE UPDATE ON usuarios
FOR EACH ROW EXECUTE FUNCTION actualizar_hora_edicion();

CREATE TABLE roles (
  id              SERIAL PRIMARY KEY,
  nombre          VARCHAR(50) NOT NULL UNIQUE,
  descripcion     VARCHAR(255) NULL,
  activo          BOOLEAN DEFAULT TRUE,
  hora_registro   TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  hora_edicion    TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER trg_roles_edicion
BEFORE UPDATE ON roles
FOR EACH ROW EXECUTE FUNCTION actualizar_hora_edicion();

CREATE TABLE usuario_roles (
  id              SERIAL PRIMARY KEY,
  usuario_id      INTEGER NOT NULL,
  rol_id          INTEGER NOT NULL,
  activo          BOOLEAN DEFAULT TRUE,
  hora_registro   TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  hora_edicion    TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (usuario_id, rol_id),
  CONSTRAINT fk_ur_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
  CONSTRAINT fk_ur_rol FOREIGN KEY (rol_id) REFERENCES roles (id) ON DELETE CASCADE
);

CREATE TRIGGER trg_usuario_roles_edicion
BEFORE UPDATE ON usuario_roles
FOR EACH ROW EXECUTE FUNCTION actualizar_hora_edicion();

CREATE TABLE tokens_actualizacion (
  id               SERIAL PRIMARY KEY,
  usuario_id       INTEGER NOT NULL,
  token            VARCHAR(512) NOT NULL UNIQUE,
  fecha_expiracion TIMESTAMP WITH TIME ZONE NOT NULL,
  fecha_revocacion TIMESTAMP WITH TIME ZONE NULL DEFAULT NULL,
  activo           BOOLEAN DEFAULT TRUE,
  hora_registro    TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  hora_edicion     TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tokens_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
);

CREATE TRIGGER trg_tokens_actualizacion_edicion
BEFORE UPDATE ON tokens_actualizacion
FOR EACH ROW EXECUTE FUNCTION actualizar_hora_edicion();

CREATE TABLE permisos (
  id              SERIAL PRIMARY KEY,
  nombre          VARCHAR(100) NOT NULL UNIQUE,
  descripcion     VARCHAR(255) NULL,
  activo          BOOLEAN DEFAULT TRUE,
  hora_registro   TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  hora_edicion    TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER trg_permisos_edicion
BEFORE UPDATE ON permisos
FOR EACH ROW EXECUTE FUNCTION actualizar_hora_edicion();

CREATE TABLE rol_permisos (
  id              SERIAL PRIMARY KEY,
  rol_id          INTEGER NOT NULL,
  permiso_id      INTEGER NOT NULL,
  activo          BOOLEAN DEFAULT TRUE,
  hora_registro   TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  hora_edicion    TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (rol_id, permiso_id),
  CONSTRAINT fk_rp_rol FOREIGN KEY (rol_id) REFERENCES roles (id) ON DELETE CASCADE,
  CONSTRAINT fk_rp_permiso FOREIGN KEY (permiso_id) REFERENCES permisos (id) ON DELETE CASCADE
);

CREATE TRIGGER trg_rol_permisos_edicion
BEFORE UPDATE ON rol_permisos
FOR EACH ROW EXECUTE FUNCTION actualizar_hora_edicion();

-- Datos de prueba (seeds)
INSERT INTO roles (nombre, descripcion) VALUES
  ('admin', 'Administrador principal del sistema'),
  ('editor', 'Editor con permisos limitados'),
  ('viewer', 'Solo lectura');

INSERT INTO personas (nombre, apellidos, sexo) VALUES
  ('Juan', 'Pérez', 'Masculino'),
  ('María', 'Gómez', 'Femenino'),
  ('Carlos', 'López', 'Masculino');

-- Contraseña para todos los seeds: 'secret123' (hash bcrypt)
INSERT INTO usuarios (persona_id, usuario, contrasena) VALUES
  (1, 'admin_juan', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
  (2, 'editor_maria', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
  (3, 'viewer_carlos', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO usuario_roles (usuario_id, rol_id) VALUES
  (1, 1), -- Juan es Admin
  (2, 2), -- Maria es Editor
  (3, 3); -- Carlos es Viewer
