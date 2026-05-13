<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

$esSuperAdmin = !empty($_SESSION["is_super_admin"])
    || ((int)($_SESSION["id_rol"] ?? 0) === 1)
    || ((int)($_SESSION["id_colegio"] ?? 0) === 15);
$esAdminSistema = $esSuperAdmin;

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
$colegiosModal = $colegios;
$idUsuarioActual = (int)($_SESSION["id_usuario"] ?? 0);

if ($idUsuarioActual !== 2) {
    $colegiosModal = array_values(array_filter($colegiosModal, static function ($colegio) {
        return (int)($colegio["id_colegio"] ?? 0) !== 15;
    }));
}
$colegiosLogoMap = [];

foreach ($colegios as $colegio) {
    $idColegioLogo = (int)($colegio["id_colegio"] ?? 0);
    if ($idColegioLogo <= 0) {
        continue;
    }

    $logoCandidates = [$idColegioLogo];
    if ($idColegioLogo === 14 || $idColegioLogo === 15) {
        $logoCandidates = [15, 14];
    }

    foreach ($logoCandidates as $logoId) {
        foreach (["png", "jpg", "jpeg"] as $extLogo) {
            $logoRelTmp = "imagenes/colegios/colegio_" . $logoId . "." . $extLogo;
            $logoAbsTmp = __DIR__ . "/" . $logoRelTmp;
            if (is_file($logoAbsTmp)) {
                $colegiosLogoMap[(string)$idColegioLogo] = $logoRelTmp;
                break 2;
            }
        }
    }
}

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

function estadoUsuarioDetalle(array $usuario)
{
    $estado = (int)($usuario["estado"] ?? 0);
    $tokenPendiente = trim((string)($usuario["token_reinicio"] ?? "")) !== "";

    if ($estado === 2) {
        return [
            "texto" => "Bloqueado",
            "clase" => "is-warning",
            "popover" => ""
        ];
    }

    if ($tokenPendiente) {
        return [
            "texto" => "Inactivo",
            "clase" => "is-muted",
            "popover" => "Esperando que active su clave."
        ];
    }

    if ($estado === 1) {
        return [
            "texto" => "Activo",
            "clase" => "is-ok",
            "popover" => ""
        ];
    }

    return [
        "texto" => "Inactivo",
        "clase" => "is-muted",
        "popover" => ""
    ];
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
    <link rel="stylesheet" type="text/css" href="css/modales.css?v=<?= filemtime(__DIR__ . '/css/modales.css') ?>">

    <link rel="icon" type="image/png" href="imagenes/logo_1.jpg" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/button.js"></script>
</head>
<body>
<div class="page">
    <?php include __DIR__ . "/menu_lateral.php"; ?>
    <!-- Menu lateral -->
     <?php $headerTitle = "Calculadora de Horas Pedagógicas y Cronológicas"; ?>
    <?php include __DIR__ . "/header.php"; ?>


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
                <div class="emp-table-wrap users-table-wrap">
                <table class="emp-table" id="usersTable">
                    <thead>
                        <tr>
                            <th class="sortable" data-type="number">N° <i class="bi bi-arrow-down-up sort-ico"></i></th>
                            <th class="sortable" data-type="text">Nombre - Apellidos <i class="bi bi-arrow-down-up sort-ico"></i></th>
                            <th class="sortable" data-type="text">Email <i class="bi bi-arrow-down-up sort-ico"></i></th>
                            <th class="sortable" data-type="text">Colegio <i class="bi bi-arrow-down-up sort-ico"></i></th>
                            <th class="sortable" data-type="text">Roles <i class="bi bi-arrow-down-up sort-ico"></i></th>
                            <th class="sortable" data-type="text">Estado <i class="bi bi-arrow-down-up sort-ico"></i></th>
                            <th class="sortable" data-type="text">Opciones <i class="bi bi-arrow-down-up sort-ico"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <?php
                            static $contadorUsuarios = 0;
                            $contadorUsuarios++;
                            $nombreCompleto = trim(($usuario["nombre"] ?? "") . " " . ($usuario["apellido_paterno"] ?? "") . " " . ($usuario["apellido_materno"] ?? ""));
                            $idColegioUsuario = (int)($usuario["id_colegio"] ?? 0);
                            $nombreColegioUsuario = trim((string)($usuario["nco_colegio"] ?? "Sin colegio"));
                            $logoColegioUsuario = $colegiosLogoMap[(string)$idColegioUsuario] ?? "";
                            $rolesAsignados = trim((string)($usuario["roles_asignados"] ?: "Sin roles"));
                            $estadoDetalle = estadoUsuarioDetalle($usuario);
                        ?>
                        <tr>
                            <td class="cell-num" data-value="<?= $contadorUsuarios ?>"><?= $contadorUsuarios ?></td>
                            <td class="cell-nombre" data-value="<?= htmlspecialchars($nombreCompleto, ENT_QUOTES) ?>">
                                <?= htmlspecialchars($nombreCompleto) ?>
                            </td>
                            <td data-value="<?= htmlspecialchars($usuario["email"] ?? "", ENT_QUOTES) ?>">
                                <?= htmlspecialchars($usuario["email"] ?? "") ?>
                            </td>
                            <td class="cell-colegio" data-value="<?= htmlspecialchars($nombreColegioUsuario, ENT_QUOTES) ?>">
                                <div class="cell-colegio-wrap">
                                    <?php if ($logoColegioUsuario !== ''): ?>
                                        <img class="colegio-avatar" src="<?= htmlspecialchars($logoColegioUsuario, ENT_QUOTES) ?>" alt="<?= htmlspecialchars($nombreColegioUsuario) ?>">
                                    <?php else: ?>
                                        <span class="colegio-avatar colegio-avatar-fallback"><?= $idColegioUsuario > 0 ? $idColegioUsuario : '-' ?></span>
                                    <?php endif; ?>
                                    <span class="colegio-nombre"><?= htmlspecialchars($nombreColegioUsuario) ?></span>
                                </div>
                            </td>
                            <td data-value="<?= htmlspecialchars($rolesAsignados, ENT_QUOTES) ?>">
                                <span class="role-pill"><?= htmlspecialchars($rolesAsignados) ?></span>
                            </td>
                            <td data-value="<?= htmlspecialchars($estadoDetalle["texto"], ENT_QUOTES) ?>">
                                <div class="status-cell">
                                    <span class="badge-state <?= htmlspecialchars($estadoDetalle["clase"], ENT_QUOTES) ?>"><?= htmlspecialchars($estadoDetalle["texto"]) ?></span>
                                    <?php if ($estadoDetalle["popover"] !== ""): ?>
                                        <button type="button" class="status-help-btn" aria-label="Información de estado">
                                            <i class="bi bi-question-circle-fill"></i>
                                        </button>
                                        <div class="status-popover"><?= htmlspecialchars($estadoDetalle["popover"]) ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="cell-opciones" data-value="menus">
                                <div class="cell-actions">
                                    <button
                                        type="button"
                                        class="btn-table-icon"
                                        title="Menús"
                                        onclick="abrirPermisosUsuario(
                                            <?= (int)$usuario['id_usuario'] ?>,
                                            <?= htmlspecialchars(json_encode($nombreCompleto), ENT_QUOTES) ?>,
                                            <?= htmlspecialchars(json_encode($usuario['email'] ?? ''), ENT_QUOTES) ?>,
                                            <?= htmlspecialchars(json_encode($nombreColegioUsuario), ENT_QUOTES) ?>,
                                            <?= htmlspecialchars(json_encode($logoColegioUsuario), ENT_QUOTES) ?>,
                                            <?= (int)$idColegioUsuario ?>
                                        )">
                                        <i class="bi bi-sliders2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<script>
const ROLES_USUARIO = <?= json_encode($roles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const COLEGIOS_USUARIO = <?= json_encode($colegiosModal, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const COLEGIOS_LOGO_USUARIO = <?= json_encode($colegiosLogoMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const ID_USUARIO_ACTUAL = <?= $idUsuarioActual ?>;
const ID_ROL_ADMIN_COLEGIO = 2;
const SWAL_SEDUC_CONFIG = {
    buttonsStyling: false,
    reverseButtons: true,
    showCloseButton: true,
    backdrop: "rgba(15, 23, 42, .35)",
    customClass: {
        popup: "swal-seduc",
        confirmButton: "btn-seduc btn-seduc-primary",
        cancelButton: "btn-seduc btn-seduc-ghost"
    }
};

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
    const puedeElegirRol = ID_USUARIO_ACTUAL === 2;
    const rolAdminColegio = ROLES_USUARIO.find((rol) => Number(rol.id_rol) === ID_ROL_ADMIN_COLEGIO);
    const nombreRolAdminColegio = escapeHtml(rolAdminColegio?.nombre || "Administrador de colegios");
    const html = `
        <form class="user-create-form" id="userCreateForm">
            <div class="user-create-topbar">
                <div id="nuevoColegioLogoWrap" class="colegio-logo-chip is-hidden" aria-live="polite">
                    <img id="nuevoColegioLogoImg" class="colegio-logo-chip-img" src="" alt="">
                    <div class="colegio-logo-chip-copy">
                        <strong id="nuevoColegioLogoNombre">Colegio</strong>
                        <span id="nuevoColegioLogoMeta">ID colegio</span>
                    </div>
                </div>
            </div>
            <div class="user-create-grid">
                <label class="user-field">
                    <span>Email</span>
                    <input id="nuevoEmail" type="email" maxlength="150" placeholder="correo@colegio.cl">
                    <small id="nuevoEmailEstado" class="user-field-note is-hidden"></small>
                </label>
                <label class="user-field user-field-with-help">
                    <span class="user-field-label">
                        <span>Rol</span>
                        ${puedeElegirRol ? "" : `
                        <button type="button" id="nuevoRolAyudaBtn" class="status-help-btn" aria-label="Información sobre permisos de rol">
                            <i class="bi bi-question-circle-fill"></i>
                        </button>
                        <div id="nuevoRolAyudaPopover" class="status-popover">
                            Usted solo tiene permisos para crear rol de administrador de colegios.
                        </div>
                        `}
                    </span>
                    ${puedeElegirRol ? `
                    <select id="nuevoRol">
                        <option value="">Selecciona</option>
                        ${ROLES_USUARIO.map((rol) => `<option value="${Number(rol.id_rol)}">${escapeHtml(rol.nombre || rol.codigo || "Rol")}</option>`).join("")}
                    </select>
                    ` : `
                    <input id="nuevoRol" type="hidden" value="${ID_ROL_ADMIN_COLEGIO}">
                    <input type="text" value="${nombreRolAdminColegio}" disabled>
                    `}
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
            </div>
        </form>
    `;

    Swal.fire({
        ...SWAL_SEDUC_CONFIG,
        title: "Agregar usuario",
        html,
        width: 760,
        showCancelButton: true,
        confirmButtonText: "Crear usuario",
        cancelButtonText: "Cancelar",
        focusConfirm: false,
        didOpen: () => {
            const colegioSelect = document.getElementById("nuevoColegio");
            const logoWrap = document.getElementById("nuevoColegioLogoWrap");
            const logoImg = document.getElementById("nuevoColegioLogoImg");
            const logoNombre = document.getElementById("nuevoColegioLogoNombre");
            const logoMeta = document.getElementById("nuevoColegioLogoMeta");
            const emailInput = document.getElementById("nuevoEmail");
            const emailEstado = document.getElementById("nuevoEmailEstado");
            const rolAyudaBtn = document.getElementById("nuevoRolAyudaBtn");
            const rolAyudaPopover = document.getElementById("nuevoRolAyudaPopover");

            const syncColegioLogo = () => {
                if (!colegioSelect || !logoWrap || !logoImg || !logoNombre || !logoMeta) {
                    return;
                }

                const idColegio = String(colegioSelect.value || "");
                const selectedOption = colegioSelect.options[colegioSelect.selectedIndex];
                const nombreColegio = selectedOption ? selectedOption.textContent.trim() : "Colegio";
                const logoPath = COLEGIOS_LOGO_USUARIO[idColegio] || "";

                if (!idColegio || !logoPath) {
                    logoWrap.classList.add("is-hidden");
                    logoImg.removeAttribute("src");
                    logoImg.alt = "";
                    return;
                }

                logoImg.src = logoPath;
                logoImg.alt = `Logo de ${nombreColegio}`;
                logoNombre.textContent = nombreColegio;
                logoMeta.textContent = `ID colegio: ${idColegio}`;
                logoWrap.classList.remove("is-hidden");
            };

            let lastEmailChecked = "";
            let lastEmailResult = null;

            const setEmailEstado = (type, text) => {
                if (!emailEstado || !emailInput) return;

                emailEstado.textContent = text || "";
                emailEstado.classList.remove("is-hidden", "is-error", "is-ok");
                emailInput.classList.remove("is-invalid");

                if (!text) {
                    emailEstado.classList.add("is-hidden");
                    return;
                }

                if (type === "error") {
                    emailEstado.classList.add("is-error");
                    emailInput.classList.add("is-invalid");
                    return;
                }

                if (type === "ok") {
                    emailEstado.classList.add("is-ok");
                    return;
                }
            };

            const validarEmailExistente = async () => {
                if (!emailInput) return null;

                const email = emailInput.value.trim();
                const emailNormalizado = email.toLowerCase();

                if (!email) {
                    lastEmailChecked = "";
                    lastEmailResult = null;
                    setEmailEstado("", "");
                    return null;
                }

                if (emailNormalizado === lastEmailChecked) {
                    return lastEmailResult;
                }

                const respuesta = await fetch(`modelos/rescatar/usuario_existente.php?email=${encodeURIComponent(email)}`);
                const data = await respuesta.json();
                lastEmailChecked = emailNormalizado;
                lastEmailResult = data;

                if (!data.ok) {
                    setEmailEstado("", "");
                    return data;
                }

                if (data.exists) {
                    setEmailEstado("error", `Usuario ya existe: ${data.nombre_completo || "Usuario registrado"}.`);
                    return data;
                }

                setEmailEstado("ok", "Correo disponible.");
                return data;
            };

            colegioSelect?.addEventListener("change", syncColegioLogo);
            emailInput?.addEventListener("blur", validarEmailExistente);
            emailInput?.addEventListener("input", () => {
                lastEmailChecked = "";
                lastEmailResult = null;
                setEmailEstado("", "");
            });
            if (rolAyudaBtn && rolAyudaPopover) {
                rolAyudaBtn.addEventListener("click", (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    const willOpen = !rolAyudaPopover.classList.contains("is-open");
                    rolAyudaPopover.classList.toggle("is-open", willOpen);
                });

                document.addEventListener("click", () => {
                    rolAyudaPopover.classList.remove("is-open");
                });
            }
            syncColegioLogo();
        },
        preConfirm: async () => {
            const payload = {
                email: document.getElementById("nuevoEmail").value.trim(),
                nombre: document.getElementById("nuevoNombre").value.trim(),
                apellido_paterno: document.getElementById("nuevoApellidoPaterno").value.trim(),
                apellido_materno: document.getElementById("nuevoApellidoMaterno").value.trim(),
                id_rol: document.getElementById("nuevoRol").value,
                id_colegio: document.getElementById("nuevoColegio").value,
                run: document.getElementById("nuevoRun").value.trim(),
                telefono: document.getElementById("nuevoTelefono").value.trim(),
                colegio_nombre: document.getElementById("nuevoColegio").options[document.getElementById("nuevoColegio").selectedIndex]?.text?.trim() || ""
            };

            if (!payload.email || !payload.nombre || !payload.apellido_paterno || !payload.id_rol || !payload.id_colegio) {
                Swal.showValidationMessage("Completa los campos obligatorios del formulario.");
                return false;
            }

            const verificacionEmail = await fetch(`modelos/rescatar/usuario_existente.php?email=${encodeURIComponent(payload.email)}`);
            const dataEmail = await verificacionEmail.json();
            if (dataEmail.ok && dataEmail.exists) {
                Swal.showValidationMessage(`Usuario ya existe: ${dataEmail.nombre_completo || "Usuario registrado"}.`);
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
            const data = resultado.value || {};
            const texto = data.mail_ok
                ? "La cuenta fue creada y se envio un correo de bienvenida con el enlace para definir la clave."
                : `La cuenta fue creada, pero el correo no se pudo enviar. ${data.mail_msg || "Revisa la configuracion SMTP."}`;

            Swal.fire({
                ...SWAL_SEDUC_CONFIG,
                title: "Usuario creado",
                text: texto,
                icon: data.mail_ok ? "success" : "warning",
                confirmButtonText: "Aceptar"
            })
                .then(() => window.location.reload());
        }
    });
}

async function abrirPermisosUsuario(idUsuario, nombreUsuario, emailUsuario, nombreColegio, logoColegio, idColegio) {
    const respuesta = await fetch(`modelos/rescatar/usuario_menu.php?id_usuario=${encodeURIComponent(idUsuario)}`);
    const data = await respuesta.json();

    if (!data.ok) {
        Swal.fire({
            ...SWAL_SEDUC_CONFIG,
            title: "Permisos",
            text: data.msg || "No se pudieron cargar los permisos.",
            icon: "error",
            confirmButtonText: "Aceptar"
        });
        return;
    }

    const permisos = Array.isArray(data.permisos) ? data.permisos : [];
    const nombreColegioSeguro = nombreColegio || "Sin colegio";
    const logoColegioHtml = logoColegio
        ? `<img class="colegio-avatar" src="${escapeHtml(logoColegio)}" alt="${escapeHtml(nombreColegioSeguro)}">`
        : `<span class="colegio-avatar colegio-avatar-fallback">${Number(idColegio) > 0 ? Number(idColegio) : "-"}</span>`;
    const html = `
        <div class="menu-permisos-modal">
            <div class="menu-permisos-head">
                <div class="menu-permisos-head-copy">
                    <strong>${escapeHtml(nombreUsuario || "Usuario")}</strong>
                    <span>${escapeHtml(emailUsuario || "Sin email")} · Activa o bloquea cada menú del sistema.</span>
                </div>
                <div class="menu-permisos-head-colegio">
                    ${logoColegioHtml}
                    <span class="colegio-nombre">${escapeHtml(nombreColegioSeguro)}</span>
                </div>
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
        ...SWAL_SEDUC_CONFIG,
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
        Swal.fire({
            ...SWAL_SEDUC_CONFIG,
            title: "Permisos actualizados",
            text: "Los menús del usuario fueron guardados.",
            icon: "success",
            confirmButtonText: "Aceptar"
        });
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

document.addEventListener("DOMContentLoaded", function () {
    const table = document.getElementById("usersTable");
    const statusHelpButtons = Array.from(document.querySelectorAll(".status-help-btn"));
    if (statusHelpButtons.length > 0) {
        function closeStatusPopovers(currentButton = null) {
            document.querySelectorAll(".status-popover.is-open").forEach((popover) => {
                if (currentButton && popover.previousElementSibling === currentButton) return;
                popover.classList.remove("is-open");
            });
        }

        statusHelpButtons.forEach((button) => {
            button.addEventListener("click", function (event) {
                event.preventDefault();
                event.stopPropagation();
                const popover = button.nextElementSibling;
                if (!popover || !popover.classList.contains("status-popover")) return;

                const willOpen = !popover.classList.contains("is-open");
                closeStatusPopovers(button);
                popover.classList.toggle("is-open", willOpen);
            });
        });

        document.addEventListener("click", function () {
            closeStatusPopovers();
        });
    }

    if (!table) return;

    const tbody = table.querySelector("tbody");
    const headers = Array.from(table.querySelectorAll("thead th.sortable"));
    let sortState = { index: -1, dir: "asc", type: "text" };

    function normalize(text) {
        return String(text ?? "")
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
    }

    function parseValue(value, type) {
        const raw = String(value ?? "").trim();
        if (type === "number") return Number(raw) || 0;
        return normalize(raw);
    }

    function sortRows(index, type) {
        const rows = Array.from(tbody.querySelectorAll("tr"));
        const dir = sortState.index === index && sortState.dir === "asc" ? "desc" : "asc";
        sortState = { index, dir, type };

        headers.forEach((th, idx) => {
            th.classList.remove("is-sorted", "sort-asc", "sort-desc");
            if (idx === index) {
                th.classList.add("is-sorted", dir === "asc" ? "sort-asc" : "sort-desc");
            }
        });

        rows.sort((a, b) => {
            const aVal = parseValue(a.children[index]?.getAttribute("data-value") ?? a.children[index]?.textContent, type);
            const bVal = parseValue(b.children[index]?.getAttribute("data-value") ?? b.children[index]?.textContent, type);

            if (aVal < bVal) return dir === "asc" ? -1 : 1;
            if (aVal > bVal) return dir === "asc" ? 1 : -1;
            return 0;
        });

        rows.forEach((row) => tbody.appendChild(row));
    }

    headers.forEach((th, index) => {
        th.addEventListener("click", () => sortRows(index, th.dataset.type || "text"));
    });
});

updateHeaderDateTime();
setInterval(updateHeaderDateTime, 1000);
</script>
</body>
</html>
