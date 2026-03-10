<?php
/**
 * menu_lateral.php
 * Barra lateral izquierda flotante (3 botones).
 * - Diseño tipo botonera vertical.
 * - Acciones: scroll a secciones (puedes reemplazar por tus funciones).
 */
$idColegio = $_SESSION["id_colegio"];
$esSuperAdmin = !empty($_SESSION["is_super_admin"]);
$whereColegio = $esSuperAdmin ? "1=1" : "id_colegio = " . (int)$idColegio;

$sqlTotal = "
SELECT COUNT(*) AS total
FROM empleados
WHERE $whereColegio
";

$resTotal = $db->consulta($sqlTotal);
$rowTotal = $db->fetch_assoc($resTotal);
$totalEmpleados = $rowTotal['total'];
$mostrarUsuarios = !empty($_SESSION["is_super_admin"]) || (int)($_SESSION["id_rol"] ?? 0) === 1;
$totalUsuarios = 0;
if ($mostrarUsuarios) {
    $sqlTotalUsuarios = "
    SELECT COUNT(*) AS total
    FROM usuarios
    WHERE " . ($esSuperAdmin ? "1=1" : "id_colegio = " . (int)$idColegio);

    $resTotalUsuarios = $db->consulta($sqlTotalUsuarios);
    $rowTotalUsuarios = $db->fetch_assoc($resTotalUsuarios);
    $totalUsuarios = (int)($rowTotalUsuarios['total'] ?? 0);
}
$paginaActual = basename($_SERVER["PHP_SELF"] ?? "");
$activoEmpleados = $paginaActual === "index.php";
$activoGraficos = $paginaActual === "grafico.php";
$activoUsuarios = $paginaActual === "usuarios.php";

?>

<div class="side-mini" aria-label="Menú lateral">
    <!-- Empleados -->
    <div class="side-btn-wrap">
        <button class="side-btn<?= $activoEmpleados ? ' active' : '' ?>" id="btnSideEmpleados" type="button" title="Empleados (lista y selección)"
            onclick="window.location.href='index.php'">
            <i class="bi bi-people-fill"></i>
        </button>
        <!-- ejemplo badge (si quieres mostrar total) -->
        <div class="side-badge" id="badgeEmpleados" title="Total empleados">
            <?= $totalEmpleados ?>
        </div>
    </div>

    <!-- Horario -->
    <!-- <div class="side-btn-wrap">
        <button class="side-btn" id="btnSideHorario" type="button" title="Horario (copiar / limpiar / guardar)"
            onclick="sideGo('horario')">
            <i class="bi bi-calendar2-week-fill"></i>
        </button>
    </div> -->

    <!-- Reportes -->
    <div class="side-btn-wrap">
        <button class="side-btn<?= $activoGraficos ? ' active' : '' ?>" id="btnSideReportes" type="button" title="Gráficos y estadísticas"
            onclick="window.location.href='grafico.php'">
            <i class="bi bi-bar-chart-fill"></i>
        </button>
    </div>

    <?php if ($mostrarUsuarios): ?>
    <div class="side-btn-wrap">
        <button class="side-btn<?= $activoUsuarios ? ' active' : '' ?>" id="btnSideUsuarios" type="button" title="Agregar usuario y revisar permisos"
            onclick="window.location.href='usuarios.php'">
            <i class="bi bi-person-plus-fill"></i>
        </button>
        <div class="side-badge" id="badgeUsuarios" title="Total usuarios">
            <?= $totalUsuarios ?>
        </div>
    </div>
    <?php endif; ?>
    <!--SALIR -->
    <div class="side-btn-wrap">


        <div class="side-btn-wrap">
            <button class="side-btn side-btn-logout" id="btnSideLogout" type="button" title="Salir del sistema"
                onclick="confirmarSalir()">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </div>

    </div>
</div>

<script>
// Cambia el "active" visual
function sideSetActive(key) {
    const map = {
        empleados: "btnSideEmpleados",
        reportes: "btnSideReportes",
        usuarios: "btnSideUsuarios",
        horario: "btnSideHorario"
    };
    Object.values(map).forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.remove("active");
    });
    const active = document.getElementById(map[key]);
    if (active) active.classList.add("active");
}

// Acciones (por ahora scroll, después lo conectamos a tus funciones)
function sideGo(key) {
    sideSetActive(key);

    // 1) Empleados: baja a tu acordeón/lista
    if (key === "empleados") {
        const el = document.getElementById("empAccordion") || document.getElementById("panelEmpleados");
        if (el) return el.scrollIntoView({
            behavior: "smooth",
            block: "start"
        });
        return;
    }

    // 2) Horario: sube a la tabla de horario
    if (key === "horario") {
        const el = document.querySelector(".tabla-horario") || document.getElementById("tablaHorario") || document
            .querySelector("table");
        if (el) return el.scrollIntoView({
            behavior: "smooth",
            block: "start"
        });
        return;
    }
}


</script>
