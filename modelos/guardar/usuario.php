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
require_once __DIR__ . "/../../envio_correo_recuperado.php";

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");
$funciones = new Funciones($db);

function salirError($msg)
{
    echo json_encode(["ok" => false, "msg" => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function esc($db, $valor)
{
    return $db->escape_string(trim((string)$valor));
}

function generarClaveTemporal($largo = 12)
{
    $bytes = random_bytes((int)ceil($largo / 2));
    return substr(bin2hex($bytes), 0, $largo);
}

function generarTokenIncorporacion($largo = 64)
{
    $bytes = random_bytes((int)ceil($largo / 2));
    return substr(bin2hex($bytes), 0, $largo);
}

function normalizarIdentificadorBase($valor)
{
    $valor = trim((string)$valor);
    if ($valor === "") {
        return "";
    }

    if (function_exists('iconv')) {
        $convertido = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);
        if ($convertido !== false) {
            $valor = $convertido;
        }
    }

    $valor = strtolower($valor);
    $valor = preg_replace('/[^a-z0-9]+/', '', $valor);
    return trim((string)$valor);
}

function generarIdentificadorUsuario($db, $nombre, $apellidoPaterno)
{
    $nombreBase = normalizarIdentificadorBase($nombre);
    $apellidoBase = normalizarIdentificadorBase($apellidoPaterno);

    $inicial = $nombreBase !== "" ? substr($nombreBase, 0, 1) : "u";
    $base = $inicial . ($apellidoBase !== "" ? $apellidoBase : "usuario");
    $base = substr($base, 0, 60);

    if ($base === "") {
        $base = "usuario";
    }

    $identificador = $base;
    $correlativo = 1;

    while (true) {
        $identificadorEsc = esc($db, $identificador);
        $res = $db->consulta("
            SELECT id_usuario
            FROM usuarios
            WHERE identificador = '{$identificadorEsc}'
            LIMIT 1
        ");

        if ($db->num_rows($res) === 0) {
            return $identificador;
        }

        $sufijo = (string)$correlativo;
        $identificador = substr($base, 0, max(1, 60 - strlen($sufijo))) . $sufijo;
        $correlativo++;
    }
}

$email = trim((string)($_POST["email"] ?? ""));
$nombre = trim((string)($_POST["nombre"] ?? ""));
$apellidoPaterno = trim((string)($_POST["apellido_paterno"] ?? ""));
$apellidoMaterno = trim((string)($_POST["apellido_materno"] ?? ""));
$run = trim((string)($_POST["run"] ?? ""));
$telefono = trim((string)($_POST["telefono"] ?? ""));
$idRol = (int)($_POST["id_rol"] ?? 0);
$idColegio = (int)($_POST["id_colegio"] ?? 0);
$estado = (int)($_POST["estado"] ?? 1);
$colegioNombre = trim((string)($_POST["colegio_nombre"] ?? ""));
$idUsuarioSesion = (int)($_SESSION["id_usuario"] ?? 0);

if ($idUsuarioSesion !== 2) {
    $idRol = 2;
}

if ($email === "") salirError("El email es obligatorio.");
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) salirError("El email no es válido.");
if ($nombre === "") salirError("El nombre es obligatorio.");
if ($apellidoPaterno === "") salirError("El apellido paterno es obligatorio.");
if ($idRol <= 0) salirError("Debes seleccionar un rol.");
if ($idColegio <= 0) salirError("Debes seleccionar un colegio.");
if (!in_array($estado, [0, 1, 2], true)) salirError("Estado inválido.");

$identificador = generarIdentificadorUsuario($db, $nombre, $apellidoPaterno);
$estadoCreacion = 0;

$claveTemporal = generarClaveTemporal(12);
$tokenIncorporacion = generarTokenIncorporacion(64);

$identificadorEsc = esc($db, $identificador);
$emailEsc = esc($db, $email);
$claveEsc = esc($db, $claveTemporal);
$nombreEsc = esc($db, $nombre);
$apellidoPaternoEsc = esc($db, $apellidoPaterno);
$apellidoMaternoEsc = esc($db, $apellidoMaterno);
$runEsc = esc($db, $run);
$telefonoEsc = esc($db, $telefono);
$tokenIncorporacionEsc = esc($db, $tokenIncorporacion);

$resRol = $db->consulta("
    SELECT id_rol
    FROM roles
    WHERE id_rol = {$idRol}
    LIMIT 1
");
if ($db->num_rows($resRol) === 0) {
    salirError("El rol seleccionado no existe.");
}

$resColegio = $db->consulta("
    SELECT id_colegio
    FROM colegio
    WHERE id_colegio = {$idColegio}
    LIMIT 1
");
if ($db->num_rows($resColegio) === 0) {
    salirError("El colegio seleccionado no existe.");
}

$resDuplicado = $db->consulta("
    SELECT id_usuario
    FROM usuarios
    WHERE identificador = '{$identificadorEsc}'
       OR email = '{$emailEsc}'
    LIMIT 1
");
if ($db->num_rows($resDuplicado) > 0) {
    salirError("Ya existe un usuario con ese identificador o email.");
}

$db->consulta("START TRANSACTION");

$sqlUsuario = "
    INSERT INTO usuarios (
        identificador,
        email,
        clave_hash,
        nombre,
        apellido_paterno,
        apellido_materno,
        run,
        telefono,
        id_colegio,
        token_reinicio,
        token_reinicio_expira,
        estado,
        intentos,
        created_at,
        updated_at
    ) VALUES (
        '{$identificadorEsc}',
        '{$emailEsc}',
        '{$claveEsc}',
        '{$nombreEsc}',
        '{$apellidoPaternoEsc}',
        " . ($apellidoMaternoEsc === "" ? "NULL" : "'{$apellidoMaternoEsc}'") . ",
        " . ($runEsc === "" ? "NULL" : "'{$runEsc}'") . ",
        " . ($telefonoEsc === "" ? "NULL" : "'{$telefonoEsc}'") . ",
        {$idColegio},
        '{$tokenIncorporacionEsc}',
        DATE_ADD(NOW(), INTERVAL 3 DAY),
        {$estadoCreacion},
        0,
        NOW(),
        NOW()
    )
";

$errorInsertUsuario = $db->guardar($sqlUsuario);
if ($errorInsertUsuario !== 0) {
    $db->consulta("ROLLBACK");
    salirError("No se pudo crear el usuario.");
}

$idUsuario = (int)$db->insert_id();
if ($idUsuario <= 0) {
    $db->consulta("ROLLBACK");
    salirError("No se pudo obtener el usuario creado.");
}

$sqlRol = "
    INSERT INTO usuario_rol_colegio (
        id_usuario,
        id_rol,
        id_colegio,
        estado,
        created_at
    ) VALUES (
        {$idUsuario},
        {$idRol},
        {$idColegio},
        1,
        NOW()
    )
";

$errorInsertRol = $db->guardar($sqlRol);
if ($errorInsertRol !== 0) {
    $db->consulta("ROLLBACK");
    salirError("No se pudo asignar el rol al usuario.");
}

$menusPermitidos = ["empleados", "graficos"];
if ($idRol === 1) {
    $menusPermitidos[] = "usuarios";
}
$funciones->guardarPermisosMenuUsuario($idUsuario, $menusPermitidos);

$db->consulta("COMMIT");

$correoBienvenida = enviarCorreoBienvenidaUsuario([
    'token' => $tokenIncorporacion,
    'email' => $email,
    'nombre' => trim($nombre . ' ' . $apellidoPaterno . ' ' . $apellidoMaterno),
    'identificador' => $identificador,
    'colegio' => $colegioNombre !== '' ? $colegioNombre : 'Seduc'
]);

echo json_encode([
    "ok" => true,
    "msg" => "Usuario creado correctamente.",
    "id_usuario" => $idUsuario,
    "mail_ok" => (bool)($correoBienvenida['ok'] ?? false),
    "mail_msg" => (string)($correoBienvenida['msg'] ?? ''),
], JSON_UNESCAPED_UNICODE);
