<?php
/**
 * menu_lateral.php
 * Barra lateral izquierda flotante (3 botones).
 * - Diseño tipo botonera vertical.
 * - Acciones: scroll a secciones (puedes reemplazar por tus funciones).
 */
$idColegio = $_SESSION["id_colegio"];

$sqlTotal = "
SELECT COUNT(*) AS total
FROM empleados
WHERE id_colegio = $idColegio
";

$resTotal = $db->consulta($sqlTotal);
$rowTotal = $db->fetch_assoc($resTotal);
$totalEmpleados = $rowTotal['total'];

?>

<div class="side-mini" aria-label="Menú lateral">
    <!-- Empleados -->
    <div class="side-btn-wrap">
        <button class="side-btn active" id="btnSideEmpleados" type="button" title="Empleados (lista y selección)"
            onclick="window.location.href='index.php'">
            <i class="bi bi-people-fill"></i>
        </button>
        <!-- ejemplo badge (si quieres mostrar total) -->
        <div class="side-badge" id="badgeEmpleados" title="Total empleados">
            <?= $totalEmpleados ?>
        </div>
    </div>

    <!-- Horario -->
    <div class="side-btn-wrap">
        <button class="side-btn" id="btnSideHorario" type="button" title="Horario (copiar / limpiar / guardar)"
            onclick="sideGo('horario')">
            <i class="bi bi-calendar2-week-fill"></i>
        </button>
    </div>

    <!-- Reportes -->
    <div class="side-btn-wrap">
        <button class="side-btn" id="btnSideReportes" type="button" title="Gráficos y estadísticas"
            onclick="window.location.href='graficos.php'">
            <i class="bi bi-bar-chart-fill"></i>
        </button>
    </div>
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

function confirmarSalir() {
    Swal.fire({
        title: '¿Salir del sistema?',
        html: `
      <div>
        Se cerrará tu sesión por seguridad.<br>
        ¿Deseas continuar?
      </div>
    `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, salir',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        focusCancel: true,
        backdrop: 'rgba(15, 23, 42, .35)',
        customClass: {
            popup: 'swal-seduc',
            confirmButton: 'btn-seduc btn-seduc-primary',
            cancelButton: 'btn-seduc btn-seduc-ghost'
        }
    }).then((r) => {
        if (r.isConfirmed) window.location.href = 'logout.php';
    });
}
</script>