<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION["id_usuario"])) {
    echo json_encode(["ok" => false, "msg" => "Sesión expirada."], JSON_UNESCAPED_UNICODE);
    exit;
}

if ((int)($_SESSION["id_rol"] ?? 0) !== 1) {
    echo json_encode(["ok" => false, "msg" => "No autorizado."], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . "/../../class/conexion.php";

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");

$email = trim((string)($_GET["email"] ?? ""));
if ($email === "") {
    echo json_encode([
        "ok" => true,
        "exists" => false,
        "nombre_completo" => ""
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "ok" => false,
        "msg" => "El email no es válido."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$emailEsc = $db->escape_string($email);
$res = $db->consulta("
    SELECT
        id_usuario,
        nombre,
        apellido_paterno,
        apellido_materno
    FROM usuarios
    WHERE email = '{$emailEsc}'
    LIMIT 1
");

if ($db->num_rows($res) === 0) {
    echo json_encode([
        "ok" => true,
        "exists" => false,
        "nombre_completo" => ""
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$row = $db->fetch_assoc($res);
$nombreCompleto = trim(implode(" ", array_filter([
    trim((string)($row["nombre"] ?? "")),
    trim((string)($row["apellido_paterno"] ?? "")),
    trim((string)($row["apellido_materno"] ?? ""))
])));

echo json_encode([
    "ok" => true,
    "exists" => true,
    "id_usuario" => (int)($row["id_usuario"] ?? 0),
    "nombre_completo" => $nombreCompleto !== "" ? $nombreCompleto : "Usuario registrado"
], JSON_UNESCAPED_UNICODE);
