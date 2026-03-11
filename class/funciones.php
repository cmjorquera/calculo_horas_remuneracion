<?php
require_once __DIR__ . "/helpers.php";

class Funciones
{
    private $db;
    private $menuSchemaReady = false;

    public function __construct(MySQL $db)
    {
        $this->db = $db;
    }

    public function obtenerDiasSemana($soloLaborales = true)
    {
        $sql = "SELECT clave, nombre, prefijo
                FROM dias_semana
                WHERE activo = 1";

        if ($soloLaborales) {
            $sql .= " AND orden <= 5";
        }

        $sql .= " ORDER BY orden ASC";

        $res = $this->db->consulta($sql);

        $dias = [];
        while ($row = $this->db->fetch_assoc($res)) {
            $dias[] = [
                "key"    => $row["clave"],
                "label"  => $row["nombre"],
                "prefix" => $row["prefijo"]
            ];
        }

        return $dias;
    }
    public function obtenerEmpleadosConContratoVigente($id_colegio, $verTodosColegios = false)
    {
        $id_colegio = (int)$id_colegio;
        $whereColegio = $verTodosColegios ? "1=1" : "e.id_colegio = {$id_colegio}";

$sql = "
  SELECT
    e.id_empleado, e.id_colegio, co.nco_colegio, e.codigo, e.run, e.nombres, e.apellido_paterno, e.apellido_materno, e.genero, e.activo,
    c.id_contrato, c.horas_semanales_cron, c.horas_lectivas, c.horas_no_lectivas, c.min_colacion_diaria, c.observacion,

    COALESCE(SUM(
      (CASE
        WHEN hs.man_ini IS NOT NULL AND hs.man_fin IS NOT NULL
             AND hs.man_ini <> '00:00:00' AND hs.man_fin <> '00:00:00'
        THEN TIMESTAMPDIFF(MINUTE, hs.man_ini, hs.man_fin)
        ELSE 0
      END)
      +
      (CASE
        WHEN hs.tar_ini IS NOT NULL AND hs.tar_fin IS NOT NULL
             AND hs.tar_ini <> '00:00:00' AND hs.tar_fin <> '00:00:00'
        THEN TIMESTAMPDIFF(MINUTE, hs.tar_ini, hs.tar_fin)
        ELSE 0
      END)
    ), 0) AS trabajadas_min

  FROM empleados e
  LEFT JOIN colegio co
    ON co.id_colegio = e.id_colegio
  LEFT JOIN contratos_empleado c
    ON c.id_empleado = e.id_empleado AND c.fecha_fin IS NULL
  LEFT JOIN horarios_semanales hs
    ON hs.id_contrato = c.id_contrato AND hs.activo = 1

  WHERE {$whereColegio}

  GROUP BY
    e.id_empleado, e.id_colegio, co.nco_colegio, e.codigo, e.run, e.nombres, e.apellido_paterno, e.apellido_materno, e.genero, e.activo,
    c.id_contrato, c.horas_semanales_cron, c.horas_lectivas, c.horas_no_lectivas, c.min_colacion_diaria, c.observacion

  ORDER BY e.apellido_paterno, e.apellido_materno, e.nombres
";



        $res = $this->db->consulta($sql);

        $empleados = [];
        while ($row = $this->db->fetch_assoc($res)) {
            $empleados[] = $row;
        }
        return $empleados;
    }

    private function minutosAHHMM($totalMin)
    {
        return minutosAHHMM($totalMin);
    }

    public function calcularHorasSemanales($id_contrato)
    {
        $id_contrato = (int)$id_contrato;
        if ($id_contrato <= 0) return 0;

        $sqlH = "
            SELECT dia, man_ini, man_fin, tar_ini, tar_fin
            FROM horarios_semanales
            WHERE id_contrato = {$id_contrato} AND activo = 1
        ";
        $rh = $this->db->consulta($sqlH);

        $totalMin = 0;

        while ($h = $this->db->fetch_assoc($rh)) {

            if (!empty($h['man_ini']) && !empty($h['man_fin'])) {
                $q = $this->db->consulta("SELECT TIMESTAMPDIFF(MINUTE,'{$h['man_ini']}','{$h['man_fin']}') AS m");
                $m = $this->db->fetch_assoc($q);
                $totalMin += max(0, (int)$m['m']);
            }

            if (!empty($h['tar_ini']) && !empty($h['tar_fin'])) {
                $q = $this->db->consulta("SELECT TIMESTAMPDIFF(MINUTE,'{$h['tar_ini']}','{$h['tar_fin']}') AS m");
                $m = $this->db->fetch_assoc($q);
                $totalMin += max(0, (int)$m['m']);
            }
        }

        return round($totalMin / 60, 2);
    }


    public function obtenerEmpleadosConResumen($id_colegio, $verTodosColegios = false)
{
    $empleados = $this->obtenerEmpleadosConContratoVigente($id_colegio, $verTodosColegios);

    foreach ($empleados as &$e) {
        $contrato = (int)($e['horas_semanales_cron'] ?? 0);
        $trab = $this->calcularHorasSemanales($e['id_contrato']);
        $e['horas_lectivas_hhmm'] = $this->minutosAHHMM((int)($e['horas_lectivas'] ?? 0));
        $e['horas_no_lectivas_hhmm'] = $this->minutosAHHMM((int)($e['horas_no_lectivas'] ?? 0));

        $pct = ($contrato > 0) ? min(100, round(($trab / $contrato) * 100)) : 0;
        $diff = round($trab - $contrato, 2);

        $e['contrato_horas'] = $contrato;
        $e['trab_horas'] = $trab;
        $e['pct'] = $pct;
        $e['diff'] = $diff;
    }
    unset($e);

    return $empleados;
}

    public function obtenerOpcionesColacion()
    {
        $this->db->consulta("
            CREATE TABLE IF NOT EXISTS colacion (
                id_colacion INT AUTO_INCREMENT PRIMARY KEY,
                hora TIME NOT NULL,
                minutos INT NOT NULL UNIQUE,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_colacion_hora (hora)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $resHasHora = $this->db->consulta("
            SELECT COUNT(*) AS t
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'colacion'
              AND COLUMN_NAME = 'hora'
        ");
        $hasHora = (int)($this->db->fetch_assoc($resHasHora)['t'] ?? 0) > 0;

        if ($hasHora) {
            $this->db->consulta("
                INSERT IGNORE INTO colacion (hora, minutos, activo) VALUES
                ('00:00:00', 0, 1),
                ('00:30:00', 30, 1),
                ('00:40:00', 40, 1),
                ('01:00:00', 60, 1)
            ");

            $res = $this->db->consulta("
                SELECT id_colacion, TIME_FORMAT(hora, '%H:%i') AS hora_hhmm, minutos
                FROM colacion
                WHERE activo = 1
                ORDER BY hora ASC
            ");
        } else {
            $this->db->consulta("
                INSERT IGNORE INTO colacion (minutos, activo) VALUES
                (0, 1), (30, 1), (40, 1), (60, 1)
            ");

            $res = $this->db->consulta("
                SELECT id_colacion, DATE_FORMAT(SEC_TO_TIME(minutos * 60), '%H:%i') AS hora_hhmm, minutos
                FROM colacion
                WHERE activo = 1
                ORDER BY minutos ASC
            ");
        }

        $opciones = [];
        while ($row = $this->db->fetch_assoc($res)) {
            $opciones[] = [
                "id_colacion" => (int)$row["id_colacion"],
                "hora" => $row["hora_hhmm"],
                "minutos" => (int)$row["minutos"]
            ];
        }

        return $opciones;
    }

    public function obtenerRoles()
    {
        $sql = "
            SELECT id_rol, codigo, nombre, descripcion
            FROM roles
            ORDER BY id_rol ASC
        ";

        $res = $this->db->consulta($sql);
        $roles = [];
        while ($row = $this->db->fetch_assoc($res)) {
            $roles[] = $row;
        }

        return $roles;
    }

    public function obtenerUsuarios($id_colegio, $verTodosColegios = false)
    {
        $id_colegio = (int)$id_colegio;
        $whereColegio = $verTodosColegios ? "1=1" : "COALESCE(u.id_colegio, urc.id_colegio, 0) = {$id_colegio}";

        $sql = "
            SELECT
                u.id_usuario,
                u.identificador,
                u.email,
                u.nombre,
                u.apellido_paterno,
                u.apellido_materno,
                u.run,
                u.telefono,
                u.id_colegio,
                u.estado,
                u.intentos,
                u.ultimo_login,
                u.created_at,
                c.nco_colegio,
                GROUP_CONCAT(DISTINCT r.nombre ORDER BY r.id_rol SEPARATOR ', ') AS roles_asignados
            FROM usuarios u
            LEFT JOIN colegio c
                ON c.id_colegio = u.id_colegio
            LEFT JOIN usuario_rol_colegio urc
                ON urc.id_usuario = u.id_usuario
               AND urc.estado = 1
            LEFT JOIN roles r
                ON r.id_rol = urc.id_rol
            WHERE {$whereColegio}
            GROUP BY
                u.id_usuario,
                u.identificador,
                u.email,
                u.nombre,
                u.apellido_paterno,
                u.apellido_materno,
                u.run,
                u.telefono,
                u.id_colegio,
                u.estado,
                u.intentos,
                u.ultimo_login,
                u.created_at,
                c.nco_colegio
            ORDER BY u.nombre ASC, u.apellido_paterno ASC, u.apellido_materno ASC
        ";

        $res = $this->db->consulta($sql);
        $usuarios = [];
        while ($row = $this->db->fetch_assoc($res)) {
            $usuarios[] = $row;
        }

        return $usuarios;
    }

    public function obtenerUsuarioRolColegio($id_colegio, $verTodosColegios = false)
    {
        $id_colegio = (int)$id_colegio;
        $whereColegio = $verTodosColegios ? "1=1" : "COALESCE(urc.id_colegio, u.id_colegio, 0) = {$id_colegio}";

        $sql = "
            SELECT
                urc.id,
                urc.id_usuario,
                urc.id_rol,
                urc.id_colegio,
                urc.estado,
                urc.created_at,
                u.identificador,
                CONCAT_WS(' ', u.nombre, u.apellido_paterno, u.apellido_materno) AS usuario_nombre,
                u.email,
                r.codigo AS rol_codigo,
                r.nombre AS rol_nombre,
                c.nco_colegio
            FROM usuario_rol_colegio urc
            INNER JOIN usuarios u
                ON u.id_usuario = urc.id_usuario
            INNER JOIN roles r
                ON r.id_rol = urc.id_rol
            LEFT JOIN colegio c
                ON c.id_colegio = urc.id_colegio
            WHERE {$whereColegio}
            ORDER BY urc.id DESC
        ";

        $res = $this->db->consulta($sql);
        $asignaciones = [];
        while ($row = $this->db->fetch_assoc($res)) {
            $asignaciones[] = $row;
        }

        return $asignaciones;
    }

    private function asegurarEsquemaMenus()
    {
        if ($this->menuSchemaReady) {
            return;
        }

        $this->db->consulta("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->db->consulta("
            CREATE TABLE IF NOT EXISTS usuario_menu_v (
                id_usuario_menu INT AUTO_INCREMENT PRIMARY KEY,
                id_usuario INT NOT NULL,
                id_menu INT NOT NULL,
                permitido TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_usuario_menu_v (id_usuario, id_menu),
                KEY idx_usuario_menu_v_usuario (id_usuario),
                KEY idx_usuario_menu_v_menu (id_menu),
                KEY idx_usuario_menu_v_permitido (permitido)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->db->consulta("
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
                visible = VALUES(visible)
        ");

        $this->db->consulta("
            INSERT INTO usuario_menu_v (id_usuario, id_menu, permitido)
            SELECT u.id_usuario, m.id_menu,
                CASE
                    WHEN m.codigo = 'usuarios' THEN
                        CASE
                            WHEN EXISTS (
                                SELECT 1
                                FROM usuario_rol_colegio urc
                                WHERE urc.id_usuario = u.id_usuario
                                  AND urc.id_rol = 1
                                  AND urc.estado = 1
                            ) THEN 1
                            ELSE 0
                        END
                    ELSE 1
                END AS permitido
            FROM usuarios u
            INNER JOIN menu_v m
                ON m.visible = 1
            LEFT JOIN usuario_menu_v um
                ON um.id_usuario = u.id_usuario
               AND um.id_menu = m.id_menu
            WHERE um.id_usuario_menu IS NULL
        ");

        $this->menuSchemaReady = true;
    }

    public function obtenerMenusSistema()
    {
        $this->asegurarEsquemaMenus();

        $res = $this->db->consulta("
            SELECT id_menu, codigo, nombre, url, icono, descripcion, orden, visible
            FROM menu_v
            WHERE visible = 1
            ORDER BY orden ASC, id_menu ASC
        ");

        $menus = [];
        while ($row = $this->db->fetch_assoc($res)) {
            $menus[] = $row;
        }

        return $menus;
    }

    public function obtenerPermisosMenuUsuario($idUsuario)
    {
        $this->asegurarEsquemaMenus();
        $idUsuario = (int)$idUsuario;

        $res = $this->db->consulta("
            SELECT
                m.id_menu,
                m.codigo,
                m.nombre,
                m.url,
                m.icono,
                m.orden,
                m.descripcion,
                COALESCE(um.permitido, 0) AS permitido
            FROM menu_v m
            LEFT JOIN usuario_menu_v um
                ON um.id_menu = m.id_menu
               AND um.id_usuario = {$idUsuario}
            WHERE m.visible = 1
            ORDER BY m.orden ASC, m.id_menu ASC
        ");

        $permisos = [];
        while ($row = $this->db->fetch_assoc($res)) {
            $row["permitido"] = (int)($row["permitido"] ?? 0);
            $permisos[] = $row;
        }

        return $permisos;
    }

    public function obtenerCodigosMenusPermitidosUsuario($idUsuario)
    {
        $permisos = $this->obtenerPermisosMenuUsuario($idUsuario);
        $codigos = [];

        foreach ($permisos as $permiso) {
            if ((int)($permiso["permitido"] ?? 0) === 1) {
                $codigos[] = (string)$permiso["codigo"];
            }
        }

        return $codigos;
    }

    public function guardarPermisosMenuUsuario($idUsuario, array $menusPermitidos)
    {
        $this->asegurarEsquemaMenus();
        $idUsuario = (int)$idUsuario;

        $menus = $this->obtenerMenusSistema();
        $permitidosMap = [];
        foreach ($menusPermitidos as $codigo) {
            $permitidosMap[(string)$codigo] = true;
        }

        foreach ($menus as $menu) {
            $idMenu = (int)$menu["id_menu"];
            $codigo = (string)$menu["codigo"];
            $permitido = isset($permitidosMap[$codigo]) ? 1 : 0;

            $this->db->consulta("
                INSERT INTO usuario_menu_v (id_usuario, id_menu, permitido)
                VALUES ({$idUsuario}, {$idMenu}, {$permitido})
                ON DUPLICATE KEY UPDATE
                    permitido = VALUES(permitido),
                    updated_at = CURRENT_TIMESTAMP
            ");
        }
    }

}
