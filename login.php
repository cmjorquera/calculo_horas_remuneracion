<?php
// login.php (solo maqueta visual)
// Si luego conectas backend, cambia el action a tu validador (ej: auth.php)
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Ingreso | Cálculo de Horas</title>
    <link rel="stylesheet" type="text/css" href="css/login.css">

  <!-- Bootstrap Icons (para el ojito / iconos) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

</head>

<body>
  <div class="wrap">

    <!-- Panel Form -->
    <div class="panel form-panel">
      <div class="brand">
        <div class="logo">CH</div>
        <div>
          <h1>Cálculo de Horas</h1>
          <p>Sistema de jornada, colación y resumen</p>
        </div>
      </div>

      <div class="title">Iniciar sesión</div>
      <div class="subtitle">Ingresa tus credenciales para continuar.</div>
      <!-- Validar mensajes de error (ej: ?m=usuario_no_existe) -->
<?php
          $msg = isset($_GET["m"]) ? $_GET["m"] : "";
          $intentos = isset($_GET["i"]) ? (int)$_GET["i"] : 0;

          function alerta($texto, $tipo="danger") {
            echo '<div class="alert alert-'.$tipo.'" style="margin:12px 0; border-radius:14px;">'.$texto.'</div>';
          }

          if ($msg === "campos_vacios") {
            alerta("Por favor ingresa usuario y contraseña.");
          }

          if ($msg === "usuario_no_existe") {
            alerta("Usuario no registrado. Si crees que es un error, comunícate con el equipo de Informática.");
          }

          if ($msg === "clave_incorrecta") {
            alerta("Contraseña incorrecta. Intento $intentos de 3.");
          }

          if ($msg === "inactivo") {
            alerta("Tu cuenta está desactivada. Comunícate con el equipo de Informática para habilitarla.");
          }

          if ($msg === "bloqueado") {
            alerta("Tu cuenta está bloqueada por seguridad. Comunícate con el equipo de Informática para restablecer el acceso.");
          }

          if ($msg === "bloqueado_3") {
            // ✅ Mensaje mejorado (lo que pediste)
            alerta("Bloqueo de seguridad: se registraron 3 intentos fallidos de ingreso. Tu cuenta fue bloqueada temporalmente para proteger tu información. Comunícate con el equipo de Informática para recuperar el acceso.");
          }
?>

      <form method="post" action="validaciones.php" autocomplete="off">
        <div class="field">
          <div class="label">Usuario</div>
          <input class="input" type="text" name="usuario" placeholder="Ej: cjorquera" >
        </div>

        <div class="field">
          <div class="label">Contraseña</div>
          <div class="pw-wrap">
            <input class="input" id="pw" type="password" name="password" placeholder="••••••••" >
            <button class="pw-btn" type="button" id="togglePw" aria-label="Mostrar contraseña">
              <i class="bi bi-eye-fill"></i>
            </button>
          </div>
        </div>
<!-- 
        <div class="row">
          <label class="check">
            <input type="checkbox" name="remember" value="1">
            Mantener sesión
          </label>
          <a class="link" href="#" onclick="return false;">¿Olvidaste tu contraseña?</a>
        </div> -->

        <button class="btn" type="submit">
          Entrar <i class="bi bi-arrow-right-short"></i>
        </button>

        <div class="foot">
          <span>© <?php echo date('Y'); ?> • Versión 1.0</span>
        </div>
      </form>
    </div>

    <!-- Panel Info -->
    <div class="panel info-panel">
      <div class="info-inner">
        <div>
          <div class="badge">
            <i class="bi bi-shield-check"></i>
            <!-- Acceso seguro -->
          </div>

          <div class="info-title">
            Todo tu cálculo de jornada<br>en un solo lugar.
          </div>

          <p class="info-text">
            Marca horarios por día, calcula colación automática y obtén totales
            cronológicos y pedagógicos con un resumen claro.
          </p>

          <div class="cards">
            <div class="mini">
              <div class="k"><i class="bi bi-clock-history"></i> Jornada & Colación</div>
              <div class="v">Diferencias exactas por día y totales semanales.</div>
            </div>
            <div class="mini">
              <div class="k"><i class="bi bi-bar-chart-fill"></i> Resumen</div>
              <div class="v">Horas lectivas/no lectivas y conversiones.</div>
            </div>
          </div>
        </div>

        <div class="info-footer">
          <div><i class="bi bi-info-circle"></i></div>
          <div><i class="bi bi-wifi"></i> Seduc <?php echo date('Y'); ?></div>
        </div>
      </div>
    </div>

  </div>

  <script>
    // Mostrar/ocultar contraseña (solo UX)
    const pw = document.getElementById("pw");
    const btn = document.getElementById("togglePw");
    if (btn && pw) {
      btn.addEventListener("click", () => {
        const isPass = pw.type === "password";
        pw.type = isPass ? "text" : "password";
        btn.innerHTML = isPass ? '<i class="bi bi-eye-slash-fill"></i>' : '<i class="bi bi-eye-fill"></i>';
      });
    }
  </script>
</body>
</html>
