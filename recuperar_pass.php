<?php
require_once __DIR__ . "/class/conexion.php";

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");
$token = trim((string)($_GET["token"] ?? ""));
$mensajeCodigo = trim((string)($_GET["m"] ?? ""));
$emailIngresado = trim((string)($_GET["email"] ?? ""));

$estadoVista = [
    "modo" => "solicitud",
    "valido" => false,
    "titulo" => "Recuperar clave",
    "subtitle" => "Ingresa tu correo para enviarte un enlace seguro de recuperacion.",
    "mensaje" => "",
    "usuario" => null
];

if ($token !== "") {
    $tokenEsc = $db->escape_string($token);
    $res = $db->consulta("
        SELECT
            u.id_usuario,
            u.identificador,
            u.email,
            u.nombre,
            u.apellido_paterno,
            u.apellido_materno,
            u.token_reinicio,
            u.token_reinicio_expira,
            COALESCE(NULLIF(c.nco_colegio, ''), c.nom_colegio, 'Seduc') AS colegio
        FROM usuarios u
        LEFT JOIN colegio c
            ON c.id_colegio = u.id_colegio
        WHERE u.token_reinicio = '{$tokenEsc}'
        LIMIT 1
    ");

    $estadoVista["modo"] = "restablecer";
    $estadoVista["titulo"] = "Crear nueva clave";
    $estadoVista["subtitle"] = "Completa tu nueva clave para recuperar el acceso.";

    if ($db->num_rows($res) > 0) {
        $usuario = $db->fetch_assoc($res);
        $expira = strtotime((string)($usuario["token_reinicio_expira"] ?? ""));

        if ($expira !== false && $expira >= time()) {
            $estadoVista["valido"] = true;
            $estadoVista["mensaje"] = "El enlace es valido. Define tu nueva clave personal.";
            $estadoVista["usuario"] = $usuario;
        } else {
            $estadoVista["mensaje"] = "Este enlace de recuperacion vencio. Solicita uno nuevo.";
        }
    } else {
        $estadoVista["mensaje"] = "El enlace de recuperacion no es valido.";
    }
}

$nombreCompleto = "";
if (!empty($estadoVista["usuario"])) {
    $u = $estadoVista["usuario"];
    $nombreCompleto = trim(implode(" ", array_filter([
        trim((string)($u["nombre"] ?? "")),
        trim((string)($u["apellido_paterno"] ?? "")),
        trim((string)($u["apellido_materno"] ?? ""))
    ])));
}

$mensajeAlerta = "";
$tipoAlerta = "danger";

if ($mensajeCodigo === "campos_vacios") {
    $mensajeAlerta = "Completa todos los campos obligatorios.";
} elseif ($mensajeCodigo === "email_invalido") {
    $mensajeAlerta = "Ingresa un correo valido.";
} elseif ($mensajeCodigo === "email_no_existe") {
    $mensajeAlerta = "No existe una cuenta registrada con ese correo.";
} elseif ($mensajeCodigo === "error_envio") {
    $mensajeAlerta = "No fue posible enviar el correo de recuperacion. Intenta nuevamente.";
} elseif ($mensajeCodigo === "solicitud_ok") {
    $mensajeAlerta = "Se envio un enlace de recuperacion a tu correo.";
    $tipoAlerta = "success";
} elseif ($mensajeCodigo === "clave_corta") {
    $mensajeAlerta = "La clave debe tener al menos 8 caracteres.";
} elseif ($mensajeCodigo === "clave_distinta") {
    $mensajeAlerta = "Las claves ingresadas no coinciden.";
} elseif ($mensajeCodigo === "token_invalido") {
    $mensajeAlerta = "El enlace de recuperacion no es valido.";
} elseif ($mensajeCodigo === "token_vencido") {
    $mensajeAlerta = "El enlace ya vencio. Solicita uno nuevo.";
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Recuperar clave | Calculo de Horas</title>
  <link rel="stylesheet" type="text/css" href="css/login.css?v=<?= filemtime(__DIR__ . '/css/login.css') ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
  <div class="wrap">
    <div class="panel form-panel">
      <div class="brand">
        <div class="logo">
          <img src="imagenes/logo_seduc_02.png" alt="Logo Seduc">
        </div>
        <div>
          <h1>Calculo de Horas</h1>
          <p>Recuperacion de contraseña</p>
        </div>
      </div>

      <div class="title"><?= htmlspecialchars($estadoVista["titulo"]) ?></div>
      <div class="subtitle"><?= htmlspecialchars($estadoVista["subtitle"]) ?></div>

      <?php if ($mensajeAlerta !== ""): ?>
        <div class="alert alert-<?= htmlspecialchars($tipoAlerta) ?>" style="margin:12px 0; border-radius:14px;"><?= htmlspecialchars($mensajeAlerta) ?></div>
      <?php endif; ?>

      <?php if ($estadoVista["modo"] === "solicitud"): ?>
        <form method="post" action="modelos/guardar/solicitar_reinicio_clave.php" autocomplete="off">
          <div class="field">
            <div class="label">Correo</div>
            <input class="input" type="email" name="email" maxlength="150" value="<?= htmlspecialchars($emailIngresado) ?>" placeholder="correo@colegio.cl" required>
          </div>
          <button class="btn" type="submit">Enviar enlace</button>
        </form>
      <?php elseif ($estadoVista["valido"]): ?>
        <div class="field">
          <div class="label">Cuenta</div>
          <input class="input" type="text" value="<?= htmlspecialchars($nombreCompleto) ?>" readonly>
        </div>
        <div class="field">
          <div class="label">Identificador</div>
          <input class="input" type="text" value="<?= htmlspecialchars((string)($estadoVista["usuario"]["identificador"] ?? "")) ?>" readonly>
        </div>
        <div class="field">
          <div class="label">Correo</div>
          <input class="input" type="text" value="<?= htmlspecialchars((string)($estadoVista["usuario"]["email"] ?? "")) ?>" readonly>
        </div>
        <div class="field">
          <div class="label">Colegio</div>
          <input class="input" type="text" value="<?= htmlspecialchars((string)($estadoVista["usuario"]["colegio"] ?? "Seduc")) ?>" readonly>
        </div>

        <form method="post" action="modelos/guardar/establecer_clave.php" autocomplete="off">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
          <input type="hidden" name="origen" value="recuperacion">
          <div class="field">
            <div class="label">Nueva clave</div>
            <div class="pw-wrap">
              <input class="input" id="pw" type="password" name="clave" minlength="8" maxlength="100" placeholder="Minimo 8 caracteres" required>
              <button class="pw-btn" type="button" id="togglePw" aria-label="Mostrar clave">
                <i class="bi bi-eye-fill"></i>
              </button>
            </div>
          </div>
          <div class="field">
            <div class="label">Confirmar clave</div>
            <div class="pw-wrap">
              <input class="input" id="pw2" type="password" name="clave_confirmacion" minlength="8" maxlength="100" placeholder="Repite la clave" required>
              <button class="pw-btn" type="button" id="togglePw2" aria-label="Mostrar confirmacion">
                <i class="bi bi-eye-fill"></i>
              </button>
            </div>
          </div>
          <button class="btn" type="submit">Guardar nueva clave</button>
        </form>
      <?php else: ?>
        <div class="alert alert-danger" style="margin:12px 0; border-radius:14px;"><?= htmlspecialchars($estadoVista["mensaje"]) ?></div>
      <?php endif; ?>

      <div class="row" style="margin-top:18px;">
        <a class="link" href="login.php">Volver al inicio</a>
        <?php if ($estadoVista["modo"] === "restablecer"): ?>
          <a class="link" href="recuperar_pass.php">Solicitar un nuevo enlace</a>
        <?php endif; ?>
      </div>

      <div class="foot">© <?= date('Y') ?> · Seduc</div>
    </div>

    <div class="panel info-panel">
      <div class="info-inner">
        <div>
          <div class="badge"><i class="bi bi-envelope-check"></i></div>
          <div class="info-title">Recupera tu acceso de forma segura.</div>
          <p class="info-text">
            El sistema valida tu correo registrado, genera un enlace temporal y te permite crear una nueva clave con confirmacion obligatoria.
          </p>
          <div class="cards">
            <div class="mini">
              <div class="k"><i class="bi bi-shield-lock-fill"></i> Enlace temporal</div>
              <div class="v">Cada solicitud genera un token con vigencia limitada por seguridad.</div>
            </div>
            <div class="mini">
              <div class="k"><i class="bi bi-key-fill"></i> Nueva clave</div>
              <div class="v">La nueva contraseña debe ingresarse dos veces y cumplir largo minimo.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    function bindToggle(buttonId, inputId) {
      const input = document.getElementById(inputId);
      const button = document.getElementById(buttonId);
      if (!input || !button) return;

      button.addEventListener("click", () => {
        const isPassword = input.type === "password";
        input.type = isPassword ? "text" : "password";
        button.innerHTML = isPassword ? '<i class="bi bi-eye-slash-fill"></i>' : '<i class="bi bi-eye-fill"></i>';
      });
    }

    bindToggle("togglePw", "pw");
    bindToggle("togglePw2", "pw2");
  </script>
</body>
</html>
