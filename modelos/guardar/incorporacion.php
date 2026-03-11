<?php
require_once __DIR__ . "/class/conexion.php";

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");
$token = trim((string)($_GET["token"] ?? ""));
$mensajeCodigo = trim((string)($_GET["m"] ?? ""));
$estadoVista = [
    "valido" => false,
    "titulo" => "Enlace invalido",
    "mensaje" => "El enlace de incorporacion no es valido o ya no se encuentra disponible.",
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

    if ($db->num_rows($res) > 0) {
        $usuario = $db->fetch_assoc($res);
        $expira = strtotime((string)($usuario["token_reinicio_expira"] ?? ""));
        if ($expira !== false && $expira >= time()) {
            $estadoVista = [
                "valido" => true,
                "titulo" => "Define tu clave",
                "mensaje" => "Activa tu acceso al sistema completando tu clave personal.",
                "usuario" => $usuario
            ];
        } else {
            $estadoVista = [
                "valido" => false,
                "titulo" => "Enlace vencido",
                "mensaje" => "Este enlace de incorporacion vencio. Solicita un nuevo correo al administrador del sistema.",
                "usuario" => null
            ];
        }
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
if ($mensajeCodigo === "campos_vacios") {
    $mensajeAlerta = "Completa ambos campos de clave.";
} elseif ($mensajeCodigo === "clave_corta") {
    $mensajeAlerta = "La clave debe tener al menos 8 caracteres.";
} elseif ($mensajeCodigo === "clave_distinta") {
    $mensajeAlerta = "La confirmacion de clave no coincide.";
} elseif ($mensajeCodigo === "token_vencido") {
    $mensajeAlerta = "El enlace ya vencio. Solicita un nuevo correo de incorporacion.";
} elseif ($mensajeCodigo === "token_invalido") {
    $mensajeAlerta = "El enlace de incorporacion no es valido.";
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Incorporacion | Calculo de Horas</title>
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
          <p>Incorporacion de usuario</p>
        </div>
      </div>

      <div class="title"><?= htmlspecialchars($estadoVista["titulo"]) ?></div>
      <div class="subtitle"><?= htmlspecialchars($estadoVista["mensaje"]) ?></div>

      <?php if ($mensajeAlerta !== ""): ?>
        <div class="alert alert-danger" style="margin:12px 0; border-radius:14px;"><?= htmlspecialchars($mensajeAlerta) ?></div>
      <?php endif; ?>

      <?php if ($estadoVista["valido"]): ?>
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
          <button class="btn" type="submit">Guardar clave</button>
        </form>
      <?php else: ?>
        <a class="btn" style="display:inline-block;text-align:center;text-decoration:none;" href="login.php">Volver al inicio</a>
      <?php endif; ?>

      <div class="foot">© <?= date('Y') ?> · Seduc</div>
    </div>

    <div class="panel info-panel">
      <div class="info-inner">
        <div>
          <div class="badge"><i class="bi bi-shield-check"></i></div>
          <div class="info-title">Tu acceso comienza aqui.</div>
          <p class="info-text">
            Define una clave segura para completar tu incorporacion al sistema y acceder a la plataforma de gestion horaria.
          </p>
          <div class="cards">
            <div class="mini">
              <div class="k"><i class="bi bi-person-check-fill"></i> Activacion</div>
              <div class="v">Completa tu incorporacion con una clave personal.</div>
            </div>
            <div class="mini">
              <div class="k"><i class="bi bi-lock-fill"></i> Seguridad</div>
              <div class="v">El enlace de acceso tiene vigencia limitada.</div>
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
