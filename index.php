<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/class/conexion.php";
require_once __DIR__ . "/class/funciones.php";

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");

$funciones = new Funciones($db);
$dias = $funciones->obtenerDiasSemana(true); // lunes a viernes
$empleados = $funciones->obtenerEmpleadosConResumen($_SESSION["id_colegio"]);


function hhmm($hoursFloat){
  $totalMin = (int)round($hoursFloat * 60);
  $h = floor($totalMin/60);
  $m = $totalMin % 60;
  return str_pad($h,2,'0',STR_PAD_LEFT).":".str_pad($m,2,'0',STR_PAD_LEFT);
}
?>





<!doctype html>
<html lang="es">

<head>
    <link rel="stylesheet" type="text/css" href="css/principal.css">
    <link rel="stylesheet" type="text/css" href="css/menu_lateral.css">
    <link rel="stylesheet" type="text/css" href="css/modales.css">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Calculadora de Horas Cronológicas</title>
    <script>
    const DIAS_LV = <?= json_encode($dias); ?>;
    </script>

</head>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script><!-- SweetAlert2 -->
<link rel="icon" type="image/png" href="imagenes/logo_1.jpg" />
<script src="js/funciones.js"></script>
<script src="js/button.js"></script>
<script src="js/guardar_empleado.js"></script>
<script src="js/tabla_dinamicas.js"></script>

<style>
/* input| */
/* CONTENEDOR */
.swal-form-modern {
    width: 100%;
    padding-top: 5px;
}

/* GRID 2 COLUMNAS */
.swal-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px 18px;
}

/* CAMPOS */
.swal-field {
    display: flex;
    flex-direction: column;
    text-align: left;
}

.swal-field label {
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 6px;
    color: #374151;
}

/* INPUTS MODERNOS */
.swal-input-modern {
    height: 40px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    padding: 0 12px;
    font-size: 14px;
    transition: all 0.2s ease;
    background: #f9fafb;
}

.swal-input-modern:focus {
    border-color: #2563eb;
    background: #ffffff;
    outline: none;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}

/* SELECT */
.swal-input-modern select {
    cursor: pointer;
}

/* GENERO FULL WIDTH */
.swal-full {
    margin-top: 18px;
}

/* RESPONSIVE */
@media (max-width: 600px) {
    .swal-grid-2 {
        grid-template-columns: 1fr;
    }
}
</style>
<div class="page">
    <?php include __DIR__ . "/menu_lateral.php"; ?>
    <!-- Menu lateral -->
    <header class="header">
        <div class="brand">
            <div class="logo">
                <img src="imagenes/logo_2.jpg" alt="Logo" onerror="this.style.display='none'">
            </div>
            <div class="titles">
                <h1>Calculadora de Horas Cronológicas</h1>
                <!-- <p>Distribución semanal (mañana / tarde) y resumen de totales</p> -->
                <!-- Usuario logueado -->
                <div class="user-info">
                    <i class="bi bi-person-circle"></i>
                    <span><?= htmlspecialchars($_SESSION["nombre_completo"]) ?></span>
                    <span class="sep">•</span>
                    <span><?= htmlspecialchars($_SESSION["nom_colegio"]) ?></span>
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


    <main class="content">
        <!-- TABLA -->
        <section class="card">
            <div class="card-head">
                <h2>Horario semanal</h2>
                <small>Selecciona hora de inicio y término por jornada</small>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th rowspan="2">Día</th>
                            <th colspan="2">Mañana</th>
                            <th colspan="2">Tarde</th>
                        </tr>
                        <tr>
                            <th>Inicio</th>
                            <th>Término</th>
                            <th>Inicio</th>
                            <th>Término</th>
                        </tr>
                    </thead>

                    <tbody id="tbodyHorario">
                        <!-- Se genera por JS (Lunes a Viernes) -->
                    </tbody>
                </table>
            </div>
        </section>

        <!-- RESUMEN -->
        <aside class="card">
            <div class="card-head">
                <h2>Resumen</h2>
                <small>Horas pedagógicas y cronológicas</small>
            </div>

            <div class="summary">

                <div class="summary-grid">
                    <div class="sum-row">
                        <div>
                            <div class="name">Jornada ordinaria</div>
                            <div class="hint">Total semanal</div>
                        </div>
                        <div class="box"><small>Pedagógicas</small><span id="sumJornadaPed">--:--</span> </div>
                        <div class="box"><small>Cronológicas</small><span id="sumJornadaCro">00:00</span></div>
                    </div>

                    <div class="sum-row editable-row">
                        <div>
                            <div class="name d-flex align-items-center gap-2">
                                Horas lectivas
                                <i class="bi bi-pencil-fill edit-row-icon" title="Editable"></i>
                            </div>
                            <div class="hint">Clases / aula</div>
                        </div>

                        <div class="box">
                            <small>Pedagógicas</small>
                            <input id="sumLectivasPed" class="sum-input" type="text" value="00:00" inputmode="numeric"
                                autocomplete="off">
                        </div>

                        <div class="box">
                            <small>Cronológicas</small>
                            <input id="sumLectivasCro" class="sum-input" type="text" value="00:00" inputmode="numeric"
                                autocomplete="off">
                        </div>
                    </div>




                    <div class="sum-row editable-row">
                        <div>
                            <div class="name d-flex align-items-center gap-2">
                                Horas lectivas
                                <i class="bi bi-pencil-fill edit-row-icon" title="Editar horas lectivas"></i>
                            </div>
                            <div class="hint">Clases / aula</div>
                        </div>

                        <!-- Pedagógicas -->
                        <div class="box">
                            <small>Pedagógicas</small>
                            <input id="sumLectivasPed" class="sum-input" type="text" value="00:00" inputmode="numeric"
                                autocomplete="off" aria-label="Horas lectivas pedagógicas">
                        </div>

                        <!-- Cronológicas -->
                        <div class="box">
                            <small>Cronológicas</small>
                            <input id="sumLectivasCro" class="sum-input" type="text" value="00:00" inputmode="numeric"
                                autocomplete="off" aria-label="Horas lectivas cronológicas">
                        </div>
                    </div>




                    <div class="sum-row">
                        <div>
                            <div class="name">Colación</div>
                            <div class="hint">Descuento diario</div>
                        </div>
                        <div class="box"><small>Minutos</small><span id="sumColacionMin">00</span></div>
                        <div class="box"><small>hh:mm</small><span id="sumColacionHHMM">00:00</span></div>
                    </div>

                    <div class="actions">
                        <button class="btn secondary" id="btnLimpiar" type="button">Limpiar</button>
                        <button class="btn" id="btnGuardar" type="button" onclick="guardarEmpleado()">Agregar</button>
                    </div>
                </div>
            </div>
        </aside>
    </main>
    <!-- TABLA DE EMPLEADOS -->
<div class="panel empleados-panel">

  <!-- ===== HEAD estilo “Horario semanal / Resumen” ===== -->
  <div class="panel-head panel-head-split">
    <div class="panel-head-top">
      <div class="panel-title">
        <span class="dot"></span>
        Empleados
      </div>

      <div class="panel-head-right">
        <div class="panel-subline">
        </div>
      </div>
    </div>

    <div class="panel-head-bar"></div>

    <div class="panel-head-actions">
      <div class="acciones-empleado">
        <div class="emp-search">
          <i class="bi bi-search"></i>
          <input id="empSearch" type="text" placeholder="Buscar por RUN, nombre, horas..." autocomplete="off">
          <button type="button" class="emp-clear" id="empClear" title="Limpiar">
            <i class="bi bi-x-circle"></i>
          </button>
        </div>

        <button type="button" class="btn-mini btn-excel" onclick="descargarExcel()">
          <i class="bi bi-file-earmark-excel-fill"></i>
          Excel
        </button>
      </div>
    </div>
  </div>

  <!-- ===== TABLE ===== -->
  <div class="emp-table-wrap">
    <table class="emp-table" id="empTable">
      <thead>
        <tr>
          <th class="sortable" data-type="number">N° <i class="bi bi-arrow-down-up sort-ico"></i></th>
          <th class="sortable" data-type="text">RUN <i class="bi bi-arrow-down-up sort-ico"></i></th>
          <th class="sortable" data-type="text">Nombre - Apellidos <i class="bi bi-arrow-down-up sort-ico"></i></th>

          <th class="sortable" data-type="time">
            <span class="th-flex">
              Jornada Ordinaria
            </span>
            <i class="bi bi-arrow-down-up sort-ico"></i>
          </th>

          <th class="sortable" data-type="minutes">
            <span class="th-flex">
              Colación
            </span>
            <i class="bi bi-arrow-down-up sort-ico"></i>
          </th>

          <th class="sortable" data-type="time">
            <span class="th-flex">
              Horas No Lectivas
            </span>
            <i class="bi bi-arrow-down-up sort-ico"></i>
          </th>

          <th class="sortable" data-type="time">
            <span class="th-flex">
              Horas Lectivas
            </span>
            <i class="bi bi-arrow-down-up sort-ico"></i>
          </th>

          <th class="sortable" data-type="text">Opciones <i class="bi bi-arrow-down-up sort-ico"></i></th>
        </tr>
      </thead>

      <tbody>
        <?php $contador = 1; foreach($empleados as $e):

          $nombre = trim($e['nombres'].' '.$e['apellido_paterno'].' '.$e['apellido_materno']);
          $idEmpleado  = (int)$e['id_empleado'];
          $idContrato  = (int)($e['id_contrato'] ?? 0);

          $jornada = (int)($e['horas_semanales_cron'] ?? 0);
          $jornadaTxt = str_pad($jornada, 2, '0', STR_PAD_LEFT) . ':00';

          $lectivasTxt   = isset($e['horas_pedagogicas_lectivas_hhmm']) ? $e['horas_pedagogicas_lectivas_hhmm'] : '00:00';
          $noLectivasTxt = isset($e['horas_no_lectivas_hhmm']) ? $e['horas_no_lectivas_hhmm'] : '00:00';

          $colacionMin = (int)($e['min_colacion_diaria'] ?? 0);
          $colacionTxt = $colacionMin . ' min';

          $obs = trim((string)($e['observacion'] ?? ''));
        ?>
        <tr data-filter="<?= htmlspecialchars(mb_strtolower($contador.' '.$e['run'].' '.$nombre.' '.$jornadaTxt.' '.$colacionTxt.' '.$noLectivasTxt.' '.$lectivasTxt), ENT_QUOTES) ?>">

          <td class="cell-num" data-col="N°" data-value="<?= $contador ?>"><?= $contador ?></td>

          <td class="cell-run" data-col="RUN" data-value="<?= htmlspecialchars($e['run'], ENT_QUOTES) ?>">
            <?= htmlspecialchars($e['run']) ?>
          </td>

          <td class="cell-nombre" data-col="Nombre" data-value="<?= htmlspecialchars($nombre, ENT_QUOTES) ?>">
            <?= htmlspecialchars($nombre) ?>
          </td>

          <td class="cell-center" data-col="Jornada Ordinaria" data-type="time" data-value="<?= htmlspecialchars($jornadaTxt, ENT_QUOTES) ?>">
            <div class="cell-copy">
              <span class="cell-val"><?= htmlspecialchars($jornadaTxt) ?></span>
              <button type="button" class="meta-copy" title="Copiar"
                onclick="copiarDato('<?= htmlspecialchars($jornadaTxt, ENT_QUOTES) ?>')">
                <i class="bi bi-clipboard"></i>
              </button>
            </div>
          </td>

          <td class="cell-center" data-col="Colación" data-type="minutes" data-value="<?= htmlspecialchars($colacionTxt, ENT_QUOTES) ?>">
            <div class="cell-copy">
              <span class="cell-val"><?= htmlspecialchars($colacionTxt) ?></span>
              <button type="button" class="meta-copy" title="Copiar"
                onclick="copiarDato('<?= htmlspecialchars($colacionTxt, ENT_QUOTES) ?>')">
                <i class="bi bi-clipboard"></i>
              </button>
            </div>
          </td>

          <td class="cell-center" data-col="Horas No Lectivas" data-type="time" data-value="<?= htmlspecialchars($noLectivasTxt, ENT_QUOTES) ?>">
            <div class="cell-copy">
              <span class="cell-val"><?= htmlspecialchars($noLectivasTxt) ?></span>
              <button type="button" class="meta-copy" title="Copiar"
                onclick="copiarDato('<?= htmlspecialchars($noLectivasTxt, ENT_QUOTES) ?>')">
                <i class="bi bi-clipboard"></i>
              </button>
            </div>
          </td>

          <td class="cell-center" data-col="Horas Lectivas" data-type="time" data-value="<?= htmlspecialchars($lectivasTxt, ENT_QUOTES) ?>">
            <div class="cell-copy">
              <span class="cell-val"><?= htmlspecialchars($lectivasTxt) ?></span>
              <button type="button" class="meta-copy" title="Copiar"
                onclick="copiarDato('<?= htmlspecialchars($lectivasTxt, ENT_QUOTES) ?>')">
                <i class="bi bi-clipboard"></i>
              </button>
            </div>
          </td>

          <td class="cell-opciones" data-col="Opciones" data-value="opciones">
            <div class="cell-actions">
              <button type="button" class="btn-table-icon" title="Cargar horario"
                onclick="seleccionarEmpleado(<?= $idEmpleado ?>, <?= $idContrato ?>)">
                <i class="bi bi-upload"></i>
              </button>

              <button type="button" class="btn-table-icon" title="Ver detalle"
                onclick="verDetalleHorario(<?= $idEmpleado ?>, <?= $idContrato ?>)">
                <i class="bi bi-eye"></i>
              </button>

              <button type="button" class="btn-table-icon" title="Observación"
                onclick="verObservacion(<?= $idEmpleado ?>, '<?= htmlspecialchars($nombre, ENT_QUOTES) ?>', '<?= htmlspecialchars($obs, ENT_QUOTES) ?>')">
                <i class="bi bi-file-text"></i>
              </button>
            </div>
          </td>

        </tr>
        <?php $contador++; endforeach; ?>
      </tbody>
    </table>
  </div>
</div>



</div>
<script>
function verDetalleHorario(idEmpleado, idContrato) {

    const hhmm = (t) => (!t || t === '00:00:00') ? '—' : t.substring(0, 5);

    const buildTable = (dias) => {
        let html = `
      <div class="table-responsive" style="margin-top:10px;">
        <table class="table table-sm table-bordered align-middle" style="margin:0;">
          <thead>
            <tr>
              <th>Día</th>
              <th>Inicio (Mañana)</th>
              <th>Término (Mañana)</th>
              <th>Inicio (Tarde)</th>
              <th>Término (Tarde)</th>
            </tr>
          </thead>
          <tbody>
    `;

        dias.forEach(r => {
            html += `
        <tr>
          <td><b>${r.nombre}</b></td>
          <td>${hhmm(r.man_ini)}</td>
          <td>${hhmm(r.man_fin)}</td>
          <td>${hhmm(r.tar_ini)}</td>
          <td>${hhmm(r.tar_fin)}</td>
        </tr>
      `;
        });

        html += `</tbody></table></div>`;
        return html;
    };

    Swal.fire({
        title: 'Detalle de horario',
        html: `
      <div style="display:flex;align-items:center;gap:10px;justify-content:center;padding:10px 0;">
        <div class="spinner-border" role="status" aria-hidden="true"></div>
      </div>
      <div id="swalHorarioDetalle"></div>
    `,
        showCancelButton: true,
        confirmButtonText: 'Cerrar',
        cancelButtonText: 'Descargar',
        width: '80%',
        customClass: {
            popup: 'swal-seduc',
            confirmButton: 'btn-seduc btn-seduc-primary',
            cancelButton: 'btn-seduc btn-seduc-ghost'
        },
        didOpen: () => {
            const fd = new FormData();
            fd.append('id_contrato', idContrato);

            fetch('modelos/rescatar/horarios.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    const el = document.getElementById('swalHorarioDetalle');
                    if (!data.ok) {
                        el.innerHTML =
                            `<div class="alert alert-danger" style="margin:0;">${data.msg || 'No se pudo cargar.'}</div>`;
                        return;
                    }
                    el.innerHTML = buildTable(data.dias);
                })
                .catch(() => {
                    document.getElementById('swalHorarioDetalle').innerHTML =
                        `<div class="alert alert-danger" style="margin:0;">Error de conexión al cargar el horario.</div>`;
                });
        }
    }).then((result) => {
        // Si presiona "Descargar" (cancel)
        if (result.dismiss === Swal.DismissReason.cancel) {
            descargarHorario(idContrato);
        }
    });
}

function descargarHorario(idContrato) {
    //  Excel por contrato
    window.location.href = 'descarga/horario_empleado.php?id_contrato=' + idContrato;

}

function copiarDato(texto) {
    navigator.clipboard.writeText(texto).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Copiado',
            text: texto,
            timer: 900,
            showConfirmButton: false,
            customClass: {
                popup: 'swal-seduc',
                confirmButton: 'btn-seduc btn-seduc-primary',
                cancelButton: 'btn-seduc btn-seduc-ghost'
            }
        });
    });
}

function verObservacion(idEmpleado, nombre, observacion) {
    Swal.fire({
        title: 'Observación',
        html: `
            <div style="text-align: left;">
                <p style="color: #6b7280; font-size: 14px; margin-bottom: 12px;"><strong>${nombre}</strong></p>
                <div style="width: 100%; min-height: 150px; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; background: #f9fafb; font-family: inherit; font-size: 13px; line-height: 1.5; word-wrap: break-word; white-space: pre-wrap;">${observacion || '<span style="color: #9ca3af;">Sin observación</span>'}</div>
            </div>
        `,
        confirmButtonText: 'Cerrar',
        customClass: {
            popup: 'swal-seduc',
            confirmButton: 'btn-seduc btn-seduc-primary'
        }
    });
}

function copiarDato(texto) {
    navigator.clipboard.writeText(texto).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Copiado',
            text: texto,
            timer: 900,
            showConfirmButton: false,
            customClass: {
                popup: 'swal-seduc',
                confirmButton: 'btn-seduc btn-seduc-primary',
                cancelButton: 'btn-seduc btn-seduc-ghost'
            }
        });
    });
}
</script>











<script>
/* =========================
   2) UTILIDADES
   ========================= */
const pad2 = (n) => String(n).padStart(2, "0"); //formatea 8 como "08", 0 como "00".

function buildOptions(range, step = 1) { // arma <option> para las horas (00–23).
    const frag = document.createDocumentFragment();
    for (let i = range.min; i <= range.max; i += step) {
        const opt = document.createElement("option");
        opt.value = pad2(i);
        opt.textContent = pad2(i);
        frag.appendChild(opt);
    }
    return frag;
}

function createSelect({ //crea <select> de horas o minutos.
    name,
    type
}) {
    const sel = document.createElement("select");
    sel.name = name;

    if (type === "h") {
        sel.appendChild(buildOptions({
            min: 0,
            max: 23
        }, 1));
    } else {
        sel.classList.add("sel-min");
        // minutos en saltos de 5 (ajusta a 15 si prefieres)
        for (let m = 0; m <= 55; m += 5) {
            const opt = document.createElement("option");
            opt.value = pad2(m);
            opt.textContent = pad2(m);
            sel.appendChild(opt);
        }
    }
    // valor inicial 00
    sel.value = "00";
    return sel;
}

function timePicker(prefix, bloque, tipo) { //arma el “control” HH : MM
    // bloque: man | tar
    // tipo: ini | fin
    const wrap = document.createElement("div");
    wrap.className = "slot";

    const box = document.createElement("div");
    box.className = "time";

    const selH = createSelect({
        name: `${prefix}_${bloque}_${tipo}_h`,
        type: "h"
    });
    const selM = createSelect({
        name: `${prefix}_${bloque}_${tipo}_m`,
        type: "m"
    });

    const sep = document.createElement("span");
    sep.className = "sep";
    sep.textContent = ":";

    box.appendChild(selH);
    box.appendChild(sep);
    box.appendChild(selM);
    wrap.appendChild(box);

    return wrap;
}

function buildRow(dia) { // arma una fila completa del día (mañana ini/fin, tarde ini/fin).
    const tr = document.createElement("tr");

    const th = document.createElement("th");
    th.textContent = dia.label;
    tr.appendChild(th);

    const td1 = document.createElement("td");
    td1.appendChild(timePicker(dia.prefix, "man", "ini"));
    tr.appendChild(td1);

    const td2 = document.createElement("td");
    td2.appendChild(timePicker(dia.prefix, "man", "fin"));
    tr.appendChild(td2);

    const td3 = document.createElement("td");
    td3.appendChild(timePicker(dia.prefix, "tar", "ini"));
    tr.appendChild(td3);

    const td4 = document.createElement("td");
    td4.appendChild(timePicker(dia.prefix, "tar", "fin"));
    tr.appendChild(td4);

    return tr;
}
</script>




<script>
// esto es solo para mostrar la fecha y hora actual en el header, 
// se actualiza cada segundo para mantener la hora al día.
// Se formatea según la localización "es-CL" (español de Chile) 
// para mostrar el día de la semana, fecha completa y hora con formato de 24 horas.
/* =========================
   3) FECHA/HORA HEADER
   ========================= */
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

/* =========================
   4) INIT   ()
   ========================= */
function init() {
    // fecha/hora
    updateHeaderDateTime();
    setInterval(updateHeaderDateTime, 1000);

    // generar filas L-V
    const tbody = document.getElementById("tbodyHorario");
    const frag = document.createDocumentFragment();
    DIAS_LV.forEach(d => frag.appendChild(buildRow(d)));
    tbody.appendChild(frag);

    // botones (solo demo)
    document.getElementById("btnLimpiar").addEventListener("click", () => {
        document.querySelectorAll("#tbodyHorario select").forEach(s => s.value = "00");
    });


}

init();
</script>






<!-- ***************************************************************** -->
<!-- se activan con el archivo funciones.js -->
<!-- ***************************************************************** -->

<!--  para que se marquen las hora inferiores automaticamente  -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    bindAutoFillHorario({
        tbodySelector: "#tbodyHorario",
        dayPrefixes: ["lun", "mar", "mie", "jue", "vie"],
        onlyIfEmpty: false // true si NO quieres pisar valores ya puestos
    });
});
</script>

<!--calculo de horas de colacion -->
<!--calculo de horas Cronologicas-->
<script>
document.addEventListener("DOMContentLoaded", function() {

    // 1) Auto-fill (si ya lo estás usando)
    if (window.bindAutoFillHorario) {
        bindAutoFillHorario({
            tbodySelector: "#tbodyHorario",
            dayPrefixes: ["lun", "mar", "mie", "jue", "vie"],
            onlyIfEmpty: false
        });
    }

    // 2) Re-cálculo jornada + colación + resumen
    bindRecalculoHorario({
        tbodySelector: "#tbodyHorario",
        dayPrefixes: ["lun", "mar", "mie", "jue", "vie"]
    });

});
</script>

<!-- algo deberia hacer enn lso inpit de horas lectivas para que al hacer click en el i
 cono de lapiz se active el input y se pueda escribir, ademas de que al hacer click fuera del 
 input se guarde el valor y se actualice el resumen, esto para las horas pedagógicas y cronológicas lectivas -->

<script>
document.addEventListener("click", function(e) {
    const icon = e.target.closest(".edit-row-icon");
    if (!icon) return;

    const row = icon.closest(".editable-row");
    if (!row) return;

    const firstInput = row.querySelector(".sum-input");
    if (firstInput) firstInput.focus();
});
</script>














<script>
(function() {
    const table = document.getElementById('empTable');
    if (!table) return;

    const tbody = table.querySelector('tbody');
    const search = document.getElementById('empSearch');
    const clearBtn = document.getElementById('empClear');
    const ths = Array.from(table.querySelectorAll('thead th.sortable'));

    let sortState = {
        index: -1,
        dir: 'asc',
        type: 'text'
    };

    // ---- helpers
    function toast(msg) {
        let t = document.querySelector('.emp-toast');
        if (!t) {
            t = document.createElement('div');
            t.className = 'emp-toast';
            document.body.appendChild(t);
        }
        t.textContent = msg;
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 1400);
    }

    async function copyText(text) {
        try {
            await navigator.clipboard.writeText(text);
            toast('Copiado ✅');
        } catch (e) {
            // fallback
            const ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            ta.remove();
            toast('Copiado ✅');
        }
    }

    function normalize(s) {
        return (s ?? '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, ''); // quita acentos
    }

    function timeToMinutes(hhmm) {
        // "40:00" => 2400
        const m = (hhmm || '00:00').match(/^(\d{1,3}):(\d{2})$/);
        if (!m) return 0;
        return (parseInt(m[1], 10) * 60) + parseInt(m[2], 10);
    }

    function minutesTextToNumber(txt) {
        // "45 min" => 45
        const m = (txt || '').match(/-?\d+/);
        return m ? parseInt(m[0], 10) : 0;
    }

    function getCellValue(tr, index) {
        const td = tr.children[index];
        if (!td) return '';
        return td.getAttribute('data-value') || td.textContent.trim();
    }

    function compare(a, b, type) {
        if (type === 'number') {
            return (parseFloat(a) || 0) - (parseFloat(b) || 0);
        }
        if (type === 'time') {
            return timeToMinutes(a) - timeToMinutes(b);
        }
        if (type === 'minutes') {
            return minutesTextToNumber(a) - minutesTextToNumber(b);
        }
        // text
        a = normalize(a);
        b = normalize(b);
        return a.localeCompare(b, 'es');
    }

    function applyFilter() {
        const q = normalize(search.value.trim());
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.forEach(tr => {
            const hay = normalize(tr.getAttribute('data-filter') || tr.textContent);
            tr.style.display = (q === '' || hay.includes(q)) ? '' : 'none';
        });
    }

    function applySort(thIndex, type) {
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const visibleRows = rows.filter(r => r.style.display !== 'none');

        // toggle dir
        if (sortState.index === thIndex) {
            sortState.dir = (sortState.dir === 'asc') ? 'desc' : 'asc';
        } else {
            sortState.index = thIndex;
            sortState.dir = 'asc';
            sortState.type = type || 'text';
        }

        // UI th classes
        ths.forEach((th, i) => {
            th.classList.remove('is-sorted', 'sort-asc', 'sort-desc');
            if (i === sortState.index) {
                th.classList.add('is-sorted', sortState.dir === 'asc' ? 'sort-asc' : 'sort-desc');
            }
        });

        visibleRows.sort((r1, r2) => {
            const v1 = getCellValue(r1, thIndex);
            const v2 = getCellValue(r2, thIndex);
            const c = compare(v1, v2, sortState.type);
            return sortState.dir === 'asc' ? c : -c;
        });

        // reinsert (manteniendo filas ocultas al final sin tocar)
        visibleRows.forEach(r => tbody.appendChild(r));
    }

    // ---- Search events
    search?.addEventListener('input', applyFilter);
    clearBtn?.addEventListener('click', () => {
        search.value = '';
        applyFilter();
        search.focus();
    });

    // ---- Sort events
    ths.forEach((th, index) => {
        const type = th.getAttribute('data-type') || 'text';
        th.addEventListener('click', (ev) => {
            // si clickeas un botón dentro del TH (copiar columna), no ordenar
            if (ev.target.closest('button')) return;
            applySort(index, type);
        });

        // Copiar columna completa
        const btn = th.querySelector('.th-copy-all');
        if (btn) {
            btn.addEventListener('click', async () => {
                const colName = th.innerText.replace(/\s+/g, ' ').trim();
                const rows = Array.from(tbody.querySelectorAll('tr')).filter(r => r.style
                    .display !== 'none');
                const values = rows.map(r => getCellValue(r, index));
                const text = colName + "\n" + values.join("\n");
                await copyText(text);
            });
        }
    });

    // ---- Copy cell events (delegation)
    table.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-copy');
        if (!btn) return;
        const td = btn.closest('td');
        const val = td?.getAttribute('data-value') || td?.innerText.trim() || '';
        await copyText(val);
    });

    // ---- Default sort (opcional): por N°
    applySort(0, 'number');

})();
</script>
</body>

</html>