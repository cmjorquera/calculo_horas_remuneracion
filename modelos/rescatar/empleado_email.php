<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION["id_usuario"])) {
  echo json_encode(["ok" => false, "msg" => "Sesión expirada."]);
  exit;
}

require_once __DIR__ . "/../../class/conexion.php";
require_once __DIR__ . "/../../class/funciones.php";

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");
$funciones = new Funciones($db);

function jresp($arr) {
  echo json_encode($arr);
  exit;
}

function esc($db, $v) {
  return $db->escape_string($v);
}

$idUsuarioSesion = (int)($_SESSION["id_usuario"] ?? 0);
$idColegioSesion = (int)($_SESSION["id_colegio"] ?? 0);
$emailRaw = trim((string)($_GET["email"] ?? ""));
$emailNorm = mb_strtolower($emailRaw, "UTF-8");

if ($emailNorm === "") {
  jresp(["ok" => true, "exists" => false]);
}

if (!filter_var($emailNorm, FILTER_VALIDATE_EMAIL)) {
  jresp(["ok" => true, "exists" => false]);
}

$esSuperAdmin = $funciones->usuarioTieneRol($idUsuarioSesion, 1) || $idUsuarioSesion === 2 || $idUsuarioSesion === 5;

$sql = "
  SELECT email, nombres, apellido_paterno, apellido_materno
  FROM empleados
  WHERE " . ($esSuperAdmin ? "1=1" : "id_colegio = {$idColegioSesion}") . "
    AND LOWER(email) = '" . esc($db, $emailNorm) . "'
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
  "email" => (string)($row["email"] ?? $emailRaw),
  "nombre_completo" => $nombreCompleto
]);
