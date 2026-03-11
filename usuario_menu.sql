-- MySQL 8+
-- Permisos de menus por usuario
-- Idea:
-- 1) Catalogo de menus disponibles en menu_v
-- 2) Tabla usuario_menu_v para permitir / bloquear cada menu por usuario
-- 3) Carga inicial segun rol actual

START TRANSACTION;

CREATE TABLE IF NOT EXISTS menu_v (
    id_menu INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    url VARCHAR(255) NOT NULL,
    icono VARCHAR(100) DEFAULT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    orden INT NOT NULL DEFAULT 0,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_menu_v_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usuario_menu_v (
    id_usuario_menu INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_menu INT NOT NULL,
    permitido TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_menu_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_usuario_menu_v_menu
        FOREIGN KEY (id_menu) REFERENCES menu_v(id_menu)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    UNIQUE KEY uk_usuario_menu_v (id_usuario, id_menu),
    KEY idx_usuario_menu_v_usuario (id_usuario),
    KEY idx_usuario_menu_v_menu (id_menu),
    KEY idx_usuario_menu_v_permitido (permitido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO menu_v (codigo, nombre, url, icono, descripcion, orden, visible)
VALUES
    ('empleados', 'Empleados', 'index.php', 'bi-people-fill', 'Modulo principal de empleados', 10, 1),
    ('graficos', 'Graficos', 'grafico.php', 'bi-bar-chart-fill', 'Graficos y reportes', 20, 1),
    ('usuarios', 'Usuarios', 'usuarios.php', 'bi-person-plus-fill', 'Administracion de usuarios', 30, 1)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    url = VALUES(url),
    icono = VALUES(icono),
    descripcion = VALUES(descripcion),
    orden = VALUES(orden),
    visible = VALUES(visible);

-- Carga inicial:
-- Todos los usuarios reciben Empleados y Graficos
INSERT INTO usuario_menu_v (id_usuario, id_menu, permitido)
SELECT u.id_usuario, m.id_menu, 1
FROM usuarios u
INNER JOIN menu_v m
    ON m.codigo IN ('empleados', 'graficos')
LEFT JOIN usuario_menu_v um
    ON um.id_usuario = u.id_usuario
   AND um.id_menu = m.id_menu
WHERE um.id_usuario_menu IS NULL;

-- Solo usuarios con rol 1 reciben acceso inicial a Usuarios
INSERT INTO usuario_menu_v (id_usuario, id_menu, permitido)
SELECT DISTINCT urc.id_usuario, m.id_menu, 1
FROM usuario_rol_colegio urc
INNER JOIN menu_v m
    ON m.codigo = 'usuarios'
LEFT JOIN usuario_menu_v um
    ON um.id_usuario = urc.id_usuario
   AND um.id_menu = m.id_menu
WHERE urc.id_rol = 1
  AND urc.estado = 1
  AND um.id_usuario_menu IS NULL;

COMMIT;

-- Consulta util para revisar permisos por usuario
-- SELECT
--     u.id_usuario,
--     u.identificador,
--     m.codigo AS menu_codigo,
--     m.nombre AS menu_nombre,
--     um.permitido
-- FROM usuario_menu_v um
-- INNER JOIN usuarios u ON u.id_usuario = um.id_usuario
-- INNER JOIN menu_v m ON m.id_menu = um.id_menu
-- ORDER BY u.id_usuario, m.orden;
