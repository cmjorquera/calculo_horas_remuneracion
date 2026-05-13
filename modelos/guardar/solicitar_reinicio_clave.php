<?php
require_once __DIR__ . "/../../class/conexion.php";
require_once __DIR__ . "/../../envio_correo_recuperado.php";

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");

function redirigirSolicitud($mensaje, $email = '')
{
    $destino = "../../recuperar_pass.php?m=" . rawurlencode($mensaje);
    if ($email !== '') {
        $destino .= "&email=" . rawurlencode($email);
    }
    header("Location: " . $destino);
    exit;
}

function generarTokenSeguro($largo = 64)
{
    $bytes = random_bytes((int)ceil($largo / 2));
    return substr(bin2hex($bytes), 0, $largo);
}

$email = trim((string)($_POST["email"] ?? ""));

if ($email === '') {
    redirigirSolicitud('campos_vacios');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirigirSolicitud('email_invalido', $email);
}

$emailEsc = $db->escape_string($email);
$res = $db->consulta("
    SELECT
        u.id_usuario,
        u.email,
        u.identificador,
        u.nombre,
        u.apellido_paterno,
        u.apellido_materno,
        u.estado,
        COALESCE(NULLIF(c.nco_colegio, ''), c.nom_colegio, 'Seduc') AS colegio
    FROM usuarios u
    LEFT JOIN colegio c
        ON c.id_colegio = u.id_colegio
    WHERE u.email = '{$emailEsc}'
    LIMIT 1
");

if ($db->num_rows($res) === 0) {
    redirigirSolicitud('email_no_existe', $email);
}

$usuario = $db->fetch_assoc($res);
$token = generarTokenSeguro(64);
$tokenEsc = $db->escape_string($token);
$idUsuario = (int)($usuario["id_usuario"] ?? 0);

$db->consulta("
    UPDATE usuarios
    SET
        token_reinicio = '{$tokenEsc}',
        token_reinicio_expira = DATE_ADD(NOW(), INTERVAL 3 DAY),
        updated_at = NOW()
    WHERE id_usuario = {$idUsuario}
    LIMIT 1
");

$nombreCompleto = trim(implode(" ", array_filter([
    trim((string)($usuario["nombre"] ?? "")),
    trim((string)($usuario["apellido_paterno"] ?? "")),
    trim((string)($usuario["apellido_materno"] ?? ""))
])));

$correo = enviarCorreoRecuperacionClave([
    'token' => $token,
    'email' => $usuario["email"] ?? $email,
    'nombre' => $nombreCompleto !== '' ? $nombreCompleto : ($usuario["identificador"] ?? 'Usuario'),
]);

if (!($correo["ok"] ?? false)) {
    redirigirSolicitud('error_envio', $email);
}

header("Location: ../../login.php?m=enlace_recuperacion_enviado");
exit;
