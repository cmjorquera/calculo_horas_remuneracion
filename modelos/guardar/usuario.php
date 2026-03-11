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

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");

function salirError($msg)
{
    echo json_encode(["ok" => false, "msg" => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function esc($db, $valor)
{
    return $db->escape_string(trim((string)$valor));
}

$identificador = trim((string)($_POST["identificador"] ?? ""));
$email = trim((string)($_POST["email"] ?? ""));
$clave = trim((string)($_POST["clave"] ?? ""));
$nombre = trim((string)($_POST["nombre"] ?? ""));
$apellidoPaterno = trim((string)($_POST["apellido_paterno"] ?? ""));
$apellidoMaterno = trim((string)($_POST["apellido_materno"] ?? ""));
$run = trim((string)($_POST["run"] ?? ""));
$telefono = trim((string)($_POST["telefono"] ?? ""));
$idRol = (int)($_POST["id_rol"] ?? 0);
$idColegio = (int)($_POST["id_colegio"] ?? 0);
$estado = (int)($_POST["estado"] ?? 1);

if ($identificador === "") salirError("El identificador es obligatorio.");
if ($email === "") salirError("El email es obligatorio.");
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) salirError("El email no es válido.");
if ($clave === "") salirError("La contraseña es obligatoria.");
if (mb_strlen($clave, 'UTF-8') < 4) salirError("La contraseña debe tener al menos 4 caracteres.");
if ($nombre === "") salirError("El nombre es obligatorio.");
if ($apellidoPaterno === "") salirError("El apellido paterno es obligatorio.");
if ($idRol <= 0) salirError("Debes seleccionar un rol.");
if ($idColegio <= 0) salirError("Debes seleccionar un colegio.");
if (!in_array($estado, [0, 1, 2], true)) salirError("Estado inválido.");

$identificadorEsc = esc($db, $identificador);
$emailEsc = esc($db, $email);
$claveEsc = esc($db, $clave);
$nombreEsc = esc($db, $nombre);
$apellidoPaternoEsc = esc($db, $apellidoPaterno);
$apellidoMaternoEsc = esc($db, $apellidoMaterno);
$runEsc = esc($db, $run);
$telefonoEsc = esc($db, $telefono);

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
        {$estado},
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

$db->consulta("COMMIT");

echo json_encode([
    "ok" => true,
    "msg" => "Usuario creado correctamente.",
    "id_usuario" => $idUsuario,
], JSON_UNESCAPED_UNICODE);
