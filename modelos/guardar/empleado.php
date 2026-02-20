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

// =====================
// 1) POST
// =====================
$nombres    = trim($_POST["nombres"] ?? "");
$ap_paterno = trim($_POST["ap_paterno"] ?? "");
$ap_materno = trim($_POST["ap_materno"] ?? "");
$run        = trim($_POST["run"] ?? "");
$email      = trim($_POST["email"] ?? "");
$telefono   = trim($_POST["telefono"] ?? "");
$genero     = trim($_POST["genero"] ?? "");
$horarioJson = $_POST["horario"] ?? "";

// =====================
// 2) VALIDACIONES (vacíos + formato)
// =====================
if ($nombres === "")    jerr("Nombres es obligatorio.");
if ($ap_paterno === "") jerr("Apellido paterno es obligatorio.");
if ($ap_materno === "") jerr("Apellido materno es obligatorio.");
if ($run === "")        jerr("RUN es obligatorio.");
if ($email === "")      jerr("Email es obligatorio.");
if ($telefono === "")   jerr("Teléfono es obligatorio.");
if ($genero === "" || !in_array($genero, ["1","2"], true)) jerr("Género inválido.");

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  jerr("Email inválido.");
}

if ($horarioJson === "") jerr("No se recibió horario.");
$horario = json_decode($horarioJson, true);
if (!is_array($horario)) jerr("Horario inválido.");

// Validar: debe existir al menos 1 hora marcada y coherencia inicio/fin
$tieneAlgo = false;

foreach ($horario as $d) {
  $manIni = trim($d["manana"]["inicio"] ?? "");
  $manFin = trim($d["manana"]["termino"] ?? "");
  $tarIni = trim($d["tarde"]["inicio"] ?? "");
  $tarFin = trim($d["tarde"]["termino"] ?? "");

  if ($manIni !== "" || $manFin !== "" || $tarIni !== "" || $tarFin !== "") {
    $tieneAlgo = true;
  }

  if (($manIni !== "" && $manFin === "") || ($manIni === "" && $manFin !== "")) {
    jerr("Bloque mañana incompleto: selecciona inicio y término.");
  }
  if (($tarIni !== "" && $tarFin === "") || ($tarIni === "" && $tarFin !== "")) {
    jerr("Bloque tarde incompleto: selecciona inicio y término.");
  }
}

if (!$tieneAlgo) jerr("Debe asignar un horario primero.");

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

// 4.1) Verificar RUN duplicado en el colegio
$sqlCheck = "
  SELECT id_empleado
  FROM empleados
  WHERE run = '".esc($db,$run)."'
    AND id_colegio = $id_colegio
  LIMIT 1
";
$resCheck = $db->consulta($sqlCheck);
if ($db->fetch_assoc($resCheck)) {
  $db->consulta("ROLLBACK");
  jerr("Ya existe un empleado con ese RUN en este colegio.");
}

// 4.2) Insert EMPLEADO
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

// 4.3) Insert CONTRATO (id_contrato = id_empleado por ahora)
$id_contrato = $id_empleado;

// Valores por defecto (ajústalos después desde UI)
$fecha_inicio = date("Y-m-d");
$fecha_fin = null;                  // NULL
$horas_semanales_cron = trim($_POST["horas_semanales_cron"] ?? "00:00");
$min_colacion_diaria  = (int)($_POST["min_colacion_diaria"] ?? 0);

$observacion = trim($_POST["observacion"] ?? "");

// OJO: si id_contrato es AUTO_INCREMENT en tu tabla, esto fallará.
// Según tu pedido, lo insertamos explícito.
$sqlCon = "
  INSERT INTO contratos_empleado
(id_empleado, fecha_inicio, fecha_fin, horas_semanales_cron, min_colacion_diaria, observacion, created_at)
VALUES
(
  $id_empleado,
  '".esc($db,$fecha_inicio)."',
  NULL,
  $horas_semanales_cron,
  $min_colacion_diaria,
  '".esc($db,$observacion)."',
  NOW()
)
";
if (!$db->consulta($sqlCon)) {
  $db->consulta("ROLLBACK");
  jerr("Error al insertar contrato del empleado.");
}

// 4.4) Insert HORARIOS
// Requisito: horarios_semanales tiene columna id_empleado
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
