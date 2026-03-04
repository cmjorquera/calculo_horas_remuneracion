<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION["id_usuario"])) {
  echo json_encode(["ok" => false, "msg" => "Sesión expirada."]);
  exit;
}

require_once __DIR__ . "/../../class/conexion.php";

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");

function jresp($arr) {
  echo json_encode($arr);
  exit;
}

function esc($db, $v) {
  return $db->escape_string($v);
}

function normalize_run($run) {
  $run = strtoupper(trim((string)$run));
  return preg_replace('/[^0-9K]/', '', $run);
}

$id_colegio = (int)($_SESSION["id_colegio"] ?? 0);
$es_super_admin = !empty($_SESSION["is_super_admin"]);
if (!$es_super_admin && $id_colegio <= 0) {
  jresp(["ok" => false, "msg" => "id_colegio no encontrado."]);
}

$runRaw = trim($_POST["run"] ?? "");
$runNorm = normalize_run($runRaw);

if ($runNorm === "") {
  jresp(["ok" => true, "exists" => false]);
}

$sql = "
  SELECT run, nombres, apellido_paterno, apellido_materno
  FROM empleados
  WHERE ".($es_super_admin ? "1=1" : "id_colegio = $id_colegio")."
    AND REPLACE(REPLACE(UPPER(run), '.', ''), '-', '') = '".esc($db, $runNorm)."'
  LIMIT 1
";

$res = $db->consulta($sql);
$row = $db->fetch_assoc($res);

if (!$row) {
  jresp(["ok" => true, "exists" => false]);
}

$nombreCompleto = trim(
  ($row["nombres"] ?? "") . " " .
  ($row["apellido_paterno"] ?? "") . " " .
  ($row["apellido_materno"] ?? "")
);

jresp([
  "ok" => true,
  "exists" => true,
  "run" => (string)($row["run"] ?? $runRaw),
  "nombre_completo" => $nombreCompleto
]);
