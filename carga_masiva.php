<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/class/conexion.php";
require_once __DIR__ . "/class/funciones.php";

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");
$funciones = new Funciones($db);
$headerTitle = "Carga masiva de horarios";
$menuLateralPath = __DIR__ . "/menu_lateral.php";
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Carga masiva | Calculadora de Horas</title>
    <link rel="stylesheet" type="text/css" href="css/principal.css?v=<?= filemtime(__DIR__ . '/css/principal.css') ?>">
    <link rel="stylesheet" type="text/css" href="css/menu_lateral.css?v=<?= filemtime(__DIR__ . '/css/menu_lateral.css') ?>">
    <link rel="icon" type="image/png" href="imagenes/logo_1.jpg" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .carga-main{
            display:grid;
            gap:14px;
        }
        .carga-hero{
            display:grid;
            grid-template-columns:minmax(0,1fr) auto;
            gap:18px;
            align-items:center;
            padding:18px;
        }
        .carga-title{
            margin:0;
            color:var(--inst-blue);
            font-size:22px;
            font-weight:900;
        }
        .carga-subtitle{
            margin:6px 0 0;
            color:var(--muted);
            font-size:14px;
            line-height:1.45;
            max-width:72ch;
        }
        .carga-action{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            min-height:44px;
            padding:0 16px;
            border-radius:12px;
            border:1px solid rgba(29,111,66,.28);
            background:#1d6f42;
            color:#fff;
            font-weight:900;
            text-decoration:none;
            box-shadow:0 12px 24px rgba(29,111,66,.16);
            white-space:nowrap;
        }
        .carga-action:hover{
            filter:brightness(.96);
        }
        .carga-grid{
            display:grid;
            grid-template-columns:minmax(0,1.2fr) minmax(320px,.8fr);
            gap:14px;
            align-items:start;
        }
        .carga-section{
            padding:16px;
        }
        .carga-section h3{
            margin:0 0 10px;
            font-size:15px;
            font-weight:900;
            color:#0f172a;
        }
        .carga-steps{
            margin:0;
            padding-left:20px;
            color:#334155;
            font-size:14px;
            line-height:1.65;
        }
        .format-table{
            width:100%;
            border-collapse:separate;
            border-spacing:0;
            overflow:hidden;
            border:1px solid rgba(15,23,42,.10);
            border-radius:12px;
            font-size:13px;
        }
        .format-table th,
        .format-table td{
            padding:10px 12px;
            border-bottom:1px solid rgba(15,23,42,.08);
            text-align:left;
        }
        .format-table th{
            background:#eef2f7;
            font-size:12px;
            text-transform:uppercase;
            letter-spacing:.04em;
        }
        .format-table tr:last-child td{
            border-bottom:0;
        }
        .badge-required{
            display:inline-flex;
            align-items:center;
            border-radius:999px;
            padding:3px 8px;
            background:rgba(0,111,173,.10);
            color:var(--inst-blue);
            font-weight:900;
            font-size:11px;
        }
        .note-box{
            display:grid;
            gap:10px;
            margin-top:12px;
        }
        .note-item{
            display:grid;
            grid-template-columns:34px minmax(0,1fr);
            gap:10px;
            align-items:start;
            padding:12px;
            border:1px solid rgba(15,23,42,.10);
            border-radius:12px;
            background:#fbfbfb;
        }
        .note-item i{
            width:34px;
            height:34px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius:10px;
            background:rgba(0,111,173,.10);
            color:var(--inst-blue);
        }
        .note-item strong{
            display:block;
            margin-bottom:3px;
            font-size:13px;
        }
        .note-item span{
            color:#64748b;
            font-size:12px;
            line-height:1.45;
        }
        @media (max-width: 980px){
            .carga-hero,
            .carga-grid{
                grid-template-columns:1fr;
            }
            .carga-action{
                width:100%;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <?php if (is_file($menuLateralPath)): ?>
        <?php include $menuLateralPath; ?>
    <?php endif; ?>

    <?php include __DIR__ . "/header.php"; ?>

    <main class="page-shell carga-main">
        <section class="card carga-hero">
            <div>
                <h2 class="carga-title">Carga masiva</h2>
                <p class="carga-subtitle">
                    Descarga la plantilla, completa una fila por funcionario y conserva los encabezados tal como vienen.
                    La plantilla incluye los datos base y los horarios de lunes a viernes. Los totales se calculan en el sistema.
                </p>
            </div>
            <a class="carga-action" href="descarga/plantilla_carga_masiva.php">
                <i class="bi bi-file-earmark-excel-fill"></i>
                Descargar plantilla Excel
            </a>
        </section>

        <div class="carga-grid">
            <section class="card carga-section">
                <div class="card-head" style="margin:-16px -16px 14px;">
                    <div>
                        <h2>Formato del archivo</h2>
                        <small>Columnas que debe mantener la plantilla.</small>
                    </div>
                </div>
                <table class="format-table" aria-label="Formato de carga masiva">
                    <thead>
                        <tr>
                            <th>Columna</th>
                            <th>Dato</th>
                            <th>Regla inicial</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>A</td><td>RUN</td><td><span class="badge-required">Obligatorio</span></td></tr>
                        <tr><td>B</td><td>Nombre</td><td><span class="badge-required">Obligatorio</span></td></tr>
                        <tr><td>C</td><td>Apellido paterno</td><td><span class="badge-required">Obligatorio</span></td></tr>
                        <tr><td>D</td><td>Apellido materno</td><td>Opcional</td></tr>
                        <tr><td>E</td><td>Genero</td><td>Masculino, Femenino u Otro</td></tr>
                        <tr><td>F</td><td>Telefono</td><td>Opcional</td></tr>
                        <tr><td>G</td><td>Observacion</td><td>Opcional</td></tr>
                        <tr><td>H:AA</td><td>Entradas y salidas</td><td>Formato HH:MM</td></tr>
                    </tbody>
                </table>
            </section>

            <section class="card carga-section">
                <div class="card-head" style="margin:-16px -16px 14px;">
                    <div>
                        <h2>Cómo funciona</h2>
                        <small>Preparación antes de importar.</small>
                    </div>
                </div>
                <ol class="carga-steps">
                    <li>Descarga la plantilla Excel desde el botón superior.</li>
                    <li>Completa una fila por funcionario, sin cambiar el nombre ni el orden de las columnas.</li>
                    <li>Usa las columnas de horario para registrar las entradas y salidas.</li>
                    <li>Guarda el archivo en formato .xlsx.</li>
                    <li>Carga el archivo para que el sistema lea los horarios y calcule los totales.</li>
                </ol>
                <div class="note-box">
                    <div class="note-item">
                        <i class="bi bi-table"></i>
                        <div>
                            <strong>Encabezados fijos</strong>
                            <span>El sistema usará los encabezados para reconocer cada dato de la carga.</span>
                        </div>
                    </div>
                    <div class="note-item">
                        <i class="bi bi-person-lines-fill"></i>
                        <div>
                            <strong>Datos del funcionario</strong>
                            <span>La fila reúne la información personal y la distribución semanal del horario.</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>

<script>
function updateHeaderDateTime() {
    const now = new Date();
    const fecha = new Intl.DateTimeFormat("es-CL", {
        weekday: "long",
        year: "numeric",
        month: "2-digit",
        day: "2-digit"
    }).format(now);
    const hora = new Intl.DateTimeFormat("es-CL", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit"
    }).format(now);

    const fechaEl = document.getElementById("uiFecha");
    const horaEl = document.getElementById("uiHora");
    if (fechaEl) fechaEl.textContent = fecha;
    if (horaEl) horaEl.textContent = hora;
}

updateHeaderDateTime();
setInterval(updateHeaderDateTime, 1000);
</script>
</body>
</html>
