<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

$esSuperAdmin = !empty($_SESSION["is_super_admin"]);
$esAdminSistema = $esSuperAdmin || (int)($_SESSION["id_rol"] ?? 0) === 1;

if (!$esAdminSistema) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . "/class/conexion.php";
require_once __DIR__ . "/class/funciones.php";

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");
$funciones = new Funciones($db);
$verTodosColegios = $esSuperAdmin;

$usuarios = $funciones->obtenerUsuarios($_SESSION["id_colegio"], $verTodosColegios);
$asignaciones = $funciones->obtenerUsuarioRolColegio($_SESSION["id_colegio"], $verTodosColegios);
$roles = $funciones->obtenerRoles();

function estadoUsuarioTexto($estado)
{
    $estado = (int)$estado;
    if ($estado === 1) {
        return "Activo";
    }
    if ($estado === 2) {
        return "Bloqueado";
    }
    return "Inactivo";
}

function estadoClase($estado)
{
    $estado = (int)$estado;
    if ($estado === 1) {
        return "is-ok";
    }
    if ($estado === 2) {
        return "is-warning";
    }
    return "is-muted";
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Usuarios | Calculadora de Horas</title>
    <link rel="stylesheet" type="text/css" href="css/principal.css?v=<?= filemtime(__DIR__ . '/css/principal.css') ?>">
    <link rel="stylesheet" type="text/css" href="css/menu_lateral.css">
    <link rel="icon" type="image/png" href="imagenes/logo_1.jpg" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/button.js"></script>
    <style>
        .usuarios-main {
            margin-top: var(--gap);
            display: grid;
            gap: 12px;
        }

        .usuarios-kpis {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .usuario-kpi {
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: linear-gradient(180deg, #ffffff, #f8fafc);
            box-shadow: var(--shadow);
        }

        .usuario-kpi .label {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .usuario-kpi .value {
            font-size: 28px;
            font-weight: 900;
            color: var(--inst-blue);
        }

        .usuarios-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .admin-note {
            margin-top: 12px;
            border: 1px solid rgba(0, 78, 140, .18);
            background: rgba(0, 78, 140, .06);
            color: #0f2744;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 13px;
            line-height: 1.45;
        }

        .table-wrap-users {
            overflow-x: auto;
        }

        .table-admin {
            width: 100%;
            border-collapse: collapse;
            min-width: 840px;
        }

        .table-admin th,
        .table-admin td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: top;
            font-size: 13px;
        }

        .table-admin th {
            background: #f8fafc;
            color: #0f172a;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .table-admin tbody tr:hover {
            background: rgba(0, 78, 140, .03);
        }

        .badge-state {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .badge-state.is-ok {
            background: rgba(34, 197, 94, .12);
            color: #166534;
            border-color: rgba(34, 197, 94, .22);
        }

        .badge-state.is-warning {
            background: rgba(245, 158, 11, .12);
            color: #92400e;
            border-color: rgba(245, 158, 11, .22);
        }

        .badge-state.is-muted {
            background: rgba(100, 116, 139, .12);
            color: #475569;
            border-color: rgba(100, 116, 139, .22);
        }

        .role-pill {
            display: inline-flex;
            padding: 4px 8px;
            border-radius: 999px;
            background: rgba(0, 111, 173, .1);
            color: #075985;
            font-size: 12px;
            font-weight: 700;
        }

        .empty-table {
            padding: 18px;
            color: var(--muted);
            font-size: 13px;
        }

        @media (max-width: 980px) {
            .usuarios-kpis {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <?php include __DIR__ . "/menu_lateral.php"; ?>

    <header class="header">
        <div class="brand">
            <div class="logo">
                <img src="imagenes/logo_2.jpg" alt="Logo" onerror="this.style.display='none'">
            </div>
            <div class="titles">
                <h1>Agregar usuario</h1>
                <div class="user-info">
                    <i class="bi bi-person-circle"></i>
                    <span><?= htmlspecialchars($_SESSION["nombre_completo"]) ?></span>
                    <span class="sep">•</span>
                    <span><?= htmlspecialchars($_SESSION["cabecera_contexto"] ?? ($_SESSION["nom_colegio"] ?? "Sin colegio")) ?></span>
                </div>
            </div>
        </div>
        <div class="meta">
            <div class="chip">
                <span class="label">Módulo</span>
                <span class="value">Usuarios</span>
            </div>
            <div class="chip">
                <span class="label">Acceso</span>
                <span class="value"><?= $esSuperAdmin ? "Super Admin" : "Administrador" ?></span>
            </div>
        </div>
    </header>

    <main class="usuarios-main">
        <section class="usuarios-kpis">
            <article class="usuario-kpi">
                <span class="label">Usuarios</span>
                <span class="value"><?= count($usuarios) ?></span>
            </article>
            <article class="usuario-kpi">
                <span class="label">Asignaciones usuario/rol</span>
                <span class="value"><?= count($asignaciones) ?></span>
            </article>
            <article class="usuario-kpi">
                <span class="label">Roles</span>
                <span class="value"><?= count($roles) ?></span>
            </article>
        </section>

        <section class="card">
            <div class="card-head">
                <div>
                    <h2>Tabla usuarios</h2>
                    <small>Cuentas del sistema con su colegio y roles activos.</small>
                </div>
            </div>
            <div class="table-wrap-users">
                <?php if (!$usuarios): ?>
                    <div class="empty-table">No hay usuarios para el contexto seleccionado.</div>
                <?php else: ?>
                <table class="table-admin">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Identificador</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Colegio</th>
                            <th>Roles</th>
                            <th>Estado</th>
                            <th>Intentos</th>
                            <th>Último login</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?= (int)$usuario["id_usuario"] ?></td>
                            <td><?= htmlspecialchars($usuario["identificador"] ?? "") ?></td>
                            <td><?= htmlspecialchars(trim(($usuario["nombre"] ?? "") . " " . ($usuario["apellido_paterno"] ?? "") . " " . ($usuario["apellido_materno"] ?? ""))) ?></td>
                            <td><?= htmlspecialchars($usuario["email"] ?? "") ?></td>
                            <td><?= htmlspecialchars($usuario["nco_colegio"] ?? "Sin colegio") ?></td>
                            <td><?= htmlspecialchars($usuario["roles_asignados"] ?: "Sin roles") ?></td>
                            <td><span class="badge-state <?= estadoClase($usuario["estado"] ?? 0) ?>"><?= htmlspecialchars(estadoUsuarioTexto($usuario["estado"] ?? 0)) ?></span></td>
                            <td><?= (int)($usuario["intentos"] ?? 0) ?></td>
                            <td><?= htmlspecialchars($usuario["ultimo_login"] ?? "Sin registro") ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card-head">
                <div>
                    <h2>Tabla usuario_rol_colegio</h2>
                    <small>Asignaciones activas e históricas entre usuario, rol y colegio.</small>
                </div>
            </div>
            <div class="table-wrap-users">
                <?php if (!$asignaciones): ?>
                    <div class="empty-table">No hay asignaciones registradas.</div>
                <?php else: ?>
                <table class="table-admin">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Identificador</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Colegio</th>
                            <th>Estado</th>
                            <th>Creado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($asignaciones as $asignacion): ?>
                        <tr>
                            <td><?= (int)$asignacion["id"] ?></td>
                            <td><?= htmlspecialchars($asignacion["usuario_nombre"] ?: "Sin nombre") ?></td>
                            <td><?= htmlspecialchars($asignacion["identificador"] ?? "") ?></td>
                            <td><?= htmlspecialchars($asignacion["email"] ?? "") ?></td>
                            <td><?= htmlspecialchars(($asignacion["rol_nombre"] ?? "") . " (" . ($asignacion["rol_codigo"] ?? "") . ")") ?></td>
                            <td><?= htmlspecialchars($asignacion["nco_colegio"] ?? "Sin colegio") ?></td>
                            <td><span class="badge-state <?= estadoClase($asignacion["estado"] ?? 0) ?>"><?= (int)($asignacion["estado"] ?? 0) === 1 ? "Activo" : "Inactivo" ?></span></td>
                            <td><?= htmlspecialchars($asignacion["created_at"] ?? "") ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card-head">
                <div>
                    <h2>Tabla roles</h2>
                    <small>Catálogo actual de perfiles disponibles.</small>
                </div>
            </div>
            <div class="table-wrap-users">
                <?php if (!$roles): ?>
                    <div class="empty-table">No hay roles definidos.</div>
                <?php else: ?>
                <table class="table-admin">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($roles as $rol): ?>
                        <tr>
                            <td><?= (int)$rol["id_rol"] ?></td>
                            <td><span class="role-pill"><?= htmlspecialchars($rol["codigo"] ?? "") ?></span></td>
                            <td><?= htmlspecialchars($rol["nombre"] ?? "") ?></td>
                            <td><?= htmlspecialchars($rol["descripcion"] ?? "") ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <div class="admin-note">
                Recomendación: usa esta pantalla como base de administración y después separa creación/edición en un formulario con validaciones, hash de contraseña y asignación explícita de rol por colegio.
            </div>
        </section>
    </main>
</div>
</body>
</html>
