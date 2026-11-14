CREATE DATABASE IF NOT EXISTS suppify
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE suppify;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS seguimientos;
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS roles;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Tabla 23: roles
-- ---------------------------------------------------------------------
CREATE TABLE roles (
    id_rol       INT          NOT NULL AUTO_INCREMENT,
    nombre       VARCHAR(30)  NOT NULL,
    descripcion  VARCHAR(150) NULL,
    PRIMARY KEY (id_rol),
    UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabla 24: usuarios
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id_usuario     INT          NOT NULL AUTO_INCREMENT,
    cedula         VARCHAR(20)  NOT NULL,
    nombre         VARCHAR(100) NOT NULL,
    correo         VARCHAR(100) NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    id_rol         INT          NOT NULL,
    activo         TINYINT(1)   NOT NULL DEFAULT 1,
    fecha_creacion DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_usuarios_cedula (cedula),
    UNIQUE KEY uq_usuarios_correo (correo),
    KEY idx_usuarios_rol (id_rol),
    CONSTRAINT fk_usuarios_roles
        FOREIGN KEY (id_rol) REFERENCES roles (id_rol)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabla 25: tickets
-- ---------------------------------------------------------------------
CREATE TABLE tickets (
    id_ticket           INT          NOT NULL AUTO_INCREMENT,
    id_empleado         INT          NOT NULL,
    id_tecnico          INT          NULL,
    titulo              VARCHAR(150) NOT NULL,
    descripcion         TEXT         NOT NULL,
    estado              ENUM('pendiente','en_proceso','resuelto') NOT NULL DEFAULT 'pendiente',
    fecha_creacion      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    fecha_resolucion    DATETIME     NULL DEFAULT NULL,
    PRIMARY KEY (id_ticket),
    KEY idx_tickets_empleado (id_empleado),
    KEY idx_tickets_tecnico (id_tecnico),
    KEY idx_tickets_estado (estado),
    CONSTRAINT fk_tickets_empleado
        FOREIGN KEY (id_empleado) REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_tickets_tecnico
        FOREIGN KEY (id_tecnico) REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabla 26: seguimientos
-- ---------------------------------------------------------------------
CREATE TABLE seguimientos (
    id_seguimiento INT      NOT NULL AUTO_INCREMENT,
    id_ticket      INT      NOT NULL,
    id_tecnico     INT      NOT NULL,
    comentario     TEXT     NOT NULL,
    estado         ENUM('pendiente','en_proceso','resuelto') NOT NULL,
    fecha          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_seguimiento),
    KEY idx_seguimientos_ticket (id_ticket),
    KEY idx_seguimientos_tecnico (id_tecnico),
    CONSTRAINT fk_seguimientos_ticket
        FOREIGN KEY (id_ticket) REFERENCES tickets (id_ticket)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_seguimientos_tecnico
        FOREIGN KEY (id_tecnico) REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Datos iniciales: roles
-- ---------------------------------------------------------------------
INSERT INTO roles (id_rol, nombre, descripcion) VALUES
    (1, 'administrador', 'Gestiona usuarios y supervisa todos los tickets del sistema.'),
    (2, 'empleado',      'Registra solicitudes de soporte y consulta el estado de sus tickets.'),
    (3, 'tecnico',       'Atiende tickets disponibles, registra seguimientos y los resuelve.');

-- ---------------------------------------------------------------------
-- Datos iniciales: usuarios de prueba (contraseñas con password_hash)
--   Administrador -> cédula 123456789 | contraseña Admin123*
--   Empleado      -> cédula 102030405 | contraseña Empleado123*
--   Técnico       -> cédula 112131415 | contraseña Tecnico123*
--   Técnico x     -> cédula 122232425 | contraseña Tecnico123*
-- ---------------------------------------------------------------------
INSERT INTO usuarios (cedula, nombre, correo, password_hash, id_rol, activo) VALUES
    ('123456789', 'Felipe Rojas', 'rojas@admin.com',
     '$2y$10$wTCH9Q6R59ltCOed7Fb.luRfBDxz4zU.Z/Kfum4gkWxfJBcpbIOM.', 1, 1),
    ('102030405', 'Carlos Rodríguez', 'rodriguez@empleado.com',
     '$2y$10$ieraT1i49oQhElMfQHopXuK53v5GPFWNEZZmKAv9JNw4EST6hSEti', 2, 1),
    ('112131415', 'Daniel Torres', 'torres@tecnico.com',
     '$2y$10$ShLojAoqg866G1lYGimWKOuQ/EL09bjY02JFLTWaqJpsaR34RYpQm', 3, 1),
     ('122232425', 'Luis Guerra', 'guerra@tecnico.com',
     '$2y$10$ShLojAoqg866G1lYGimWKOuQ/EL09bjY02JFLTWaqJpsaR34RYpQm', 3, 0);

