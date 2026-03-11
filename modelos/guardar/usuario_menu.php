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

$payload = json_decode(file_get_contents("php://input"), true);
if (!is_array($payload)) {
    echo json_encode(["ok" => false, "msg" => "Payload inválido."]);
    exit;
}

$idUsuario = (int)($payload["id_usuario"] ?? 0);
$menus = $payload["menus"] ?? [];

if ($idUsuario <= 0) {
    echo json_encode(["ok" => false, "msg" => "Usuario inválido."]);
    exit;
}

if (!is_array($menus)) {
    echo json_encode(["ok" => false, "msg" => "Lista de menús inválida."]);
    exit;
}

$menusNormalizados = [];
foreach ($menus as $codigo) {
    $codigo = trim((string)$codigo);
    if ($codigo !== "") {
        $menusNormalizados[] = $codigo;
    }
}

$funciones->guardarPermisosMenuUsuario($idUsuario, $menusNormalizados);

echo json_encode([
    "ok" => true,
    "msg" => "Permisos actualizados correctamente.",
], JSON_UNESCAPED_UNICODE);
