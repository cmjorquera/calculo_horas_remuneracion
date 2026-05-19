<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/class/conexion.php";
require_once __DIR__ . "/class/funciones.php";
require_once __DIR__ . "/vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");
$funciones = new Funciones($db);
$headerTitle = "Carga masiva de horarios";
$menuLateralPath = __DIR__ . "/menu_lateral.php";

function carga_texto_celda($sheet, string $cell): string
{
    $value = $sheet->getCell($cell)->getValue();
    if ($value === null) return '';
    return trim((string)$value);
}

function carga_hora_celda($sheet, string $cell): string
{
    $value = $sheet->getCell($cell)->getValue();
    if ($value === null || $value === '') return '';

    if (is_numeric($value)) {
        try {
            return ExcelDate::excelToDateTimeObject((float)$value)->format('H:i');
        } catch (Throwable $e) {
            return '';
        }
    }

    $value = trim((string)$value);
    if ($value === '') return '';
    if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $value, $m)) {
        $hh = (int)$m[1];
        $mm = (int)$m[2];
        if ($hh >= 0 && $hh <= 23 && $mm >= 0 && $mm <= 59) {
            return sprintf('%02d:%02d', $hh, $mm);
        }
    }

    return '';
}

function carga_hora_a_minutos(string $hhmm): int
{
    if (!preg_match('/^(\d{2}):(\d{2})$/', $hhmm, $m)) return 0;
    return ((int)$m[1] * 60) + (int)$m[2];
}

function carga_diff_bloque(string $inicio, string $termino, array &$avisos, string $dia, string $bloque): int
{
    if ($inicio === '' && $termino === '') return 0;
    if ($inicio === '00:00' && $termino === '00:00') return 0;
    if ($inicio === '' || $termino === '') {
        $avisos[] = "{$dia} {$bloque}: bloque incompleto.";
        return 0;
    }

    $ini = carga_hora_a_minutos($inicio);
    $fin = carga_hora_a_minutos($termino);
    if ($fin < $ini) {
        $avisos[] = "{$dia} {$bloque}: el término es menor al inicio.";
        return 0;
    }

    return $fin - $ini;
}

function carga_select_hora_html(string $hhmm): string
{
    $hhmm = preg_match('/^\d{2}:\d{2}$/', $hhmm) ? $hhmm : '00:00';
    [$hh, $mm] = explode(':', $hhmm);

    return '<span class="preview-time">' .
        '<select disabled><option>' . htmlspecialchars($hh) . '</option></select>' .
        '<span>:</span>' .
        '<select disabled><option>' . htmlspecialchars($mm) . '</option></select>' .
        '</span>';
}

function carga_nombre_colegio_sesion(Funciones $funciones, int $idColegio): string
{
    foreach ($funciones->obtenerColegios(false) as $colegio) {
        if ((int)($colegio['id_colegio'] ?? 0) === $idColegio) {
            return trim((string)($colegio['nco_colegio'] ?? $colegio['nom_colegio'] ?? '')) ?: 'Colegio';
        }
    }
    return 'Colegio';
}

function carga_colacion_preview(Funciones $funciones): int
{
    $opciones = $funciones->obtenerOpcionesColacion();
    foreach ($opciones as $opcion) {
        if ((int)($opcion['minutos'] ?? 0) === 40) return 40;
    }
    return (int)($opciones[0]['minutos'] ?? 0);
}

function carga_parse_excel(string $path, Funciones $funciones, string $colegioNombre): array
{
    $spreadsheet = IOFactory::load($path);
    $sheet = $spreadsheet->getSheetByName('Plantilla') ?: $spreadsheet->getActiveSheet();
    $highestRow = min((int)$sheet->getHighestDataRow(), 500);
    $dias = [
        ['label' => 'Lunes', 'cols' => ['H', 'I', 'J', 'K']],
        ['label' => 'Martes', 'cols' => ['L', 'M', 'N', 'O']],
        ['label' => 'Miércoles', 'cols' => ['P', 'Q', 'R', 'S']],
        ['label' => 'Jueves', 'cols' => ['T', 'U', 'V', 'W']],
        ['label' => 'Viernes', 'cols' => ['X', 'Y', 'Z', 'AA']],
    ];
    $minColacion = carga_colacion_preview($funciones);
    $rows = [];

    for ($r = 2; $r <= $highestRow; $r++) {
        $run = carga_texto_celda($sheet, 'A' . $r);
        $nombre = carga_texto_celda($sheet, 'B' . $r);
        $apPaterno = carga_texto_celda($sheet, 'C' . $r);
        $apMaterno = carga_texto_celda($sheet, 'D' . $r);
        $genero = carga_texto_celda($sheet, 'E' . $r);
        $telefono = carga_texto_celda($sheet, 'F' . $r);
        $observacion = carga_texto_celda($sheet, 'G' . $r);

        $hayDatos = trim($run . $nombre . $apPaterno . $apMaterno . $genero . $telefono . $observacion) !== '';
        $horario = [];
        $avisos = [];
        $totalMin = 0;

        foreach ($dias as $dia) {
            [$c1, $c2, $c3, $c4] = $dia['cols'];
            $manIni = carga_hora_celda($sheet, $c1 . $r);
            $manFin = carga_hora_celda($sheet, $c2 . $r);
            $tarIni = carga_hora_celda($sheet, $c3 . $r);
            $tarFin = carga_hora_celda($sheet, $c4 . $r);
            if ($manIni || $manFin || $tarIni || $tarFin) {
                $hayDatos = true;
            }

            $totalMin += carga_diff_bloque($manIni, $manFin, $avisos, $dia['label'], 'mañana');
            $totalMin += carga_diff_bloque($tarIni, $tarFin, $avisos, $dia['label'], 'tarde');

            $horario[] = [
                'dia' => $dia['label'],
                'man_ini' => $manIni ?: '00:00',
                'man_fin' => $manFin ?: '00:00',
                'tar_ini' => $tarIni ?: '00:00',
                'tar_fin' => $tarFin ?: '00:00',
            ];
        }

        if (!$hayDatos) continue;

        if ($run === '') $avisos[] = 'RUN obligatorio pendiente.';
        if ($nombre === '') $avisos[] = 'Nombre obligatorio pendiente.';
        if ($apPaterno === '') $avisos[] = 'Apellido paterno obligatorio pendiente.';

        $rows[] = [
            'fila_excel' => $r,
            'run' => $run,
            'nombre_completo' => trim($nombre . ' ' . $apPaterno . ' ' . $apMaterno),
            'colegio' => $colegioNombre,
            'jornada_min' => $totalMin,
            'jornada_hhmm' => minutosAHHMM($totalMin),
            'colacion_min' => $minColacion,
            'lectivas_hhmm' => '00:00',
            'no_lectivas_hhmm' => '00:00',
            'observacion' => $observacion,
            'horario' => $horario,
            'avisos' => $avisos,
        ];
    }

    return $rows;
}

$idColegioSesion = (int)($_SESSION["id_colegio"] ?? 0);
$colegioNombreSesion = carga_nombre_colegio_sesion($funciones, $idColegioSesion);
$previewRows = [];
$uploadError = '';
$uploadName = '';
$totalFuncionariosCargados = 0;
$funcionariosCumplen40 = 0;
$funcionariosSobre40 = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_carga'])) {
    $archivo = $_FILES['archivo_carga'];
    $uploadName = (string)($archivo['name'] ?? '');

    if ((int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $uploadError = 'No se pudo cargar el archivo. Revisa que hayas seleccionado un .xlsx válido.';
    } else {
        $extension = strtolower(pathinfo($uploadName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            $uploadError = 'El archivo debe ser Excel (.xlsx o .xls).';
        } else {
            try {
                $previewRows = carga_parse_excel((string)$archivo['tmp_name'], $funciones, $colegioNombreSesion);
                if (count($previewRows) === 0) {
                    $uploadError = 'No se encontraron filas con datos en la plantilla.';
                }
            } catch (Throwable $e) {
                $uploadError = 'No se pudo leer el Excel. Confirma que sea la plantilla descargada desde el sistema.';
            }
        }
    }
}

$totalFuncionariosCargados = count($previewRows);
foreach ($previewRows as $row) {
    $jornadaMin = (int)($row['jornada_min'] ?? 0);
    if ($jornadaMin > 2400) {
        $funcionariosSobre40++;
    } else {
        $funcionariosCumplen40++;
    }
}

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
        .carga-actions{
            display:flex;
            align-items:center;
            justify-content:flex-end;
            gap:10px;
            flex-wrap:wrap;
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
        .carga-observacion-action{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            min-height:44px;
            padding:0 16px;
            border-radius:12px;
            border:1px solid rgba(0,111,173,.24);
            background:#fff;
            color:var(--inst-blue);
            font-weight:900;
            cursor:pointer;
            white-space:nowrap;
        }
        .carga-observacion-action:hover{
            background:#f8fafc;
        }
        .carga-observacion-action .count{
            min-width:22px;
            height:22px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border-radius:999px;
            background:rgba(0,111,173,.10);
            color:var(--inst-blue);
            font-size:12px;
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
        .upload-panel{
            padding:16px;
        }
        .upload-form{
            display:grid;
            grid-template-columns:minmax(0,1fr) auto;
            gap:12px;
            align-items:end;
        }
        .upload-field{
            display:grid;
            gap:6px;
        }
        .upload-field span{
            color:#334155;
            font-size:13px;
            font-weight:900;
        }
        .upload-field input{
            width:100%;
            min-height:44px;
            border:1px solid rgba(15,23,42,.14);
            border-radius:10px;
            background:#fff;
            padding:9px 10px;
        }
        .upload-submit{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            min-height:44px;
            padding:0 16px;
            border:1px solid rgba(0,111,173,.28);
            border-radius:10px;
            background:var(--inst-blue);
            color:#fff;
            font-weight:900;
            cursor:pointer;
        }
        .upload-alert{
            margin-top:12px;
            padding:10px 12px;
            border-radius:10px;
            border:1px solid rgba(220,38,38,.24);
            background:rgba(254,242,242,.92);
            color:#991b1b;
            font-size:13px;
            font-weight:800;
        }
        .carga-summary{
            display:grid;
            grid-template-columns:repeat(3, minmax(0, 1fr));
            gap:12px;
        }
        .summary-item{
            display:grid;
            grid-template-columns:42px minmax(0,1fr);
            gap:12px;
            align-items:center;
            padding:14px;
            border:1px solid rgba(15,23,42,.10);
            border-radius:12px;
            background:#fff;
        }
        .summary-item i{
            width:42px;
            height:42px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius:12px;
            background:rgba(0,111,173,.10);
            color:var(--inst-blue);
            font-size:18px;
        }
        .summary-item.warning i{
            background:rgba(245,158,11,.14);
            color:#b45309;
        }
        .summary-item strong{
            display:block;
            color:#0f172a;
            font-size:24px;
            font-weight:900;
            line-height:1;
        }
        .summary-item span{
            display:block;
            margin-top:5px;
            color:#64748b;
            font-size:12px;
            font-weight:900;
            line-height:1.35;
        }
        .preview-card{
            overflow:hidden;
        }
        .preview-head{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            padding:14px 16px;
            border-bottom:1px solid rgba(15,23,42,.08);
        }
        .preview-head-actions{
            display:flex;
            align-items:center;
            justify-content:flex-end;
            gap:10px;
            flex-wrap:wrap;
        }
        .preview-title{
            display:flex;
            align-items:center;
            gap:8px;
            margin:0;
            font-size:17px;
            font-weight:900;
        }
        .preview-title::before{
            content:"";
            width:12px;
            height:12px;
            border-radius:999px;
            background:#0ea5e9;
            box-shadow:0 0 0 5px rgba(14,165,233,.12);
        }
        .preview-meta{
            color:#64748b;
            font-size:12px;
            font-weight:800;
        }
        .preview-scroll{
            overflow:auto;
            max-height:560px;
        }
        .preview-table{
            width:100%;
            border-collapse:separate;
            border-spacing:0;
            min-width:1180px;
            font-size:13px;
        }
        .preview-table th,
        .preview-table td{
            padding:10px 12px;
            border-bottom:1px solid rgba(15,23,42,.08);
            vertical-align:middle;
        }
        .preview-table thead th{
            position:sticky;
            top:0;
            z-index:2;
            background:#fff;
            color:#020617;
            text-align:left;
            font-size:12px;
            text-transform:uppercase;
            letter-spacing:.02em;
        }
        .preview-table tbody tr:nth-child(4n+1){
            background:#fff;
        }
        .preview-table tbody tr:nth-child(4n+3){
            background:#eef4ff;
        }
        .preview-num,
        .preview-value{
            color:#005A9C;
            font-weight:900;
            white-space:nowrap;
        }
        .preview-name{
            font-weight:900;
            color:#1f2937;
        }
        .preview-check{
            width:44px;
            text-align:center;
        }
        .preview-check input{
            width:18px;
            height:18px;
            accent-color:var(--inst-blue);
            cursor:pointer;
        }
        .preview-colegio{
            color:#334155;
            white-space:nowrap;
        }
        .preview-import-btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            min-height:38px;
            padding:0 14px;
            border:1px solid rgba(29,111,66,.28);
            border-radius:10px;
            background:#1d6f42;
            color:#fff;
            font-weight:900;
            cursor:pointer;
            white-space:nowrap;
        }
        .preview-import-btn:disabled{
            opacity:.55;
            cursor:not-allowed;
        }
        .preview-actions{
            display:flex;
            gap:6px;
            align-items:center;
        }
        .preview-btn{
            width:40px;
            height:40px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border:1px solid rgba(15,23,42,.14);
            border-radius:9px;
            background:#fff;
            color:#005A9C;
            cursor:pointer;
        }
        .preview-btn:hover{
            background:#f8fafc;
        }
        .preview-btn.has-observation{
            color:#7c3aed;
            border-color:rgba(124,58,237,.24);
            background:rgba(124,58,237,.06);
        }
        .preview-btn.has-observation:hover{
            background:rgba(124,58,237,.10);
        }
        .schedule-row{
            display:none;
        }
        .schedule-row.is-open{
            display:table-row;
        }
        .schedule-row > td{
            padding:0;
            background:#fff;
        }
        .schedule-panel{
            border-top:3px solid var(--inst-blue);
            overflow:auto;
        }
        .schedule-table{
            width:100%;
            min-width:980px;
            border-collapse:collapse;
            font-size:13px;
        }
        .schedule-table th,
        .schedule-table td{
            border-bottom:1px solid rgba(15,23,42,.10);
            padding:10px 12px;
            text-align:center;
        }
        .schedule-table thead th{
            background:#eef2f7;
            font-weight:900;
        }
        .schedule-day{
            display:inline-flex;
            align-items:center;
            gap:8px;
            min-width:112px;
            justify-content:center;
            border:1px solid rgba(15,23,42,.14);
            border-radius:999px;
            padding:8px 12px;
            font-weight:900;
            background:#fff;
        }
        .schedule-day i{
            color:#64748b;
        }
        .preview-time{
            display:inline-flex;
            align-items:center;
            gap:6px;
            border:1px solid rgba(15,23,42,.14);
            border-radius:12px;
            padding:5px 8px;
            background:#fff;
        }
        .preview-time select{
            width:68px;
            min-height:34px;
            border:1px solid rgba(15,23,42,.16);
            border-radius:8px;
            background:#fff;
            color:#020617;
            text-align:center;
            opacity:1;
        }
        .preview-warnings{
            display:grid;
            gap:4px;
            color:#92400e;
            font-size:12px;
            font-weight:800;
        }
        .observacion-backdrop{
            position:fixed;
            inset:0;
            z-index:1100;
            background:rgba(15,23,42,.38);
            opacity:0;
            pointer-events:none;
            transition:opacity .18s ease;
        }
        .observacion-backdrop.is-open{
            opacity:1;
            pointer-events:auto;
        }
        .observacion-offcanvas{
            position:fixed;
            top:0;
            right:0;
            z-index:1110;
            width:min(980px, 100vw);
            height:100vh;
            display:grid;
            grid-template-rows:auto minmax(0,1fr);
            background:#f8fafc;
            box-shadow:-18px 0 36px rgba(15,23,42,.18);
            transform:translateX(100%);
            transition:transform .22s ease;
        }
        .observacion-offcanvas.is-open{
            transform:translateX(0);
        }
        .observacion-head{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:12px;
            padding:18px;
            border-bottom:1px solid rgba(15,23,42,.10);
        }
        .observacion-head h2{
            margin:0;
            color:#0f172a;
            font-size:18px;
            font-weight:900;
        }
        .observacion-head small{
            display:block;
            margin-top:4px;
            color:#64748b;
            font-size:12px;
            font-weight:700;
        }
        .observacion-close{
            width:38px;
            height:38px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border:1px solid rgba(15,23,42,.14);
            border-radius:10px;
            background:#fff;
            color:#0f172a;
            cursor:pointer;
        }
        .observacion-body{
            overflow:auto;
            padding:14px 18px 18px;
        }
        .observacion-body .carga-grid{
            grid-template-columns:minmax(0,1.2fr) minmax(320px,.8fr);
        }
        .observacion-list{
            display:grid;
            gap:10px;
        }
        .observacion-item{
            display:grid;
            gap:7px;
            padding:12px;
            border:1px solid rgba(15,23,42,.10);
            border-radius:12px;
            background:#fbfbfb;
        }
        .observacion-item strong{
            color:#0f172a;
            font-size:13px;
            font-weight:900;
        }
        .observacion-item span{
            color:#64748b;
            font-size:12px;
            font-weight:800;
        }
        .observacion-item p{
            margin:0;
            color:#1f2937;
            font-size:13px;
            line-height:1.5;
            white-space:pre-wrap;
            word-break:break-word;
        }
        .observacion-empty{
            display:grid;
            place-items:center;
            min-height:240px;
            padding:18px;
            border:1px dashed rgba(15,23,42,.20);
            border-radius:12px;
            color:#64748b;
            text-align:center;
            font-size:13px;
            line-height:1.5;
        }
        .excel-observation-backdrop{
            position:fixed;
            inset:0;
            z-index:1200;
            display:none;
            align-items:center;
            justify-content:center;
            padding:18px;
            background:rgba(15,23,42,.42);
        }
        .excel-observation-backdrop.is-open{
            display:flex;
        }
        .excel-observation-modal{
            width:min(560px, 100%);
            max-height:calc(100vh - 36px);
            display:grid;
            grid-template-rows:auto minmax(0,1fr) auto;
            overflow:hidden;
            border-radius:14px;
            background:#fff;
            box-shadow:0 24px 60px rgba(15,23,42,.24);
        }
        .excel-observation-head{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:12px;
            padding:18px;
            border-bottom:1px solid rgba(15,23,42,.10);
        }
        .excel-observation-head h2{
            margin:0;
            color:#0f172a;
            font-size:18px;
            font-weight:900;
        }
        .excel-observation-head small{
            display:block;
            margin-top:4px;
            color:#64748b;
            font-size:12px;
            font-weight:800;
        }
        .excel-observation-close{
            width:38px;
            height:38px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border:1px solid rgba(15,23,42,.14);
            border-radius:10px;
            background:#fff;
            color:#0f172a;
            cursor:pointer;
        }
        .excel-observation-body{
            overflow:auto;
            padding:18px;
        }
        .excel-observation-content{
            min-height:140px;
            padding:14px;
            border:1px solid rgba(15,23,42,.12);
            border-radius:12px;
            background:#f8fafc;
            color:#1f2937;
            font-size:14px;
            line-height:1.55;
            white-space:pre-wrap;
            word-break:break-word;
        }
        .excel-observation-content.is-empty{
            display:flex;
            align-items:center;
            justify-content:center;
            color:#64748b;
            text-align:center;
        }
        .excel-observation-foot{
            display:flex;
            justify-content:flex-end;
            padding:12px 18px 18px;
        }
        .excel-observation-foot button{
            min-height:40px;
            padding:0 16px;
            border:1px solid rgba(0,111,173,.28);
            border-radius:10px;
            background:var(--inst-blue);
            color:#fff;
            font-weight:900;
            cursor:pointer;
        }
        @media (max-width: 980px){
            .carga-hero,
            .carga-grid,
            .observacion-body .carga-grid,
            .carga-summary,
            .upload-form{
                grid-template-columns:1fr;
            }
            .carga-action,
            .carga-observacion-action,
            .upload-submit{
                width:100%;
            }
            .carga-actions{
                justify-content:stretch;
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
                <!-- <p class="carga-subtitle">
                    Descarga la plantilla, completa una fila por funcionario y conserva los encabezados tal como vienen.
                    La plantilla incluye los datos base y los horarios de lunes a viernes. Los totales se calculan en el sistema.
                </p> -->
            </div>
            <div class="carga-actions">
                <a class="carga-action" href="descarga/plantilla_carga_masiva.php">
                    <i class="bi bi-file-earmark-excel-fill"></i>
                    Descargar plantilla Excel
                </a>
                <button class="carga-observacion-action js-open-observaciones" type="button" aria-controls="observacionesOffcanvas">
                    <i class="bi bi-info-circle"></i>
                  
                </button>
            </div>
        </section>

        <section class="card upload-panel">
            <div class="card-head" style="margin:-16px -16px 14px;">
                <div>
                    <h2>Adjuntar archivo completado</h2>
                    <small>Lee la plantilla, calcula la jornada cronológica y prepara la previsualización.</small>
                </div>
            </div>
            <form class="upload-form" method="post" enctype="multipart/form-data">
                <label class="upload-field">
                    <span>Archivo Excel</span>
                    <input type="file" name="archivo_carga" accept=".xlsx,.xls" required>
                </label>
                <button class="upload-submit" type="submit">
                    <i class="bi bi-upload"></i>
                    Cargar y revisar
                </button>
            </form>
            <?php if ($uploadError !== ''): ?>
                <div class="upload-alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?= htmlspecialchars($uploadError) ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if (count($previewRows) > 0): ?>
            <section class="carga-summary" aria-label="Resumen de funcionarios cargados">
                <div class="summary-item">
                    <i class="bi bi-people-fill"></i>
                    <div>
                        <strong><?= (int)$totalFuncionariosCargados ?></strong>
                        <span>Funcionarios cargados</span>
                    </div>
                </div>
                <div class="summary-item">
                    <i class="bi bi-check2-circle"></i>
                    <div>
                        <strong><?= (int)$funcionariosCumplen40 ?></strong>
                        <span>Cumplen hasta 40 horas legales</span>
                    </div>
                </div>
                <div class="summary-item warning">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <strong><?= (int)$funcionariosSobre40 ?></strong>
                        <span>Sobre las 40 horas legales</span>
                    </div>
                </div>
            </section>

            <section class="card preview-card">
                <div class="preview-head">
                    <h2 class="preview-title">Funcionarios</h2>
                    <div class="preview-head-actions">
                        <div class="preview-meta">
                            <span id="selectedFuncionariosCount">0 seleccionados</span> ·
                            <?= count($previewRows) ?> fila<?= count($previewRows) === 1 ? '' : 's' ?> leída<?= count($previewRows) === 1 ? '' : 's' ?>
                            <?= $uploadName !== '' ? ' desde ' . htmlspecialchars($uploadName) : '' ?>
                        </div>
                        <button class="preview-import-btn" id="openImportConfirmBtn" type="button" disabled>
                            <i class="bi bi-cloud-upload"></i>
                            Cargar seleccionados
                        </button>
                    </div>
                </div>
                <div class="preview-scroll">
                    <table class="preview-table" aria-label="Previsualización de carga masiva">
                        <thead>
                            <tr>
                                <th class="preview-check">
                                    <input id="selectAllFuncionarios" type="checkbox" aria-label="Seleccionar todos los funcionarios">
                                </th>
                                <th>N°</th>
                                <th>RUN</th>
                                <th>Nombre - Apellidos</th>
                                <th>Colegio</th>
                                <th>Jornada ordinaria</th>
                                <th>Colación</th>
                                <th>Horas no lectivas</th>
                                <th>Horas lectivas</th>
                                <th>Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($previewRows as $idx => $row): ?>
                                <?php $targetId = 'schedulePreview' . $idx; ?>
                                <tr>
                                    <td class="preview-check">
                                        <input
                                            class="js-funcionario-check"
                                            type="checkbox"
                                            value="<?= (int)$idx ?>"
                                            aria-label="Seleccionar <?= htmlspecialchars($row['nombre_completo'] ?: 'funcionario', ENT_QUOTES) ?>"
                                        >
                                    </td>
                                    <td class="preview-num"><?= (int)($idx + 1) ?></td>
                                    <td><?= htmlspecialchars($row['run']) ?></td>
                                    <td class="preview-name"><?= htmlspecialchars($row['nombre_completo'] ?: 'Sin nombre') ?></td>
                                    <td class="preview-colegio">
                                        <i class="bi bi-building"></i>
                                        <?= htmlspecialchars($row['colegio']) ?>
                                    </td>
                                    <td class="preview-value"><?= htmlspecialchars($row['jornada_hhmm']) ?></td>
                                    <td class="preview-value"><?= (int)$row['colacion_min'] ?> min</td>
                                    <td class="preview-value"><?= htmlspecialchars($row['no_lectivas_hhmm']) ?></td>
                                    <td class="preview-value"><?= htmlspecialchars($row['lectivas_hhmm']) ?></td>
                                    <td>
                                        <div class="preview-actions">
                                            <button class="preview-btn js-toggle-schedule" type="button" data-target="<?= htmlspecialchars($targetId) ?>" title="Ver horario">
                                                <i class="bi bi-calendar-week"></i>
                                            </button>
                                            <button
                                                class="preview-btn js-show-excel-observation<?= trim((string)($row['observacion'] ?? '')) !== '' ? ' has-observation' : '' ?>"
                                                type="button"
                                                title="Ver observación"
                                                data-name="<?= htmlspecialchars($row['nombre_completo'] ?: 'Sin nombre', ENT_QUOTES) ?>"
                                                data-run="<?= htmlspecialchars((string)$row['run'], ENT_QUOTES) ?>"
                                                data-observation="<?= htmlspecialchars((string)($row['observacion'] ?? ''), ENT_QUOTES) ?>"
                                            >
                                                <i class="bi bi-chat-left-text"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr id="<?= htmlspecialchars($targetId) ?>" class="schedule-row">
                                    <td colspan="10">
                                        <div class="schedule-panel">
                                            <table class="schedule-table" aria-label="Horario de <?= htmlspecialchars($row['nombre_completo'] ?: 'funcionario') ?>">
                                                <thead>
                                                    <tr>
                                                        <th rowspan="2">Día</th>
                                                        <th colspan="2">Mañana</th>
                                                        <th colspan="2">Tarde</th>
                                                    </tr>
                                                    <tr>
                                                        <th>Inicio</th>
                                                        <th>Término</th>
                                                        <th>Inicio</th>
                                                        <th>Término</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($row['horario'] as $dia): ?>
                                                        <tr>
                                                            <td>
                                                                <span class="schedule-day">
                                                                    <i class="bi bi-unlock-fill"></i>
                                                                    <?= htmlspecialchars($dia['dia']) ?>
                                                                </span>
                                                            </td>
                                                            <td><?= carga_select_hora_html($dia['man_ini']) ?></td>
                                                            <td><?= carga_select_hora_html($dia['man_fin']) ?></td>
                                                            <td><?= carga_select_hora_html($dia['tar_ini']) ?></td>
                                                            <td><?= carga_select_hora_html($dia['tar_fin']) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <?php if (count($row['avisos']) > 0): ?>
                                                        <tr>
                                                            <td colspan="5">
                                                                <div class="preview-warnings">
                                                                    <?php foreach ($row['avisos'] as $aviso): ?>
                                                                        <span><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($aviso) ?></span>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>

<div class="observacion-backdrop js-close-observaciones" data-observaciones-backdrop></div>
<aside class="observacion-offcanvas" id="observacionesOffcanvas" aria-hidden="true" aria-labelledby="observacionesTitle">
    <div class="observacion-head">
        <div>
            <h2 id="observacionesTitle">Formato de la plantilla</h2>
            <small>Columnas y preparación antes de importar.</small>
        </div>
        <button class="observacion-close js-close-observaciones" type="button" aria-label="Cerrar formato de plantilla">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="observacion-body">
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
    </div>
</aside>

<div class="excel-observation-backdrop" id="excelObservationModal" aria-hidden="true">
    <section class="excel-observation-modal" role="dialog" aria-modal="true" aria-labelledby="excelObservationTitle">
        <div class="excel-observation-head">
            <div>
                <h2 id="excelObservationTitle">Observación</h2>
                <small id="excelObservationMeta">Funcionario</small>
            </div>
            <button class="excel-observation-close js-close-excel-observation" type="button" aria-label="Cerrar observación">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="excel-observation-body">
            <div class="excel-observation-content" id="excelObservationContent"></div>
        </div>
        <div class="excel-observation-foot">
            <button class="js-close-excel-observation" type="button">Cerrar</button>
        </div>
    </section>
</div>

<div class="excel-observation-backdrop" id="importConfirmModal" aria-hidden="true">
    <section class="excel-observation-modal" role="dialog" aria-modal="true" aria-labelledby="importConfirmTitle">
        <div class="excel-observation-head">
            <div>
                <h2 id="importConfirmTitle">Cargar funcionarios</h2>
                <small id="importConfirmMeta">0 funcionarios seleccionados</small>
            </div>
            <button class="excel-observation-close js-close-import-confirm" type="button" aria-label="Cancelar carga de funcionarios">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="excel-observation-body">
            <div class="excel-observation-content">
                Confirma si deseas cargar los funcionarios seleccionados. El guardado en la base de datos se agregará en el siguiente paso.
            </div>
        </div>
        <div class="excel-observation-foot">
            <button class="js-close-import-confirm" type="button" style="background:#fff;color:#0f172a;border-color:rgba(15,23,42,.16);margin-right:8px;">Cancelar</button>
            <button id="confirmImportBtn" type="button">Cargar funcionarios</button>
        </div>
    </section>
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

document.querySelectorAll(".js-toggle-schedule").forEach((button) => {
    button.addEventListener("click", () => {
        const target = document.getElementById(button.dataset.target || "");
        if (!target) return;

        const isOpen = target.classList.toggle("is-open");
        const icon = button.querySelector("i");
        if (icon) {
            icon.className = isOpen ? "bi bi-calendar-x" : "bi bi-calendar-week";
        }
    });
});

const observacionesOffcanvas = document.getElementById("observacionesOffcanvas");
const observacionesBackdrop = document.querySelector("[data-observaciones-backdrop]");

function setObservacionesOpen(isOpen) {
    if (!observacionesOffcanvas || !observacionesBackdrop) return;
    observacionesOffcanvas.classList.toggle("is-open", isOpen);
    observacionesBackdrop.classList.toggle("is-open", isOpen);
    observacionesOffcanvas.setAttribute("aria-hidden", isOpen ? "false" : "true");
    document.body.style.overflow = isOpen ? "hidden" : "";
}

document.querySelectorAll(".js-open-observaciones").forEach((button) => {
    button.addEventListener("click", () => setObservacionesOpen(true));
});

document.querySelectorAll(".js-close-observaciones").forEach((button) => {
    button.addEventListener("click", () => setObservacionesOpen(false));
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
        setObservacionesOpen(false);
        setExcelObservationOpen(false);
        setImportConfirmOpen(false);
    }
});

const excelObservationModal = document.getElementById("excelObservationModal");
const excelObservationTitle = document.getElementById("excelObservationTitle");
const excelObservationMeta = document.getElementById("excelObservationMeta");
const excelObservationContent = document.getElementById("excelObservationContent");

function setExcelObservationOpen(isOpen) {
    if (!excelObservationModal) return;
    excelObservationModal.classList.toggle("is-open", isOpen);
    excelObservationModal.setAttribute("aria-hidden", isOpen ? "false" : "true");
    document.body.style.overflow = isOpen ? "hidden" : "";
}

document.querySelectorAll(".js-show-excel-observation").forEach((button) => {
    button.addEventListener("click", () => {
        const name = button.dataset.name || "Funcionario";
        const run = button.dataset.run || "";
        const observation = (button.dataset.observation || "").trim();

        if (excelObservationTitle) {
            excelObservationTitle.textContent = name;
        }
        if (excelObservationMeta) {
            excelObservationMeta.textContent = run ? `RUN ${run}` : "Observación del Excel";
        }
        if (excelObservationContent) {
            excelObservationContent.textContent = observation || "Sin observación registrada en la columna G del Excel.";
            excelObservationContent.classList.toggle("is-empty", !observation);
        }

        setExcelObservationOpen(true);
    });
});

document.querySelectorAll(".js-close-excel-observation").forEach((button) => {
    button.addEventListener("click", () => setExcelObservationOpen(false));
});

if (excelObservationModal) {
    excelObservationModal.addEventListener("click", (event) => {
        if (event.target === excelObservationModal) {
            setExcelObservationOpen(false);
        }
    });
}

const selectAllFuncionarios = document.getElementById("selectAllFuncionarios");
const funcionarioChecks = Array.from(document.querySelectorAll(".js-funcionario-check"));
const selectedFuncionariosCount = document.getElementById("selectedFuncionariosCount");
const openImportConfirmBtn = document.getElementById("openImportConfirmBtn");
const importConfirmModal = document.getElementById("importConfirmModal");
const importConfirmMeta = document.getElementById("importConfirmMeta");
const confirmImportBtn = document.getElementById("confirmImportBtn");

function getSelectedFuncionariosCount() {
    return funcionarioChecks.filter((check) => check.checked).length;
}

function updateFuncionarioSelectionState() {
    const selected = getSelectedFuncionariosCount();
    const total = funcionarioChecks.length;

    if (selectedFuncionariosCount) {
        selectedFuncionariosCount.textContent = `${selected} seleccionado${selected === 1 ? "" : "s"}`;
    }
    if (openImportConfirmBtn) {
        openImportConfirmBtn.disabled = selected === 0;
    }
    if (selectAllFuncionarios) {
        selectAllFuncionarios.checked = total > 0 && selected === total;
        selectAllFuncionarios.indeterminate = selected > 0 && selected < total;
    }
}

function setImportConfirmOpen(isOpen) {
    if (!importConfirmModal) return;
    if (isOpen && getSelectedFuncionariosCount() === 0) return;

    const selected = getSelectedFuncionariosCount();
    if (importConfirmMeta) {
        importConfirmMeta.textContent = `${selected} funcionario${selected === 1 ? "" : "s"} seleccionado${selected === 1 ? "" : "s"}`;
    }

    importConfirmModal.classList.toggle("is-open", isOpen);
    importConfirmModal.setAttribute("aria-hidden", isOpen ? "false" : "true");
    document.body.style.overflow = isOpen ? "hidden" : "";
}

if (selectAllFuncionarios) {
    selectAllFuncionarios.addEventListener("change", () => {
        funcionarioChecks.forEach((check) => {
            check.checked = selectAllFuncionarios.checked;
        });
        updateFuncionarioSelectionState();
    });
}

funcionarioChecks.forEach((check) => {
    check.addEventListener("change", updateFuncionarioSelectionState);
});

if (openImportConfirmBtn) {
    openImportConfirmBtn.addEventListener("click", () => setImportConfirmOpen(true));
}

document.querySelectorAll(".js-close-import-confirm").forEach((button) => {
    button.addEventListener("click", () => setImportConfirmOpen(false));
});

if (confirmImportBtn) {
    confirmImportBtn.addEventListener("click", () => setImportConfirmOpen(false));
}

if (importConfirmModal) {
    importConfirmModal.addEventListener("click", (event) => {
        if (event.target === importConfirmModal) {
            setImportConfirmOpen(false);
        }
    });
}

updateFuncionarioSelectionState();
</script>
</body>
</html>
