<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION["id_usuario"])) {
    echo json_encode(["ok" => false, "msg" => "Sesión expirada."]);
    exit;
}

if ((int)($_SESSION["id_rol"] ?? 0) !== 1) {
    echo json_encode(["ok" => false, "msg" => "No autorizado."]);
    exit;
}

require_once __DIR__ . "/../../class/conexion.php";
require_once __DIR__ . "/../../class/funciones.php";

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");
$funciones = new Funciones($db);

$idUsuario = (int)($_GET["id_usuario"] ?? 0);
if ($idUsuario <= 0) {
    echo json_encode(["ok" => false, "msg" => "Usuario inválido."]);
    exit;
}

echo json_encode([
    "ok" => true,
    "permisos" => $funciones->obtenerPermisosMenuUsuario($idUsuario),
], JSON_UNESCAPED_UNICODE);
