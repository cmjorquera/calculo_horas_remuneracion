<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION["id_usuario"])) {
  echo json_encode(["ok" => false, "msg" => "Sesión expirada."]);
  exit;
}

require_once __DIR__ . "/../../class/conexion.php";

// Con tu clase MySQL (mysqli por dentro)
$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");

function jerr($msg) {
  echo json_encode(["ok" => false, "msg" => $msg]);
  exit;
}
function esc($db, $v) { return $db->escape_string($v); }
function normalize_run($run) {
  $run = strtoupper(trim((string)$run));
  return preg_replace('/[^0-9K]/', '', $run);
}

// =====================
// 1) POST
// =====================
$nombres    = trim($_POST["nombres"] ?? "");
$ap_paterno = trim($_POST["ap_paterno"] ?? "");
$ap_materno = trim($_POST["ap_materno"] ?? "");
$run        = trim($_POST["run"] ?? "");
$id_empleado_post = (int)($_POST["id_empleado"] ?? 0);
$id_contrato_post = (int)($_POST["id_contrato"] ?? 0);
$email      = trim($_POST["email"] ?? "");
$telefono   = trim($_POST["telefono"] ?? "");
$genero     = trim($_POST["genero"] ?? "");
$horarioJson = $_POST["horario"] ?? "";
$id_colacion = trim($_POST["id_colacion"] ?? "");
$horas_semanales_cron = (int)($_POST["horas_semanales_cron"] ?? 0);
$horas_lectivas = (int)($_POST["horas_lectivas"] ?? 0);
$horas_no_lectivas = (int)($_POST["horas_no_lectivas"] ?? 0);
$min_colacion_diaria  = (int)($_POST["min_colacion_diaria"] ?? 0);

// =====================
// 2) VALIDACIONES (vacíos + formato)
// =====================
if ($nombres === "")    jerr("Nombres es obligatorio.");
if ($ap_paterno === "") jerr("Apellido paterno es obligatorio.");
if ($ap_materno === "") jerr("Apellido materno es obligatorio.");
if ($run === "")        jerr("RUN es obligatorio.");
if ($genero !== "" && !in_array($genero, ["1","2"], true)) jerr("Género inválido.");

if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  jerr("Email inválido.");
}

if ($horarioJson === "") jerr("No se recibió horario.");
$horario = json_decode($horarioJson, true);
if (!is_array($horario)) jerr("Horario inválido.");

// Validar coherencia inicio/fin por bloque (puede venir todo vacío)

foreach ($horario as $d) {
  $manIni = trim($d["manana"]["inicio"] ?? "");
  $manFin = trim($d["manana"]["termino"] ?? "");
  $tarIni = trim($d["tarde"]["inicio"] ?? "");
  $tarFin = trim($d["tarde"]["termino"] ?? "");

  if (($manIni !== "" && $manFin === "") || ($manIni === "" && $manFin !== "")) {
    jerr("Bloque mañana incompleto: selecciona inicio y término.");
  }
  if (($tarIni !== "" && $tarFin === "") || ($tarIni === "" && $tarFin !== "")) {
    jerr("Bloque tarde incompleto: selecciona inicio y término.");
  }
}

// =====================
// 3) DATOS EXTRA
// =====================
$id_colegio = (int)($_SESSION["id_colegio"] ?? 0);
if ($id_colegio <= 0) jerr("id_colegio no encontrado.");

$codigo = "EMP" . date("ymdHis") . rand(100, 999);

// =====================
// 4) TRANSACCIÓN
// =====================
$db->consulta("START TRANSACTION");

// 4.1) Resolver empleado destino (nuevo o existente)
$id_empleado = 0;
if ($id_empleado_post > 0) {
  $sqlEmpExiste = "
    SELECT id_empleado
    FROM empleados
    WHERE id_empleado = $id_empleado_post
      AND id_colegio = $id_colegio
    LIMIT 1
  ";
  $resEmpExiste = $db->consulta($sqlEmpExiste);
  $rowEmpExiste = $db->fetch_assoc($resEmpExiste);
  if (!$rowEmpExiste) {
    $db->consulta("ROLLBACK");
    jerr("El empleado seleccionado no existe en este colegio.");
  }
  $id_empleado = (int)$rowEmpExiste["id_empleado"];
}
$modoModificar = $id_empleado > 0;

// 4.2) Verificar RUN duplicado en el colegio (excluye el mismo empleado cuando aplica)
$runNorm = normalize_run($run);
$sqlCheck = "
  SELECT id_empleado
  FROM empleados
  WHERE id_colegio = $id_colegio
    AND REPLACE(REPLACE(UPPER(run), '.', ''), '-', '') = '".esc($db,$runNorm)."'
    ".($id_empleado > 0 ? "AND id_empleado <> $id_empleado" : "")."
  LIMIT 1
";
$resCheck = $db->consulta($sqlCheck);
if ($db->fetch_assoc($resCheck)) {
  $db->consulta("ROLLBACK");
  jerr("Ya existe un empleado con ese RUN en este colegio.");
}

// 4.3) Insert o update EMPLEADO
if ($id_empleado > 0) {
  $sqlEmpUpdate = "
    UPDATE empleados SET
      run = '".esc($db,$run)."',
      nombres = '".esc($db,$nombres)."',
      apellido_paterno = '".esc($db,$ap_paterno)."',
      apellido_materno = '".esc($db,$ap_materno)."',
      email = CASE WHEN '".esc($db,$email)."' = '' THEN email ELSE '".esc($db,$email)."' END,
      telefono = CASE WHEN '".esc($db,$telefono)."' = '' THEN telefono ELSE '".esc($db,$telefono)."' END,
      genero = CASE WHEN '".esc($db,$genero)."' = '' THEN genero ELSE '".esc($db,$genero)."' END,
      updated_at = NOW()
    WHERE id_empleado = $id_empleado
      AND id_colegio = $id_colegio
    LIMIT 1
  ";
  if (!$db->consulta($sqlEmpUpdate)) {
    $db->consulta("ROLLBACK");
    jerr("Error al actualizar datos del empleado.");
  }
} else {
  $sqlEmp = "
    INSERT INTO empleados
    (codigo, run, id_colegio, nombres, apellido_paterno, apellido_materno, email, telefono, activo, created_at, updated_at, genero)
    VALUES
    (
      '".esc($db,$codigo)."',
      '".esc($db,$run)."',
      $id_colegio,
      '".esc($db,$nombres)."',
      '".esc($db,$ap_paterno)."',
      '".esc($db,$ap_materno)."',
      '".esc($db,$email)."',
      '".esc($db,$telefono)."',
      1,
      NOW(),
      NOW(),
      '".esc($db,$genero)."'
    )
  ";
  if (!$db->consulta($sqlEmp)) {
    $db->consulta("ROLLBACK");
    jerr("Error al insertar empleado.");
  }

  $id_empleado = (int)$db->insert_id();
  if ($id_empleado <= 0) {
    $db->consulta("ROLLBACK");
    jerr("No se pudo obtener id_empleado.");
  }
}

// 4.4) Resolver/guardar CONTRATO (update si modifica, insert si agrega)
$id_contrato = 0;
if ($modoModificar) {
  if ($id_contrato_post > 0) {
    $sqlConExiste = "
      SELECT id_contrato
      FROM contratos_empleado
      WHERE id_contrato = $id_contrato_post
        AND id_empleado = $id_empleado
      LIMIT 1
    ";
    $resConExiste = $db->consulta($sqlConExiste);
    $rowConExiste = $db->fetch_assoc($resConExiste);
    if ($rowConExiste) {
      $id_contrato = (int)$rowConExiste["id_contrato"];
    }
  }

  if ($id_contrato <= 0) {
    $sqlConVigente = "
      SELECT id_contrato
      FROM contratos_empleado
      WHERE id_empleado = $id_empleado
        AND fecha_fin IS NULL
      ORDER BY id_contrato DESC
      LIMIT 1
    ";
    $resConVigente = $db->consulta($sqlConVigente);
    $rowConVigente = $db->fetch_assoc($resConVigente);
    if ($rowConVigente) {
      $id_contrato = (int)$rowConVigente["id_contrato"];
    }
  }
}

// Valores enviados desde UI
$fecha_inicio = date("Y-m-d");
$fecha_fin = null;                  // NULL
$horas_lectivas = max(0, $horas_lectivas);
$horas_no_lectivas = max(0, $horas_no_lectivas);
$horas_semanales_cron = max(0, $horas_semanales_cron);
$min_colacion_diaria = max(0, $min_colacion_diaria);

$observacion = trim($_POST["observacion"] ?? "");

if ($id_contrato > 0) {
  $sqlConUpdate = "
    UPDATE contratos_empleado SET
      horas_semanales_cron = $horas_semanales_cron,
      horas_lectivas = $horas_lectivas,
      horas_no_lectivas = $horas_no_lectivas,
      min_colacion_diaria = $min_colacion_diaria,
      observacion = '".esc($db,$observacion)."'
    WHERE id_contrato = $id_contrato
      AND id_empleado = $id_empleado
    LIMIT 1
  ";
  if (!$db->consulta($sqlConUpdate)) {
    $db->consulta("ROLLBACK");
    jerr("Error al actualizar contrato del empleado.");
  }
} else {
  $sqlCon = "
    INSERT INTO contratos_empleado
  (id_empleado, fecha_inicio, fecha_fin, horas_semanales_cron, horas_lectivas, horas_no_lectivas, min_colacion_diaria, observacion, created_at)
  VALUES
  (
    $id_empleado,
    '".esc($db,$fecha_inicio)."',
    NULL,
    $horas_semanales_cron,
    $horas_lectivas,
    $horas_no_lectivas,
    $min_colacion_diaria,
    '".esc($db,$observacion)."',
    NOW()
  )
  ";
  if (!$db->consulta($sqlCon)) {
    $db->consulta("ROLLBACK");
    jerr("Error al insertar contrato del empleado.");
  }
  $id_contrato = (int)$db->insert_id();
  if ($id_contrato <= 0) {
    $db->consulta("ROLLBACK");
    jerr("No se pudo obtener id_contrato.");
  }
}

// 4.5) Insert HORARIOS
// Requisito: horarios_semanales tiene columna id_empleado
if ($modoModificar && $id_empleado > 0) {
  $sqlDelHor = "
    DELETE FROM horarios_semanales
    WHERE id_empleado = $id_empleado
  ";
  if (!$db->consulta($sqlDelHor)) {
    $db->consulta("ROLLBACK");
    jerr("Error al limpiar horarios anteriores del empleado.");
  }
}

foreach ($horario as $prefix => $d) {

  $manIni = trim($d["manana"]["inicio"] ?? "");
  $manFin = trim($d["manana"]["termino"] ?? "");
  $tarIni = trim($d["tarde"]["inicio"] ?? "");
  $tarFin = trim($d["tarde"]["termino"] ?? "");

  // si el día está completamente vacío, lo saltamos
  if ($manIni === "" && $manFin === "" && $tarIni === "" && $tarFin === "") continue;

  $sqlHor = "
    INSERT INTO horarios_semanales
    (id_contrato, id_empleado, dia, man_ini, man_fin, tar_ini, tar_fin, activo, created_at)
    VALUES
    (
      $id_contrato,
      $id_empleado,
      '".esc($db,$prefix)."',
      ".($manIni===""?"NULL":"'".esc($db,$manIni)."'").",
      ".($manFin===""?"NULL":"'".esc($db,$manFin)."'").",
      ".($tarIni===""?"NULL":"'".esc($db,$tarIni)."'").",
      ".($tarFin===""?"NULL":"'".esc($db,$tarFin)."'").",
      1,
      NOW()
    )
  ";

  if (!$db->consulta($sqlHor)) {
    $db->consulta("ROLLBACK");
    jerr("Error al guardar horario (día: $prefix).");
  }
}

$db->consulta("COMMIT");

echo json_encode([
  "ok" => true,
  "msg" => "Empleado, contrato y horario guardados correctamente.",
  "id_empleado" => $id_empleado,
  "id_contrato" => $id_contrato
]);
exit;
