<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/class/conexion.php";
require_once __DIR__ . "/class/funciones.php";
require_once __DIR__ . "/class/helpers.php";

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");

$funciones = new Funciones($db);
$menusPermitidosActual = $funciones->obtenerCodigosMenusPermitidosUsuario((int)($_SESSION["id_usuario"] ?? 0));
if (!in_array('empleados', $menusPermitidosActual, true)) {
    if (in_array('graficos', $menusPermitidosActual, true)) {
        header("Location: grafico.php");
    } elseif (in_array('usuarios', $menusPermitidosActual, true)) {
        header("Location: usuarios.php");
    } else {
        header("Location: logout.php");
    }
    exit;
}
$dias = $funciones->obtenerDiasSemana(true); // lunes a viernes
$colaciones = $funciones->obtenerOpcionesColacion();
$idUsuarioSesion = (int)($_SESSION["id_usuario"] ?? 0);
$esSuperAdminOperativo = $funciones->usuarioTieneRol($idUsuarioSesion, 1);
$verTodosColegios = $esSuperAdminOperativo;
$empleados = $funciones->obtenerEmpleadosConResumen($_SESSION["id_colegio"], $verTodosColegios);
$mostrarColumnaColegio = $esSuperAdminOperativo;
$colegios = $verTodosColegios ? $funciones->obtenerColegios() : [];
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

?>





<!doctype html>
<html lang="es">

<head>
    <link rel="stylesheet" type="text/css" href="css/principal.css?v=<?= filemtime(__DIR__ . '/css/principal.css') ?>">
    <link rel="stylesheet" type="text/css" href="css/menu_lateral.css?v=<?= filemtime(__DIR__ . '/css/menu_lateral.css') ?>">
    <link rel="stylesheet" type="text/css" href="css/modales.css?v=<?= filemtime(__DIR__ . '/css/modales.css') ?>">
    <link rel="stylesheet" type="text/css" href="css/index.css?v=<?= filemtime(__DIR__ . '/css/index.css') ?>">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Calculadora de Horas Cronológicas</title>
    <script>
    const DIAS_LV = <?= json_encode($dias); ?>;
    const ID_USUARIO_SESION = <?= json_encode($idUsuarioSesion) ?>;
    const ES_SUPER_ADMIN_EMPLEADO = <?= json_encode($esSuperAdminOperativo) ?>;
    const COLEGIOS_EMPLEADO = <?= json_encode($colegios, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const COLEGIOS_LOGO_EMPLEADO = <?= json_encode($colegiosLogoMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>

</head>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script><!-- SweetAlert2 -->
<link rel="icon" type="image/png" href="imagenes/logo_1.jpg" />
<script src="js/funciones.js"></script>
<script src="js/button.js"></script>
<script src="js/guardar_empleado.js?v=<?= filemtime(__DIR__ . '/js/guardar_empleado.js') ?>"></script>
<script src="js/tabla_dinamicas.js"></script>
<script src="js/horas_cronologicas.js?v=<?= filemtime(__DIR__ . '/js/horas_cronologicas.js') ?>"></script>

<div class="page">
    <?php include __DIR__ . "/menu_lateral.php"; ?>
    <!-- Menu lateral -->
    <?php $headerTitle = "Calculadora de Horas Cronológicas"; ?>
    <?php include __DIR__ . "/header.php"; ?>


    <main class="content">
        <!-- TABLA -->
        <section class="card">
            <div class="card-head card-head-balanced">
                <div class="card-head-main">
                    <h2>Horario semanal</h2>
                    <small>Selecciona hora de inicio y término por jornada</small>
                </div>
                <div class="card-head-side">
                    <div class="horario-tools">
                    </div>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th rowspan="2">Día</th>
                            <th colspan="2">Mañana</th>
                            <th colspan="2">
                                <div style="display:flex;align-items:center;justify-content:center;gap:10px;">
                                    <span>Tarde</span>
                                    <!-- <label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;cursor:pointer;">
                                        <input type="checkbox" id="repeatScheduleDownToggle" aria-label="Repetir horario hacia abajo">
                                        <span>Repetir hacia abajo</span>
                                    </label> -->
                                </div>
                            </th>
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

            <div id="jornadaLegalMsg" class="jornada-legal-msg is-low is-hidden" role="status" aria-live="polite">
                Está bajo las 40 horas legales (Ley 21.561).
            </div>
            <div id="horarioIgualMsg" class="horario-equal-msg is-hidden" role="status" aria-live="polite">
                Advertencia: hay bloques con hora de inicio y término iguales. Revisa posible error de tipeo.
            </div>
            <div id="horarioDiasBloqueadosMsg" class="horario-equal-msg is-hidden" role="status" aria-live="polite"></div>
            <div id="horasLectivasMsg" class="horas-lectivas-msg is-hidden" role="status" aria-live="polite"></div>
        </section>

        <!-- RESUMEN -->
        <aside class="card">
            <div class="card-head card-head-balanced">
                <div class="card-head-main">
                    <h2>Resumen</h2>
                    <!-- <small>Horas pedagógicas y cronológicas</small> -->
                </div>
                <div class="card-head-side">
                    <div id="empleadoSeleccionadoInfo" class="empleado-seleccionado-info">
                        Sin empleado seleccionado
                    </div>
                </div>
            </div>

            <div class="summary">

                <div class="summary-grid">
                    <div class="sum-row">
                        <div>
                            <div class="name">Jornada ordinaria</div>
                            <div class="hint">Total semanal</div>
                        </div>
                        <div class="box"><small>Pedagógicas</small><span id="sumJornadaPed">0</span> </div>
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
                            <input id="sumLectivasPed" class="sum-input" type="text" value="0" inputmode="decimal"
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
                                Horas No lectivas
                                <i class="bi bi-pencil-fill edit-row-icon" title="Editar horas lectivas"></i>
                            </div>
                            <div class="hint">Clases / aula</div>
                        </div>

                        <!-- Pedagógicas -->
                        <div class="box">
                            <small>Pedagógicas</small>
                            <input id="sumNoLectivasPed" class="sum-input" type="text" value="0" inputmode="decimal"
                                autocomplete="off" aria-label="Horas no lectivas pedagógicas">
                        </div>

                        <!-- Cronológicas -->
                        <div class="box">
                            <small>Cronológicas</small>
                            <input id="sumNoLectivasCro" class="sum-input" type="text" value="00:00" inputmode="numeric"
                                autocomplete="off" aria-label="Horas no lectivas cronológicas">
                        </div>
                    </div>




                    <div class="sum-row">
                        <div>
                            <div class="name">Colación</div>
                            <!-- <div class="hint">Descuento diario</div> -->
                        </div>
                        <!-- <div class="box"><small>Minutos</small><span id="sumColacionMin">0</span></div> -->
                        <div class="box" style="width:200%;">
                            <small>Minutos</small>
                            <select id="sumColacionSelect" class="sum-input" aria-label="Colación diaria en minutos">
                                <option value="" selected disabled>Selecciona...</option>
                                <?php foreach ($colaciones as $col): ?>
                                    <?php
                                        $idCol = (int)($col['id_colacion'] ?? 0);
                                        $min = (int)($col['minutos'] ?? 0);
                                    ?>
                                    <option
                                        value="<?= $idCol ?>"
                                        data-minutos="<?= $min ?>">
                                        <?= htmlspecialchars((string)$min, ENT_QUOTES) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
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
        Funcionarios
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
          <?php if ($mostrarColumnaColegio): ?>
          <th class="sortable" data-type="text">Colegio <i class="bi bi-arrow-down-up sort-ico"></i></th>
          <?php endif; ?>

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

          $nombresEmp = trim((string)($e['nombres'] ?? ''));
          $apPatEmp = trim((string)($e['apellido_paterno'] ?? ''));
          $apMatEmp = trim((string)($e['apellido_materno'] ?? ''));
          $runEmp = trim((string)($e['run'] ?? ''));
          $generoEmp = trim((string)($e['genero'] ?? ''));
          $idColegioEmp = (int)($e['id_colegio'] ?? 0);
          $nomColegioEmp = trim((string)($e['nco_colegio'] ?? ($e['nom_colegio'] ?? '')));
          $logoColegioRel = "";
          $logoColegioExiste = false;
          if ($idColegioEmp > 0) {
            foreach (["png", "jpg", "jpeg"] as $extLogo) {
              $logoRelTmp = "imagenes/colegios/colegio_" . $idColegioEmp . "." . $extLogo;
              $logoAbsTmp = __DIR__ . "/" . $logoRelTmp;
              if (is_file($logoAbsTmp)) {
                $logoColegioRel = $logoRelTmp;
                $logoColegioExiste = true;
                break;
              }
            }
          }
          $nombre = trim($nombresEmp.' '.$apPatEmp.' '.$apMatEmp);
          $idEmpleado  = (int)$e['id_empleado'];
          $idContrato  = (int)($e['id_contrato'] ?? 0);

          $jornadaMin = (int)($e['horas_semanales_cron'] ?? 0);
          $jornadaTxt = minutosAHHMM($jornadaMin);

          $lectivasTxt   = isset($e['horas_lectivas_hhmm']) ? $e['horas_lectivas_hhmm'] : '00:00';
          $noLectivasTxt = isset($e['horas_no_lectivas_hhmm']) ? $e['horas_no_lectivas_hhmm'] : '00:00';

          $colacionMin = (int)($e['min_colacion_diaria'] ?? 0);
          $colacionTxt = $colacionMin . ' min';

          $obs = trim((string)($e['observacion'] ?? ''));
        ?>
        <tr data-filter="<?= htmlspecialchars(mb_strtolower($contador.' '.$runEmp.' '.$nombre.' '.$nomColegioEmp.' '.$jornadaTxt.' '.$colacionTxt.' '.$noLectivasTxt.' '.$lectivasTxt), ENT_QUOTES) ?>">

          <td class="cell-num" data-col="N°" data-value="<?= $contador ?>"><?= $contador ?></td>

          <td class="cell-run" data-col="RUN" data-value="<?= htmlspecialchars($runEmp, ENT_QUOTES) ?>">
            <?= htmlspecialchars($runEmp) ?>
          </td>

          <td class="cell-nombre" data-col="Nombre" data-value="<?= htmlspecialchars($nombre, ENT_QUOTES) ?>">
            <?= htmlspecialchars($nombre) ?>
          </td>
          <?php if ($mostrarColumnaColegio): ?>
          <td class="cell-colegio" data-col="Colegio" data-value="<?= htmlspecialchars($nomColegioEmp, ENT_QUOTES) ?>">
            <div class="cell-colegio-wrap" title="<?= htmlspecialchars($nomColegioEmp !== '' ? $nomColegioEmp : ('Colegio ID ' . $idColegioEmp), ENT_QUOTES) ?>">
              <?php if ($logoColegioExiste): ?>
              <img
                src="<?= htmlspecialchars($logoColegioRel, ENT_QUOTES) ?>"
                alt="<?= htmlspecialchars($nomColegioEmp !== '' ? $nomColegioEmp : ('Colegio ' . $idColegioEmp), ENT_QUOTES) ?>"
                class="colegio-avatar"
                loading="lazy">
              <?php else: ?>
              <span class="colegio-avatar colegio-avatar-fallback"><?= $idColegioEmp > 0 ? $idColegioEmp : '?' ?></span>
              <?php endif; ?>
              <span class="colegio-nombre"><?= htmlspecialchars($nomColegioEmp !== '' ? $nomColegioEmp : ('Colegio ' . $idColegioEmp)) ?></span>
            </div>
          </td>
          <?php endif; ?>

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
                data-id-empleado="<?= $idEmpleado ?>"
                data-id-contrato="<?= $idContrato ?>"
                data-empleado-nombre="<?= htmlspecialchars($nombre, ENT_QUOTES) ?>"
                data-empleado-nombres="<?= htmlspecialchars($nombresEmp, ENT_QUOTES) ?>"
                data-empleado-ap-paterno="<?= htmlspecialchars($apPatEmp, ENT_QUOTES) ?>"
                data-empleado-ap-materno="<?= htmlspecialchars($apMatEmp, ENT_QUOTES) ?>"
                data-empleado-genero="<?= htmlspecialchars($generoEmp, ENT_QUOTES) ?>"
                data-empleado-observacion="<?= htmlspecialchars($obs, ENT_QUOTES) ?>"
                data-empleado-run="<?= htmlspecialchars($runEmp, ENT_QUOTES) ?>"
                data-jornada-cro="<?= htmlspecialchars($jornadaTxt, ENT_QUOTES) ?>"
                data-colacion-min="<?= (int)$colacionMin ?>"
                data-lectivas-cro="<?= htmlspecialchars($lectivasTxt, ENT_QUOTES) ?>"
                data-nolectivas-cro="<?= htmlspecialchars($noLectivasTxt, ENT_QUOTES) ?>"
                onclick="seleccionarEmpleado(event, this, <?= $idEmpleado ?>, <?= $idContrato ?>)">
                <i class="bi bi-upload"></i>
              </button>

              <button type="button" class="btn-table-icon" title="Ver detalle"
                onclick="verDetalleHorario(<?= $idEmpleado ?>, <?= $idContrato ?>)">
                <i class="bi bi-eye"></i>
              </button>

              <button type="button" class="btn-table-icon" title="Observación"
                onclick="verObservacion(<?= $idEmpleado ?>, '<?= htmlspecialchars($nombre, ENT_QUOTES) ?>')">
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
function normalizarDiaCodigo(codigo) {
    const txt = String(codigo || "")
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .trim();

    if (txt.startsWith("lun")) return "lun";
    if (txt.startsWith("mar")) return "mar";
    if (txt.startsWith("mie")) return "mie";
    if (txt.startsWith("jue")) return "jue";
    if (txt.startsWith("vie")) return "vie";
    return "";
}

function parseHHMMtoPedHours(hhmm) {
    const m = String(hhmm || "00:00").match(/^(\d{1,3}):(\d{2})$/);
    if (!m) return "0";
    const totalMin = (parseInt(m[1], 10) || 0) * 60 + (parseInt(m[2], 10) || 0);
    const ped = totalMin / 40;
    return Number.isInteger(ped) ? String(ped) : ped.toFixed(2).replace(/\.?0+$/, "");
}

function updateHorarioDiasBloqueadosMsg(dayLabels) {
    const msgEl = document.getElementById("horarioDiasBloqueadosMsg");
    if (!msgEl) return;

    const dias = Array.isArray(dayLabels) ? dayLabels.filter(Boolean) : [];
    if (!dias.length) {
        msgEl.classList.add("is-hidden");
        msgEl.textContent = "";
        return;
    }

    const plural = dias.length > 1;
    msgEl.textContent = plural
        ? `Advertencia: los días ${dias.join(", ")} no tienen horario asignado y fueron marcados como bloqueados.`
        : `Advertencia: el día ${dias[0]} no tiene horario asignado y fue marcado como bloqueado.`;
    msgEl.classList.remove("is-hidden");
}

function setAccionEmpleadoModo(isModificar) {
    const btn = document.getElementById("btnGuardar");
    if (!btn) return;
    btn.textContent = isModificar ? "Modificar" : "Agregar";
    btn.dataset.modo = isModificar ? "modificar" : "agregar";
}

function seleccionarEmpleado(ev, triggerBtn, idEmpleado, idContrato) {
    if (ev && typeof ev.preventDefault === "function") ev.preventDefault();
    if (ev && typeof ev.stopPropagation === "function") ev.stopPropagation();

    const empleadoId = Number(idEmpleado) || 0;
    const contratoId = Number(idContrato) || 0;
    if (empleadoId <= 0 && contratoId <= 0) {
        Swal.fire("Error", "No se pudo identificar al empleado/contrato.", "error");
        return;
    }

    const nombre = triggerBtn?.dataset?.empleadoNombre || "Empleado";
    const nombres = triggerBtn?.dataset?.empleadoNombres || "";
    const apPaterno = triggerBtn?.dataset?.empleadoApPaterno || "";
    const apMaterno = triggerBtn?.dataset?.empleadoApMaterno || "";
    const genero = triggerBtn?.dataset?.empleadoGenero || "";
    const observacion = triggerBtn?.dataset?.empleadoObservacion || "";
    const run = triggerBtn?.dataset?.empleadoRun || "-";
    const jornadaCro = triggerBtn?.dataset?.jornadaCro || "00:00";
    const lectivasCro = triggerBtn?.dataset?.lectivasCro || "00:00";
    const noLectivasCro = triggerBtn?.dataset?.nolectivasCro || "00:00";
    const colacionMin = parseInt(triggerBtn?.dataset?.colacionMin || "0", 10) || 0;

    const infoEl = document.getElementById("empleadoSeleccionadoInfo");
    if (infoEl) {
        infoEl.textContent = `${nombre} | RUN: ${run}`;
        infoEl.title = `${nombre} | RUN: ${run}`;
    }
    setAccionEmpleadoModo(true);

    window.empleadoSeleccionadoPrefill = {
        id_empleado: empleadoId,
        id_contrato: contratoId,
        nombres,
        ap_paterno: apPaterno,
        ap_materno: apMaterno,
        run,
        genero,
        observacion
    };

    const selectColacion = document.getElementById("sumColacionSelect");
    if (selectColacion) {
        const option = Array.from(selectColacion.options).find(opt => {
            const min = parseInt(opt.getAttribute("data-minutos") || "0", 10) || 0;
            return min === colacionMin;
        });
        if (option) {
            selectColacion.value = option.value;
        } else {
            selectColacion.value = "";
        }
        selectColacion.dispatchEvent(new Event("change", {
            bubbles: true
        }));
    }

    const elJornadaCro = document.getElementById("sumJornadaCro");
    const elLectivasCro = document.getElementById("sumLectivasCro");
    const elNoLectivasCro = document.getElementById("sumNoLectivasCro");
    const elLectivasPed = document.getElementById("sumLectivasPed");
    const elNoLectivasPed = document.getElementById("sumNoLectivasPed");

    if (elJornadaCro) elJornadaCro.textContent = jornadaCro;
    if (elLectivasCro) elLectivasCro.value = lectivasCro;
    if (elNoLectivasCro) elNoLectivasCro.value = noLectivasCro;
    if (elLectivasPed) elLectivasPed.value = parseHHMMtoPedHours(lectivasCro);
    if (elNoLectivasPed) elNoLectivasPed.value = parseHHMMtoPedHours(noLectivasCro);
    if (typeof window.recalcularLectivas === "function") window.recalcularLectivas();
    if (typeof window.recalcularNoLectivas === "function") window.recalcularNoLectivas();
    if (typeof window.updateHorasLectivasUI === "function") window.updateHorasLectivasUI();
    requestAnimationFrame(() => window.scrollTo({
        top: 0,
        behavior: "smooth"
    }));

    const tbody = document.getElementById("tbodyHorario");
    if (!tbody) return;
    updateHorarioDiasBloqueadosMsg([]);

    document.querySelectorAll(".day-lock-check").forEach(check => {
        if (!check.checked) return;
        check.checked = false;
        check.dispatchEvent(new Event("change", {
            bubbles: true
        }));
    });

    tbody.querySelectorAll("select").forEach(sel => {
        sel.disabled = false;
        sel.value = "00";
    });
    tbody.querySelectorAll("tr").forEach(tr => tr.classList.remove("day-blocked"));

    const setTime = (prefix, bloque, tipo, value) => {
        const safe = String(value || "").substring(0, 5);
        const m = safe.match(/^(\d{2}):(\d{2})$/);
        const h = m ? m[1] : "00";
        const min = m ? m[2] : "00";
        const hSel = tbody.querySelector(`select[name="${prefix}_${bloque}_${tipo}_h"]`);
        const mSel = tbody.querySelector(`select[name="${prefix}_${bloque}_${tipo}_m"]`);
        if (hSel) hSel.value = h;
        if (mSel) mSel.value = min;
    };

    const fd = new FormData();
    fd.append("id_contrato", String(contratoId));
    fd.append("id_empleado", String(empleadoId));

    fetch("modelos/rescatar/horarios.php", {
            method: "POST",
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok || !Array.isArray(data.dias)) {
                throw new Error(data.msg || "No se pudieron cargar horarios.");
            }

            data.dias.forEach(d => {
                const prefix = normalizarDiaCodigo(d.dia_code || d.nombre || d.dia);
                if (!prefix) return;
                setTime(prefix, "man", "ini", d.man_ini);
                setTime(prefix, "man", "fin", d.man_fin);
                setTime(prefix, "tar", "ini", d.tar_ini);
                setTime(prefix, "tar", "fin", d.tar_fin);
            });

            const diasBloqueados = [];
            tbody.querySelectorAll("tr").forEach(tr => {
                const selects = Array.from(tr.querySelectorAll("select"));
                const todosEnCero = selects.length > 0 && selects.every(sel => sel.value === "00");
                const lockCheck = tr.querySelector(".day-lock-check");
                const dayName = tr.querySelector(".day-name")?.textContent?.trim() || "";

                if (!lockCheck) return;

                if (todosEnCero) {
                    if (!lockCheck.checked) {
                        lockCheck.checked = true;
                        lockCheck.dispatchEvent(new Event("change", {
                            bubbles: true
                        }));
                    }
                    if (dayName) diasBloqueados.push(dayName);
                    return;
                }

                if (lockCheck.checked) {
                    lockCheck.checked = false;
                    lockCheck.dispatchEvent(new Event("change", {
                        bubbles: true
                    }));
                }
            });

            updateHorarioDiasBloqueadosMsg(diasBloqueados);

            tbody.dispatchEvent(new Event("change", {
                bubbles: true
            }));
        })
        .catch(() => {
            Swal.fire("Error", "No se pudo cargar el horario del empleado seleccionado.", "error");
        });
}

function restaurarEmpleadoSeleccionadoPendiente() {
    const empleadoId = Number(sessionStorage.getItem("empleadoSeleccionadoId") || "0");
    if (empleadoId <= 0) return;

    const contratoId = Number(sessionStorage.getItem("empleadoSeleccionadoContratoId") || "0");
    const triggerBtn = document.querySelector(
        `.btn-table-icon[title="Cargar horario"][data-id-empleado="${empleadoId}"]`
    );

    sessionStorage.removeItem("empleadoSeleccionadoId");
    sessionStorage.removeItem("empleadoSeleccionadoContratoId");

    if (!triggerBtn) return;

    const contratoFinal = Number(triggerBtn.dataset?.idContrato || contratoId || 0);
    seleccionarEmpleado(null, triggerBtn, empleadoId, contratoFinal);
}

function verDetalleHorario(idEmpleado, idContrato) {
    const empleadoId = Number(idEmpleado) || 0;
    const contratoId = Number(idContrato) || 0;
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
        showCloseButton: true,
        html: `
      <div style="display:flex;align-items:center;gap:10px;justify-content:center;padding:10px 0;">
        <div class="spinner-border" role="status" aria-hidden="true"></div>
      </div>
      <div id="swalHorarioDetalle"></div>
    `,
        showDenyButton: true,
        confirmButtonText: 'Cerrar',
        denyButtonText: 'Descargar PDF',
        width: '80%',
        customClass: {
            popup: 'swal-seduc',
            confirmButton: 'btn-seduc btn-seduc-primary',
            denyButton: 'btn-seduc btn-seduc-ghost'
        },
        didOpen: () => {
            const fd = new FormData();
            fd.append('id_contrato', String(contratoId));
            fd.append('id_empleado', String(empleadoId));

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
        // Si presiona "Descargar PDF"
        if (result.isDenied) {
            descargarHorario(contratoId);
        }
    });
}

function descargarHorario(idContrato) {
    const contrato = Number(idContrato) || 0;
    if (contrato <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo identificar el contrato para descargar el PDF.',
            showCloseButton: true,
            customClass: {
                popup: 'swal-seduc',
                confirmButton: 'btn-seduc btn-seduc-primary'
            }
        });
        return;
    }
    window.location.assign('descarga/horario_empleado.php?id_contrato=' + encodeURIComponent(contrato));

}

function copiarDato(texto) {
    navigator.clipboard.writeText(texto).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Copiado',
            text: texto,
            timer: 900,
            showConfirmButton: false,
            showCloseButton: true,
            customClass: {
                popup: 'swal-seduc',
                confirmButton: 'btn-seduc btn-seduc-primary',
                cancelButton: 'btn-seduc btn-seduc-ghost'
            }
        });
    });
}

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function verObservacion(idEmpleado, nombre) {
    const empleadoId = Number(idEmpleado) || 0;
    if (empleadoId <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo identificar al empleado.',
            showCloseButton: true,
            customClass: {
                popup: 'swal-seduc',
                confirmButton: 'btn-seduc btn-seduc-primary'
            }
        });
        return;
    }

    Swal.fire({
        title: 'Observación',
        showCloseButton: true,
        html: `
            <div style="text-align: left;">
                <p style="color: #6b7280; font-size: 14px; margin-bottom: 12px;"><strong>${escapeHtml(nombre)}</strong></p>
                <div id="swalObservacionContenido" style="width: 100%; min-height: 150px; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; background: #f9fafb; font-family: inherit; font-size: 13px; line-height: 1.5; word-wrap: break-word; white-space: pre-wrap; display:flex; align-items:center; justify-content:center;">
                    <div class="spinner-border" role="status" aria-hidden="true"></div>
                </div>
            </div>
        `,
        confirmButtonText: 'Cerrar',
        customClass: {
            popup: 'swal-seduc',
            confirmButton: 'btn-seduc btn-seduc-primary'
        },
        didOpen: () => {
            const fd = new FormData();
            fd.append('id_empleado', String(empleadoId));

            fetch('rescatarobservacionFuncionario.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    const el = document.getElementById('swalObservacionContenido');
                    if (!el) return;

                    if (!data.ok) {
                        el.innerHTML = '<span style="color: #dc2626;">No se pudo cargar la observación.</span>';
                        return;
                    }

                    const observacion = String(data.observacion || '').trim();
                    el.style.display = 'block';
                    el.innerHTML = observacion ?
                        escapeHtml(observacion).replace(/\n/g, '<br>') :
                        '<span style="color: #9ca3af;">Sin observación</span>';
                })
                .catch(() => {
                    const el = document.getElementById('swalObservacionContenido');
                    if (!el) return;
                    el.innerHTML = '<span style="color: #dc2626;">Error al cargar la observación.</span>';
                });
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
            showCloseButton: true,
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

function toMinutesFromSelects(selH, selM) {
    return ((parseInt(selH.value, 10) || 0) * 60) + (parseInt(selM.value, 10) || 0);
}

function isZeroTimeSelects(selH, selM) {
    return (selH.value === "00" && selM.value === "00");
}

function showHorarioWarning(message) {
    if (typeof Swal !== "undefined" && Swal.fire) {
        Swal.fire({
            icon: "warning",
            title: "Horario inválido",
            text: message,
            showCloseButton: true,
            customClass: {
                popup: 'swal-seduc',
                confirmButton: 'btn-seduc btn-seduc-primary'
            }
        });
        return;
    }

    alert(message);
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

function getRowTimeValue(tr, prefix, bloque, tipo) {
    const selH = tr.querySelector(`select[name="${prefix}_${bloque}_${tipo}_h"]`);
    const selM = tr.querySelector(`select[name="${prefix}_${bloque}_${tipo}_m"]`);
    return {
        hh: selH ? selH.value : "00",
        mm: selM ? selM.value : "00"
    };
}

function isRepeatScheduleDownEnabled() {
    const toggle = document.getElementById("repeatScheduleDownToggle");
    return !!toggle?.checked;
}

function setRowTimeValue(tr, prefix, bloque, tipo, hh, mm) {
    const selH = tr.querySelector(`select[name="${prefix}_${bloque}_${tipo}_h"]`);
    const selM = tr.querySelector(`select[name="${prefix}_${bloque}_${tipo}_m"]`);
    if (selH) selH.value = String(hh || "00").padStart(2, "0");
    if (selM) selM.value = String(mm || "00").padStart(2, "0");
}

function copiarHorarioHaciaAbajo(prefixOrigen) {
    if (window.__copiandoHorarioHaciaAbajo) return;

    const filas = Array.from(document.querySelectorAll("#tbodyHorario tr"));
    const idxOrigen = filas.findIndex((tr) => tr.dataset.dayPrefix === prefixOrigen);
    if (idxOrigen < 0) return;

    const filaOrigen = filas[idxOrigen];
    const lockOrigen = filaOrigen.querySelector(".day-lock-check");
    const origenBloqueado = !!lockOrigen?.checked;

    const horarioOrigen = {
        manIni: getRowTimeValue(filaOrigen, prefixOrigen, "man", "ini"),
        manFin: getRowTimeValue(filaOrigen, prefixOrigen, "man", "fin"),
        tarIni: getRowTimeValue(filaOrigen, prefixOrigen, "tar", "ini"),
        tarFin: getRowTimeValue(filaOrigen, prefixOrigen, "tar", "fin")
    };

    window.__copiandoHorarioHaciaAbajo = true;
    try {
        for (let i = idxOrigen + 1; i < filas.length; i++) {
            const filaDestino = filas[i];
            const prefixDestino = filaDestino.dataset.dayPrefix || "";
            if (!prefixDestino) continue;

            const lockDestino = filaDestino.querySelector(".day-lock-check");
            if (lockDestino?.checked !== origenBloqueado) {
                lockDestino.checked = origenBloqueado;
                lockDestino.dispatchEvent(new Event("change", { bubbles: true }));
            }

            setRowTimeValue(filaDestino, prefixDestino, "man", "ini", horarioOrigen.manIni.hh, horarioOrigen.manIni.mm);
            setRowTimeValue(filaDestino, prefixDestino, "man", "fin", horarioOrigen.manFin.hh, horarioOrigen.manFin.mm);
            setRowTimeValue(filaDestino, prefixDestino, "tar", "ini", horarioOrigen.tarIni.hh, horarioOrigen.tarIni.mm);
            setRowTimeValue(filaDestino, prefixDestino, "tar", "fin", horarioOrigen.tarFin.hh, horarioOrigen.tarFin.mm);
        }
    } finally {
        window.__copiandoHorarioHaciaAbajo = false;
    }

    document.getElementById("tbodyHorario")?.dispatchEvent(new Event("change", { bubbles: true }));
}

function buildRow(dia) { // arma una fila completa del día (mañana ini/fin, tarde ini/fin).
    const tr = document.createElement("tr");
    tr.dataset.dayPrefix = dia.prefix;

    const th = document.createElement("th");
    th.innerHTML = `
      <div class="day-head">
        <label class="day-lock">
          <input type="checkbox" class="day-lock-check" aria-label="Bloquear ${dia.label}">
          <span class="lock-icon" aria-hidden="true"><i class="bi bi-lock-fill"></i></span>
          <span class="day-name">${dia.label}</span>
        </label>
      </div>
    `;
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

    function bindBloqueRules(bloque, bloqueLabel) {
        const iniH = tr.querySelector(`select[name="${dia.prefix}_${bloque}_ini_h"]`);
        const iniM = tr.querySelector(`select[name="${dia.prefix}_${bloque}_ini_m"]`);
        const finH = tr.querySelector(`select[name="${dia.prefix}_${bloque}_fin_h"]`);
        const finM = tr.querySelector(`select[name="${dia.prefix}_${bloque}_fin_m"]`);
        if (!iniH || !iniM || !finH || !finM) return;

        function refreshTerminoOptions() {
            const iniZero = isZeroTimeSelects(iniH, iniM);
            const iniHour = parseInt(iniH.value, 10) || 0;
            const iniMin = parseInt(iniM.value, 10) || 0;
            const finHour = parseInt(finH.value, 10) || 0;

            Array.from(finH.options).forEach(opt => {
                const h = parseInt(opt.value, 10) || 0;
                opt.disabled = !iniZero && h < iniHour;
            });

            Array.from(finM.options).forEach(opt => {
                const m = parseInt(opt.value, 10) || 0;
                opt.disabled = !iniZero && (finHour < iniHour || (finHour === iniHour && m < iniMin));
            });
        }

        function normalizeTerminoAndValidate(source) {
            const iniZero = isZeroTimeSelects(iniH, iniM);
            const finZero = isZeroTimeSelects(finH, finM);

            if (iniZero && !finZero) {
                finH.value = "00";
                finM.value = "00";
                refreshTerminoOptions();
                if (source === "termino") {
                    showHorarioWarning(`Primero debes indicar la hora de inicio de la jornada ${bloqueLabel}.`);
                }
                return;
            }

            const iniMinTotal = toMinutesFromSelects(iniH, iniM);
            const finMinTotal = toMinutesFromSelects(finH, finM);
            if (!iniZero && !finZero && finMinTotal < iniMinTotal) {
                finH.value = iniH.value;
                finM.value = iniM.value;
                refreshTerminoOptions();
                return;
            }

            refreshTerminoOptions();
        }

        [iniH, iniM].forEach(sel => {
            sel.addEventListener("change", function() {
                normalizeTerminoAndValidate("inicio");
            });
        });

        [finH, finM].forEach(sel => {
            sel.addEventListener("change", function() {
                normalizeTerminoAndValidate("termino");
            });
        });

        refreshTerminoOptions();
    }

    bindBloqueRules("man", "mañana");
    bindBloqueRules("tar", "tarde");

    function bindCruceMananaTardeRules() {
        const manFinH = tr.querySelector(`select[name="${dia.prefix}_man_fin_h"]`);
        const manFinM = tr.querySelector(`select[name="${dia.prefix}_man_fin_m"]`);
        const tarIniH = tr.querySelector(`select[name="${dia.prefix}_tar_ini_h"]`);
        const tarIniM = tr.querySelector(`select[name="${dia.prefix}_tar_ini_m"]`);
        if (!manFinH || !manFinM || !tarIniH || !tarIniM) return;

        function refreshTardeInicioOptions() {
            const manFinZero = isZeroTimeSelects(manFinH, manFinM);
            const manFinHour = parseInt(manFinH.value, 10) || 0;
            const manFinMin = parseInt(manFinM.value, 10) || 0;
            const tarHour = parseInt(tarIniH.value, 10) || 0;

            Array.from(tarIniH.options).forEach(opt => {
                const h = parseInt(opt.value, 10) || 0;
                // Permitir 00:00 como bloque "sin tarde". Si se usa tarde, debe ser >= mañana término.
                opt.disabled = !manFinZero && h !== 0 && h < manFinHour;
            });

            Array.from(tarIniM.options).forEach(opt => {
                const m = parseInt(opt.value, 10) || 0;

                // Si hora es 00, solo permitir 00:00 como valor vacío
                if (tarHour === 0) {
                    opt.disabled = (m !== 0);
                    return;
                }

                opt.disabled = !manFinZero && (tarHour < manFinHour || (tarHour === manFinHour && m < manFinMin));
            });
        }

        function normalizeTardeInicio() {
            const manFinZero = isZeroTimeSelects(manFinH, manFinM);
            if (manFinZero) {
                refreshTardeInicioOptions();
                return;
            }

            const tarIniZero = isZeroTimeSelects(tarIniH, tarIniM);
            if (tarIniZero) {
                refreshTardeInicioOptions();
                return;
            }

            const manFinTotal = toMinutesFromSelects(manFinH, manFinM);
            const tarIniTotal = toMinutesFromSelects(tarIniH, tarIniM);
            if (tarIniTotal < manFinTotal) {
                tarIniH.value = manFinH.value;
                tarIniM.value = manFinM.value;
                tarIniM.dispatchEvent(new Event("change", { bubbles: true }));
            }

            refreshTardeInicioOptions();
        }

        [manFinH, manFinM, tarIniH, tarIniM].forEach(sel => {
            sel.addEventListener("change", normalizeTardeInicio);
        });

        refreshTardeInicioOptions();
    }

    bindCruceMananaTardeRules();

    const lockCheck = th.querySelector(".day-lock-check");
    const lockLabel = th.querySelector(".day-lock");
    const lockIcon = th.querySelector(".lock-icon i");

    const refreshLockUi = (isBlocked) => {
        if (lockLabel) lockLabel.classList.toggle("active", isBlocked);
        if (lockIcon) {
            lockIcon.classList.toggle("bi-lock-fill", isBlocked);
            lockIcon.classList.toggle("bi-unlock-fill", !isBlocked);
        }
    };

    if (lockCheck) {
        refreshLockUi(false);
        lockCheck.addEventListener("change", function() {
            const allSelects = tr.querySelectorAll("select");
            if (this.checked) {
                allSelects.forEach(s => {
                    s.value = "00";
                    s.disabled = true;
                });
                tr.classList.add("day-blocked");
            } else {
                allSelects.forEach(s => s.disabled = false);
                tr.classList.remove("day-blocked");
            }
            refreshLockUi(this.checked);
            // dispara recálculo general
            tr.dispatchEvent(new Event("change", { bubbles: true }));
            if (isRepeatScheduleDownEnabled()) {
                copiarHorarioHaciaAbajo(dia.prefix);
            }
        });
    }

    tr.querySelectorAll("select").forEach((sel) => {
        sel.addEventListener("change", function() {
            if (window.__copiandoHorarioHaciaAbajo) return;
            if (!isRepeatScheduleDownEnabled()) return;
            copiarHorarioHaciaAbajo(dia.prefix);
        });
    });

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
        document.querySelectorAll(".day-lock-check").forEach(c => c.checked = false);
        const repeatToggle = document.getElementById("repeatScheduleDownToggle");
        if (repeatToggle) repeatToggle.checked = false;
        document.querySelectorAll("#tbodyHorario tr").forEach(tr => {
            tr.classList.remove("day-blocked");
            tr.querySelectorAll("select").forEach(s => s.disabled = false);
        });

        const sumJornadaPed = document.getElementById("sumJornadaPed");
        const sumJornadaCro = document.getElementById("sumJornadaCro");
        const sumLectivasPed = document.getElementById("sumLectivasPed");
        const sumLectivasCro = document.getElementById("sumLectivasCro");
        const sumNoLectivasPed = document.getElementById("sumNoLectivasPed");
        const sumNoLectivasCro = document.getElementById("sumNoLectivasCro");
        const sumColacionMin = document.getElementById("sumColacionMin");
        const sumColacionSelect = document.getElementById("sumColacionSelect");

        if (sumJornadaPed) sumJornadaPed.textContent = "0";
        if (sumJornadaCro) sumJornadaCro.textContent = "00:00";
        if (sumLectivasPed) sumLectivasPed.value = "0";
        if (sumLectivasCro) sumLectivasCro.value = "00:00";
        if (sumNoLectivasPed) sumNoLectivasPed.value = "0";
        if (sumNoLectivasCro) sumNoLectivasCro.value = "00:00";
        if (sumColacionMin) sumColacionMin.textContent = "0";
        if (sumColacionSelect) sumColacionSelect.value = "";
        if (typeof window.updateHorasLectivasUI === "function") {
            window.updateHorasLectivasUI();
        }
        updateHorarioDiasBloqueadosMsg([]);

        window.empleadoSeleccionadoPrefill = null;
        const infoEl = document.getElementById("empleadoSeleccionadoInfo");
        if (infoEl) {
            infoEl.textContent = "Sin empleado seleccionado";
            infoEl.title = "Sin empleado seleccionado";
        }
        setAccionEmpleadoModo(false);
        if (sumColacionSelect) {
            sumColacionSelect.dispatchEvent(new Event("change", {
                bubbles: true
            }));
        }
        document.getElementById("tbodyHorario").dispatchEvent(new Event("change", {
            bubbles: true
        }));
    });


}

init();
</script>






<!-- ***************************************************************** -->
<!-- se activan con el archivo funciones.js -->
<!-- ***************************************************************** -->

<!--calculo de horas Cronologicas-->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1) Re-cálculo jornada + resumen
    bindRecalculoHorario({
        tbodySelector: "#tbodyHorario",
        dayPrefixes: ["lun", "mar", "mie", "jue", "vie"]
    });

    // 2) Colación fija seleccionada desde BD
    bindColacionFija();

    restaurarEmpleadoSeleccionadoPendiente();
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

    function renumerarFilas() {
        const rows = Array.from(tbody.querySelectorAll('tr'));
        let correlativo = 1;

        rows.forEach(tr => {
            const td = tr.children[0];
            if (!td) return;

            if (tr.style.display === 'none') {
                td.textContent = '';
                td.setAttribute('data-value', '');
                return;
            }

            const numero = String(correlativo++);
            td.textContent = numero;
            td.setAttribute('data-value', numero);
        });
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
        renumerarFilas();
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
        renumerarFilas();
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

