<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

$esSuperAdmin = !empty($_SESSION["is_super_admin"]);
$esAdminSistema = (int)($_SESSION["id_rol"] ?? 0) === 1;

if (!$esAdminSistema) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . "/class/conexion.php";
require_once __DIR__ . "/class/funciones.php";

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");
$funciones = new Funciones($db);

$menusPermitidosActual = $funciones->obtenerCodigosMenusPermitidosUsuario((int)($_SESSION["id_usuario"] ?? 0));
if (!in_array('usuarios', $menusPermitidosActual, true)) {
    if (in_array('empleados', $menusPermitidosActual, true)) {
        header("Location: index.php");
    } elseif (in_array('graficos', $menusPermitidosActual, true)) {
        header("Location: grafico.php");
    } else {
        header("Location: logout.php");
    }
    exit;
}

$verTodosColegios = true;
$usuarios = $funciones->obtenerUsuarios($_SESSION["id_colegio"], $verTodosColegios);

function estadoUsuarioTexto($estado)
{
    $estado = (int)$estado;
    if ($estado === 1) return "Activo";
    if ($estado === 2) return "Bloqueado";
    return "Inactivo";
}

function estadoClase($estado)
{
    $estado = (int)$estado;
    if ($estado === 1) return "is-ok";
    if ($estado === 2) return "is-warning";
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
    <link rel="stylesheet" type="text/css" href="css/menu_lateral.css?v=<?= filemtime(__DIR__ . '/css/menu_lateral.css') ?>">
    <link rel="stylesheet" type="text/css" href="css/usuarios.css?v=<?= filemtime(__DIR__ . '/css/usuarios.css') ?>">
    <link rel="icon" type="image/png" href="imagenes/logo_1.jpg" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/button.js"></script>
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
                <h1>Calculadora de Horas Cronológicas</h1>
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
                <span class="label">Fecha</span>
                <span class="value" id="uiFecha">--</span>
            </div>
            <div class="chip">
                <span class="label">Hora</span>
                <span class="value" id="uiHora">--</span>
            </div>
        </div>
    </header>

    <main class="usuarios-main page-shell">
        <section class="card">
            <div class="card-head">
                <div>
                    <h2>Tabla usuarios</h2>
                    <small>Administra usuarios y define qué menú puede ver cada cuenta.</small>
                </div>
                <div class="users-head-actions">
                    <button type="button" class="btn-primary-admin" onclick="mostrarCrearUsuario()">
                        <i class="bi bi-person-plus-fill"></i>
                        Agregar usuario
                    </button>
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
                            <th>Permisos</th>
                            <th>Estado</th>
                            <th>Intentos</th>
                            <th>Último login</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <?php $nombreCompleto = trim(($usuario["nombre"] ?? "") . " " . ($usuario["apellido_paterno"] ?? "") . " " . ($usuario["apellido_materno"] ?? "")); ?>
                        <tr>
                            <td><?= (int)$usuario["id_usuario"] ?></td>
                            <td><?= htmlspecialchars($usuario["identificador"] ?? "") ?></td>
                            <td><?= htmlspecialchars($nombreCompleto) ?></td>
                            <td><?= htmlspecialchars($usuario["email"] ?? "") ?></td>
                            <td><?= htmlspecialchars($usuario["nco_colegio"] ?? "Sin colegio") ?></td>
                            <td><?= htmlspecialchars($usuario["roles_asignados"] ?: "Sin roles") ?></td>
                            <td>
                                <button
                                    type="button"
                                    class="btn-outline-admin"
                                    onclick="abrirPermisosUsuario(
                                        <?= (int)$usuario['id_usuario'] ?>,
                                        <?= htmlspecialchars(json_encode($nombreCompleto), ENT_QUOTES) ?>,
                                        <?= htmlspecialchars(json_encode($usuario['identificador'] ?? ''), ENT_QUOTES) ?>
                                    )">
                                    <i class="bi bi-sliders2"></i>
                                    Menús
                                </button>
                            </td>
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
    </main>
</div>

<script>
function updateHeaderDateTime() {
    const now = new Date();
    const fecha = new Intl.DateTimeFormat("es-CL", {
        weekday: "long",
        year: "numeric",
        month: "2-digit",
        day: "2-digit"
    }).format(now);
    const hora = new Intl.DateTimeFormat("es-CL", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit"
    }).format(now);

    document.getElementById("uiFecha").textContent = fecha;
    document.getElementById("uiHora").textContent = hora;
}

function mostrarCrearUsuario() {
    Swal.fire({
        icon: "info",
        title: "Agregar usuario",
        text: "Falta conectar el formulario de creación."
    });
}

async function abrirPermisosUsuario(idUsuario, nombreUsuario, identificador) {
    const respuesta = await fetch(`modelos/rescatar/usuario_menu.php?id_usuario=${encodeURIComponent(idUsuario)}`);
    const data = await respuesta.json();

    if (!data.ok) {
        Swal.fire("Permisos", data.msg || "No se pudieron cargar los permisos.", "error");
        return;
    }

    const permisos = Array.isArray(data.permisos) ? data.permisos : [];
    const html = `
        <div class="menu-permisos-modal">
            <div class="menu-permisos-head">
                <strong>${escapeHtml(nombreUsuario || "Usuario")}</strong>
                <span>${escapeHtml(identificador || "Sin identificador")} · Activa o bloquea cada menú del sistema.</span>
            </div>
            <div class="menu-permisos-grid">
                ${permisos.map((menu) => `
                    <div class="menu-perm-card">
                        <div class="menu-perm-icon">
                            <i class="bi ${escapeHtml(menu.icono || "bi-grid")}"></i>
                        </div>
                        <div class="menu-perm-copy">
                            <strong>${escapeHtml(menu.nombre || menu.codigo || "Menú")}</strong>
                            <span>${escapeHtml(menu.url || "")}</span>
                        </div>
                        <label class="menu-perm-switch">
                            <input type="checkbox" class="js-menu-perm" value="${escapeHtml(menu.codigo || "")}" ${Number(menu.permitido) === 1 ? "checked" : ""}>
                            <span class="menu-perm-slider"></span>
                        </label>
                    </div>
                `).join("")}
            </div>
        </div>
    `;

    const resultado = await Swal.fire({
        title: "Permisos por menú",
        html,
        width: 720,
        showCancelButton: true,
        confirmButtonText: "Guardar cambios",
        cancelButtonText: "Cancelar",
        focusConfirm: false,
        preConfirm: async () => {
            const menus = Array.from(document.querySelectorAll(".js-menu-perm:checked")).map((el) => el.value);

            const saveResponse = await fetch("modelos/guardar/usuario_menu.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    id_usuario: idUsuario,
                    menus
                })
            });

            const saveData = await saveResponse.json();
            if (!saveData.ok) {
                Swal.showValidationMessage(saveData.msg || "No se pudieron guardar los permisos.");
                return false;
            }

            return saveData;
        }
    });

    if (resultado && resultado.isConfirmed) {
        Swal.fire("Permisos actualizados", "Los menús del usuario fueron guardados.", "success");
    }
}

function escapeHtml(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

updateHeaderDateTime();
setInterval(updateHeaderDateTime, 1000);
</script>
</body>
</html>
