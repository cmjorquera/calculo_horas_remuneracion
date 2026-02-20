<?php
session_start();
require_once __DIR__ . "/class/conexion.php";

// Conexión (tu clase ignora params pero la mantengo como te gusta)
$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");

// Recibir POST
$usuario  = isset($_POST["usuario"]) ? trim($_POST["usuario"]) : "";
$password = isset($_POST["password"]) ? trim($_POST["password"]) : "";

// Validación básica
if ($usuario === "" || $password === "") {
    header("Location: login.php?m=campos_vacios");
    exit;
}

// Buscar usuario por identificador o email (por si quieres permitir ambos)
$usuarioEsc = $db->escape_string($usuario);

$sql = "
SELECT 
    u.id_usuario,
    u.identificador,
    u.email,
    u.nombre,
    u.apellido_paterno,
    u.apellido_materno,
    u.clave_hash,
    u.estado,
    u.intentos,
    u.id_colegio,
    c.nom_colegio
FROM usuarios u
LEFT JOIN colegio c ON c.id_colegio = u.id_colegio
WHERE u.email = '$usuarioEsc' OR u.identificador = '$usuarioEsc'
LIMIT 1
";

// echo $sql;
// die();
$res = $db->consulta($sql);

if ($db->num_rows($res) == 0) {
    // Usuario no existe
    header("Location: login.php?m=usuario_no_existe");
    exit;
}

$u = $db->fetch_assoc($res);

// Si está bloqueado
if ((int)$u["estado"] === 2) {
    header("Location: login.php?m=bloqueado");
    exit;
}

// Si está inactivo
if ((int)$u["estado"] === 0) {
    header("Location: login.php?m=inactivo");
    exit;
}

// Verificar contraseña (hash)
if ($password !== $u['clave_hash']) {

    $intentos = (int)$u["intentos"] + 1;

    if ($intentos >= 3) {
        // Bloquear usuario
        $db->consulta("UPDATE usuarios SET intentos = 3, estado = 2 WHERE id_usuario = ".$u["id_usuario"]);
        header("Location: login.php?m=bloqueado_3");
        exit;
    } else {
        // Sumar intento fallido
        $db->consulta("UPDATE usuarios SET intentos = $intentos WHERE id_usuario = ".$u["id_usuario"]);
        header("Location: login.php?m=clave_incorrecta&i=".$intentos);
        exit;
    }
}

// ✅ Login OK: resetear intentos y guardar último login
$db->consulta("UPDATE usuarios SET intentos = 0, ultimo_login = NOW() WHERE id_usuario = ".$u["id_usuario"]);

// Guardar sesión
$_SESSION["id_usuario"]        = (int)$u["id_usuario"];
$_SESSION["identificador"]     = $u["identificador"];
$_SESSION["nombre_completo"]    = $u["nombre"]." ".$u["apellido_paterno"]." ".$u["apellido_materno"];
$_SESSION["id_colegio"]      = $u["id_colegio"];
$_SESSION["nom_colegio"]     = $u["nom_colegio"] ?? "Sin colegio";


// Redirigir al sistema
header("Location: index.php");
exit;
