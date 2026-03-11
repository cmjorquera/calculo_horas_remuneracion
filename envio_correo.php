<?php
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

function appBaseUrl()
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = str_replace('\\', '/', dirname($scriptName));
    $basePath = $basePath === '/' ? '' : rtrim($basePath, '/');
    return $scheme . '://' . $host . $basePath;
}

function correoConfig()
{
    return [
        'host' => getenv('SMTP_HOST') ?: '',
        'port' => (int)(getenv('SMTP_PORT') ?: 587),
        'username' => getenv('SMTP_USER') ?: '',
        'password' => getenv('SMTP_PASS') ?: '',
        'from_email' => getenv('SMTP_FROM_EMAIL') ?: 'no-reply@seduc.cl',
        'from_name' => getenv('SMTP_FROM_NAME') ?: 'Sistema Calculo de Horas',
        'secure' => getenv('SMTP_SECURE') ?: 'tls'
    ];
}

function plantillaBienvenidaHtml(array $data)
{
    $nombre = htmlspecialchars((string)($data['nombre'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8');
    $colegio = htmlspecialchars((string)($data['colegio'] ?? 'Seduc'), ENT_QUOTES, 'UTF-8');
    $identificador = htmlspecialchars((string)($data['identificador'] ?? ''), ENT_QUOTES, 'UTF-8');
    $activationUrl = htmlspecialchars((string)($data['activation_url'] ?? '#'), ENT_QUOTES, 'UTF-8');
    $logoUrl = htmlspecialchars((string)($data['logo_url'] ?? ''), ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bienvenido al sistema</title>
</head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:Segoe UI,Arial,sans-serif;color:#0f172a;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;padding:28px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 20px 40px rgba(15,23,42,.08);">
          <tr>
            <td style="background:linear-gradient(135deg,#0b5e8a,#0e6e9d);padding:28px 32px;text-align:center;">
              <img src="{$logoUrl}" alt="Seduc" style="max-width:160px;width:100%;height:auto;display:block;margin:0 auto 18px auto;">
              <div style="font-size:28px;font-weight:800;color:#ffffff;letter-spacing:-.3px;">Bienvenido al sistema</div>
              <div style="font-size:15px;color:rgba(255,255,255,.88);margin-top:8px;">Calculo de Horas</div>
            </td>
          </tr>
          <tr>
            <td style="padding:32px;">
              <p style="margin:0 0 14px 0;font-size:16px;line-height:1.6;">Hola <strong>{$nombre}</strong>,</p>
              <p style="margin:0 0 14px 0;font-size:15px;line-height:1.7;color:#334155;">
                Te damos la bienvenida al sistema de Calculo de Horas. Ya fue creada tu cuenta corporativa para incorporarte a la plataforma y gestionar tu acceso.
              </p>
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:18px 0;background:#f8fafc;border:1px solid #e2e8f0;border-radius:18px;">
                <tr>
                  <td style="padding:18px 20px;">
                    <div style="font-size:13px;color:#64748b;margin-bottom:6px;">Resumen de tu cuenta</div>
                    <div style="font-size:14px;line-height:1.8;color:#0f172a;">
                      <strong>Nombre:</strong> {$nombre}<br>
                      <strong>Identificador:</strong> {$identificador}<br>
                      <strong>Colegio:</strong> {$colegio}
                    </div>
                  </td>
                </tr>
              </table>
              <p style="margin:0 0 18px 0;font-size:15px;line-height:1.7;color:#334155;">
                Para activar tu acceso y definir tu clave personal, presiona el siguiente boton:
              </p>
              <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto 20px auto;">
                <tr>
                  <td align="center" bgcolor="#ffd24a" style="border-radius:14px;">
                    <a href="{$activationUrl}" style="display:inline-block;padding:14px 24px;font-size:15px;font-weight:800;color:#0f172a;text-decoration:none;border-radius:14px;">
                      Pincha aca para incorporarte al sistema
                    </a>
                  </td>
                </tr>
              </table>
              <p style="margin:0 0 10px 0;font-size:13px;line-height:1.7;color:#64748b;">
                Si el boton no funciona, copia y pega este enlace en tu navegador:
              </p>
              <p style="margin:0 0 18px 0;font-size:13px;line-height:1.7;word-break:break-all;color:#0b5e8a;">{$activationUrl}</p>
              <p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">
                Este enlace tiene una vigencia limitada por seguridad. Si no solicitaste este acceso, puedes ignorar este mensaje.
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding:18px 32px;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:12px;line-height:1.7;color:#64748b;text-align:center;">
              Sistema Calculo de Horas · Seduc
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}

function enviarCorreoBienvenidaUsuario(array $data)
{
    $config = correoConfig();
    if ($config['host'] === '' || $config['username'] === '' || $config['password'] === '') {
        return [
            'ok' => false,
            'msg' => 'SMTP no configurado. Define SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS y opcionalmente SMTP_FROM_EMAIL.'
        ];
    }

    $baseUrl = appBaseUrl();
    $logoUrl = $baseUrl . '/imagenes/logo_seduc_02.png';
    $activationUrl = $baseUrl . '/incorporacion.php?token=' . rawurlencode((string)($data['token'] ?? ''));

    $mail = new PHPMailer(true);

    try {
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->Port = $config['port'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        if ($config['secure'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress((string)$data['email'], trim((string)($data['nombre'] ?? 'Usuario')));
        $mail->isHTML(true);
        $mail->Subject = 'Bienvenido al sistema de Calculo de Horas';
        $mail->Body = plantillaBienvenidaHtml([
            'nombre' => $data['nombre'] ?? 'Usuario',
            'colegio' => $data['colegio'] ?? 'Seduc',
            'identificador' => $data['identificador'] ?? '',
            'activation_url' => $activationUrl,
            'logo_url' => $logoUrl
        ]);
        $mail->AltBody =
            "Hola " . ($data['nombre'] ?? 'Usuario') . ",\n\n" .
            "Te damos la bienvenida al sistema de Calculo de Horas.\n" .
            "Para incorporarte al sistema y definir tu clave, abre este enlace:\n" .
            $activationUrl . "\n\n" .
            "Identificador: " . ($data['identificador'] ?? '') . "\n" .
            "Colegio: " . ($data['colegio'] ?? 'Seduc');

        $mail->send();

        return [
            'ok' => true,
            'msg' => 'Correo de bienvenida enviado correctamente.'
        ];
    } catch (Exception $e) {
        return [
            'ok' => false,
            'msg' => 'No se pudo enviar el correo: ' . $mail->ErrorInfo
        ];
    }
}
