<?php
session_start();
require_once __DIR__ . "/../../class/conexion.php";

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");

function redirigirError($token, $mensaje)
{
    $destino = "../../incorporacion.php";
    if ($token !== '') {
        $destino .= "?token=" . rawurlencode($token) . "&m=" . rawurlencode($mensaje);
    } else {
        $destino .= "?m=" . rawurlencode($mensaje);
    }
    header("Location: " . $destino);
    exit;
}

$token = trim((string)($_POST["token"] ?? ""));
$clave = trim((string)($_POST["clave"] ?? ""));
$claveConfirmacion = trim((string)($_POST["clave_confirmacion"] ?? ""));

if ($token === '') {
    redirigirError('', 'token_invalido');
}
if ($clave === '' || $claveConfirmacion === '') {
    redirigirError($token, 'campos_vacios');
}
if (mb_strlen($clave, 'UTF-8') < 8) {
    redirigirError($token, 'clave_corta');
}
if ($clave !== $claveConfirmacion) {
    redirigirError($token, 'clave_distinta');
}

$tokenEsc = $db->escape_string($token);
$res = $db->consulta("
    SELECT id_usuario, token_reinicio_expira
    FROM usuarios
    WHERE token_reinicio = '{$tokenEsc}'
    LIMIT 1
");

if ($db->num_rows($res) === 0) {
    redirigirError($token, 'token_invalido');
}

$usuario = $db->fetch_assoc($res);
$expira = strtotime((string)($usuario["token_reinicio_expira"] ?? ""));
if ($expira === false || $expira < time()) {
    redirigirError($token, 'token_vencido');
}

$claveEsc = $db->escape_string($clave);
$idUsuario = (int)($usuario["id_usuario"] ?? 0);

$db->consulta("
    UPDATE usuarios
    SET
        clave_hash = '{$claveEsc}',
        token_reinicio = NULL,
        token_reinicio_expira = NULL,
        intentos = 0,
        updated_at = NOW()
    WHERE id_usuario = {$idUsuario}
    LIMIT 1
");

header("Location: ../../login.php?m=clave_creada");
exit;
