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
$roles = $funciones->obtenerRoles();
$colegios = $funciones->obtenerColegios();

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
        <link rel="stylesheet" type="text/css" href="css/modales.css">

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
const ROLES_USUARIO = <?= json_encode($roles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const COLEGIOS_USUARIO = <?= json_encode($colegios, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

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
    const html = `
        <form class="user-create-form" id="userCreateForm">
            <div class="user-create-grid">
                <label class="user-field">
                    <span>Identificador</span>
                    <input id="nuevoIdentificador" type="text" maxlength="60" placeholder="Ej: cjorquera">
                </label>
                <label class="user-field">
                    <span>Email</span>
                    <input id="nuevoEmail" type="email" maxlength="150" placeholder="correo@colegio.cl">
                </label>
                <label class="user-field">
                    <span>Contraseña</span>
                    <input id="nuevaClave" type="text" maxlength="255" placeholder="Mínimo 4 caracteres">
                </label>
                <label class="user-field">
                    <span>Rol</span>
                    <select id="nuevoRol">
                        <option value="">Selecciona</option>
                        ${ROLES_USUARIO.map((rol) => `<option value="${Number(rol.id_rol)}">${escapeHtml(rol.nombre || rol.codigo || "Rol")}</option>`).join("")}
                    </select>
                </label>
                <label class="user-field">
                    <span>Nombre</span>
                    <input id="nuevoNombre" type="text" maxlength="80" placeholder="Nombre">
                </label>
                <label class="user-field">
                    <span>Apellido paterno</span>
                    <input id="nuevoApellidoPaterno" type="text" maxlength="80" placeholder="Apellido paterno">
                </label>
                <label class="user-field">
                    <span>Apellido materno</span>
                    <input id="nuevoApellidoMaterno" type="text" maxlength="80" placeholder="Apellido materno">
                </label>
                <label class="user-field">
                    <span>Colegio</span>
                    <select id="nuevoColegio">
                        <option value="">Selecciona</option>
                        ${COLEGIOS_USUARIO.map((colegio) => {
                            const nombre = colegio.nco_colegio || colegio.nom_colegio || `Colegio ${Number(colegio.id_colegio)}`;
                            return `<option value="${Number(colegio.id_colegio)}">${escapeHtml(nombre)}</option>`;
                        }).join("")}
                    </select>
                </label>
                <label class="user-field">
                    <span>RUN</span>
                    <input id="nuevoRun" type="text" maxlength="20" placeholder="Opcional">
                </label>
                <label class="user-field">
                    <span>Teléfono</span>
                    <input id="nuevoTelefono" type="text" maxlength="25" placeholder="Opcional">
                </label>
                <label class="user-field">
                    <span>Estado</span>
                    <select id="nuevoEstado">
                        <option value="1" selected>Activo</option>
                        <option value="0">Inactivo</option>
                        <option value="2">Bloqueado</option>
                    </select>
                </label>
            </div>
        </form>
    `;

    Swal.fire({
        title: "Agregar usuario",
        html,
        width: 760,
        showCancelButton: true,
        confirmButtonText: "Crear usuario",
        cancelButtonText: "Cancelar",
        focusConfirm: false,
        preConfirm: async () => {
            const payload = {
                identificador: document.getElementById("nuevoIdentificador").value.trim(),
                email: document.getElementById("nuevoEmail").value.trim(),
                clave: document.getElementById("nuevaClave").value.trim(),
                nombre: document.getElementById("nuevoNombre").value.trim(),
                apellido_paterno: document.getElementById("nuevoApellidoPaterno").value.trim(),
                apellido_materno: document.getElementById("nuevoApellidoMaterno").value.trim(),
                id_rol: document.getElementById("nuevoRol").value,
                id_colegio: document.getElementById("nuevoColegio").value,
                run: document.getElementById("nuevoRun").value.trim(),
                telefono: document.getElementById("nuevoTelefono").value.trim(),
                estado: document.getElementById("nuevoEstado").value
            };

            if (!payload.identificador || !payload.email || !payload.clave || !payload.nombre || !payload.apellido_paterno || !payload.id_rol || !payload.id_colegio) {
                Swal.showValidationMessage("Completa los campos obligatorios del formulario.");
                return false;
            }

            const body = new URLSearchParams(payload);
            const respuesta = await fetch("modelos/guardar/usuario.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
                body: body.toString()
            });

            const data = await respuesta.json();
            if (!data.ok) {
                Swal.showValidationMessage(data.msg || "No se pudo crear el usuario.");
                return false;
            }

            return data;
        }
    }).then((resultado) => {
        if (resultado.isConfirmed) {
            Swal.fire("Usuario creado", "La cuenta fue registrada correctamente.", "success")
                .then(() => window.location.reload());
        }
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
