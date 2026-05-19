<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit;
}

ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(0);

require_once __DIR__ . "/../class/conexion.php";
require_once __DIR__ . "/../class/funciones.php";
require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

function minutosAHoras($valor) {
    if ($valor === '' || $valor === null) return '-';
    $valor = trim((string)$valor);

    if (strpos($valor, ':') !== false) {
        $p = explode(':', $valor);
        $hh = str_pad((string)((int)($p[0] ?? 0)), 2, '0', STR_PAD_LEFT);
        $mm = str_pad((string)((int)($p[1] ?? 0)), 2, '0', STR_PAD_LEFT);
        return $hh . ':' . $mm;
    }

    if (ctype_digit($valor)) {
        $min = (int)$valor;
        $h = intdiv($min, 60);
        $m = $min % 60;
        return str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string)$m, 2, '0', STR_PAD_LEFT);
    }

    return $valor;
}

function fmtHora($h) {
    if (!$h) return '-';
    return substr((string)$h, 0, 5);
}

function fmtHoraBux($h) {
    if (!$h) return '';
    $hora = substr((string)$h, 0, 5);
    return $hora === '00:00' ? '' : $hora;
}

function minutosAPedagogicas($minutos) {
    $valor = round(max(0, (int)$minutos) / 40, 2);
    return fmod($valor, 1.0) === 0.0 ? (int)$valor : $valor;
}

function minutosAHorasDecimales($valor) {
    if ($valor === '' || $valor === null) return '';

    $minutos = 0;
    $valor = trim((string)$valor);

    if (strpos($valor, ':') !== false) {
        $p = explode(':', $valor);
        $horas = (int)($p[0] ?? 0);
        $mins = (int)($p[1] ?? 0);
        $minutos = ($horas * 60) + $mins;
    } elseif (ctype_digit($valor)) {
        $minutos = (int)$valor;
    } else {
        return '';
    }

    return round(max(0, $minutos) / 60, 2);
}

function slugArchivo($texto) {
    $texto = trim((string)$texto);
    if ($texto === '') return '';

    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    if ($ascii !== false) {
        $texto = $ascii;
    }

    $texto = strtolower($texto);
    $texto = preg_replace('/[^a-z0-9]+/', '_', $texto);
    return trim((string)$texto, '_');
}

function styleHeader($sheet, $range) {
    $sheet->getStyle($range)->applyFromArray([
        'font' => ['bold' => true],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'EAEAEA']
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'DDDDDD']
            ]
        ],
    ]);
}

function styleTableBorders($sheet, $range) {
    $sheet->getStyle($range)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'DDDDDD']
            ]
        ],
    ]);
}

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");
$funciones = new Funciones($db);

$idUsuarioSesion = (int)($_SESSION["id_usuario"] ?? 0);
$idColegioSesion = (int)($_SESSION["id_colegio"] ?? 0);
$esSuperAdminOperativo = $funciones->usuarioTieneRol($idUsuarioSesion, 1);
$esColegioGlobalSesion = ($idColegioSesion === 15);
$verTodosColegios = $esSuperAdminOperativo || $esColegioGlobalSesion;
$idColegioSolicitado = isset($_GET['id_colegio']) ? (int)$_GET['id_colegio'] : 0;
$whereColegioExport = $verTodosColegios
    ? ($idColegioSolicitado > 0 ? "e.id_colegio = {$idColegioSolicitado}" : "1=1")
    : "e.id_colegio = {$idColegioSesion}";

$idColegioNombreArchivo = $verTodosColegios
    ? $idColegioSolicitado
    : $idColegioSesion;
$nombreColegioArchivo = 'todos_los_colegios';
$nombreColegioExportado = 'Todos los colegios';

if ($idColegioNombreArchivo > 0) {
    $resColegioArchivo = $db->consulta("
        SELECT COALESCE(NULLIF(nco_colegio, ''), nom_colegio) AS nombre_colegio
        FROM colegio
        WHERE id_colegio = {$idColegioNombreArchivo}
        LIMIT 1
    ");
    $rowColegioArchivo = $db->fetch_assoc($resColegioArchivo);
    $nombreColegioExportado = trim((string)($rowColegioArchivo['nombre_colegio'] ?? '')) ?: 'Colegio';
    $slugColegio = slugArchivo($rowColegioArchivo['nombre_colegio'] ?? '');
    if ($slugColegio !== '') {
        $nombreColegioArchivo = $slugColegio;
    }
}

$nombreDescargador = trim((string)($_SESSION["nombre_completo"] ?? $_SESSION["identificador"] ?? 'Usuario'));
$fechaDescarga = date('d-m-Y');
$horaDescarga = date('H:i:s');
$logoColegioPath = '';
if ($idColegioNombreArchivo > 0) {
    foreach (['png', 'jpg', 'jpeg'] as $extensionLogo) {
        $rutaLogo = __DIR__ . "/../imagenes/colegios/colegio_{$idColegioNombreArchivo}.{$extensionLogo}";
        if (is_file($rutaLogo)) {
            $logoColegioPath = $rutaLogo;
            break;
        }
    }
}
if ($logoColegioPath === '') {
    $logoGeneral = __DIR__ . '/../imagenes/logo_seduc_02.png';
    if (is_file($logoGeneral)) {
        $logoColegioPath = $logoGeneral;
    }
}

/* ==========================
   HOJA 1: EMPLEADOS + CONTRATO VIGENTE
   ========================== */
$sqlEmp = "
SELECT
  e.id_empleado, e.codigo, e.run,
  e.nombres, e.apellido_paterno, e.apellido_materno,
  e.email, e.telefono, e.activo,
  e.id_colegio, co.nom_colegio,
  c.id_contrato,
  c.horas_semanales_cron,
  c.min_colacion_diaria
FROM empleados e
LEFT JOIN colegio co ON co.id_colegio = e.id_colegio
LEFT JOIN contratos_empleado c
  ON c.id_empleado = e.id_empleado
 AND c.id_contrato = (
      SELECT c2.id_contrato
      FROM contratos_empleado c2
      WHERE c2.id_empleado = e.id_empleado
      ORDER BY c2.fecha_inicio DESC, c2.id_contrato DESC
      LIMIT 1
 )
WHERE {$whereColegioExport}
ORDER BY co.nom_colegio ASC, e.apellido_paterno ASC, e.apellido_materno ASC, e.nombres ASC
";
$resEmp = $db->consulta($sqlEmp);

/* ==========================
   RESUMEN PARA HOJA INFORMACION
   ========================== */
$sqlResumen = "
SELECT
  COUNT(*) AS total_funcionarios,
  SUM(
    CASE
      WHEN COALESCE(c.horas_semanales_cron, 0) > 2400 THEN 1
      ELSE 0
    END
  ) AS funcionarios_mas_40,
  SUM(
    CASE
      WHEN COALESCE(c.horas_semanales_cron, 0) < 2400 THEN 1
      ELSE 0
    END
  ) AS funcionarios_menos_40
FROM empleados e
LEFT JOIN contratos_empleado c
  ON c.id_empleado = e.id_empleado
 AND c.id_contrato = (
      SELECT c2.id_contrato
      FROM contratos_empleado c2
      WHERE c2.id_empleado = e.id_empleado
      ORDER BY c2.fecha_inicio DESC, c2.id_contrato DESC
      LIMIT 1
 )
WHERE {$whereColegioExport}
";
$resResumen = $db->consulta($sqlResumen);
$resumen = $db->fetch_assoc($resResumen) ?: [];
$totalFuncionarios = (int)($resumen['total_funcionarios'] ?? 0);
$funcionariosMas40 = (int)($resumen['funcionarios_mas_40'] ?? 0);
$funcionariosMenos40 = (int)($resumen['funcionarios_menos_40'] ?? 0);

/* ==========================
   HOJA 2: HORARIOS (por día por empleado)
   ========================== */
$sqlHor = "
SELECT
  e.id_empleado,
  CONCAT(e.nombres,' ',e.apellido_paterno,' ',e.apellido_materno) AS empleado,
  e.run,
  co.nom_colegio,
  c.id_contrato,
  d.orden,
  d.nombre AS dia_nombre,
  hs.man_ini, hs.man_fin,
  hs.tar_ini, hs.tar_fin
FROM empleados e
LEFT JOIN colegio co ON co.id_colegio = e.id_colegio
LEFT JOIN contratos_empleado c
  ON c.id_empleado = e.id_empleado
 AND c.id_contrato = (
      SELECT c2.id_contrato
      FROM contratos_empleado c2
      WHERE c2.id_empleado = e.id_empleado
      ORDER BY c2.fecha_inicio DESC, c2.id_contrato DESC
      LIMIT 1
 )
JOIN dias_semana d ON d.orden BETWEEN 1 AND 5
LEFT JOIN horarios_semanales hs
  ON hs.id_contrato = c.id_contrato
 AND (
      UPPER(TRIM(hs.dia)) = UPPER(TRIM(d.prefijo))
   OR UPPER(TRIM(hs.dia)) = UPPER(TRIM(d.clave))
   OR UPPER(TRIM(hs.dia)) = UPPER(LEFT(TRIM(d.clave), 3))
   OR UPPER(LEFT(TRIM(hs.dia), 3)) = UPPER(LEFT(TRIM(d.prefijo), 3))
   OR UPPER(LEFT(TRIM(hs.dia), 3)) = UPPER(LEFT(TRIM(d.clave), 3))
 )
WHERE {$whereColegioExport}
ORDER BY co.nom_colegio, empleado, d.orden
";
$resHor = $db->consulta($sqlHor);

/* ==========================
   HOJA 3: DESCARGA BUX (formato Trabajo)
   ========================== */
$sqlBux = "
SELECT
  e.id_empleado,
  e.codigo,
  e.run,
  CONCAT(e.nombres,' ',e.apellido_paterno,' ',e.apellido_materno) AS empleado,
  c.id_contrato,
  c.horas_semanales_cron,
  c.horas_lectivas,
  c.horas_no_lectivas,
  c.min_colacion_diaria,
  d.orden,
  hs.man_ini,
  hs.man_fin,
  hs.tar_ini,
  hs.tar_fin
FROM empleados e
LEFT JOIN contratos_empleado c
  ON c.id_empleado = e.id_empleado
 AND c.id_contrato = (
      SELECT c2.id_contrato
      FROM contratos_empleado c2
      WHERE c2.id_empleado = e.id_empleado
      ORDER BY c2.fecha_inicio DESC, c2.id_contrato DESC
      LIMIT 1
 )
JOIN dias_semana d ON d.orden BETWEEN 1 AND 5
LEFT JOIN horarios_semanales hs
  ON hs.id_contrato = c.id_contrato
 AND hs.activo = 1
 AND (
      UPPER(TRIM(hs.dia)) = UPPER(TRIM(d.prefijo))
   OR UPPER(TRIM(hs.dia)) = UPPER(TRIM(d.clave))
   OR UPPER(TRIM(hs.dia)) = UPPER(LEFT(TRIM(d.clave), 3))
   OR UPPER(LEFT(TRIM(hs.dia), 3)) = UPPER(LEFT(TRIM(d.prefijo), 3))
   OR UPPER(LEFT(TRIM(hs.dia), 3)) = UPPER(LEFT(TRIM(d.clave), 3))
 )
WHERE {$whereColegioExport}
ORDER BY empleado ASC, d.orden ASC
";
$resBux = $db->consulta($sqlBux);
$datosBux = [];
while ($row = $db->fetch_assoc($resBux)) {
    $idEmpleado = (int)($row['id_empleado'] ?? 0);
    if ($idEmpleado <= 0) {
        continue;
    }

    if (!isset($datosBux[$idEmpleado])) {
        $datosBux[$idEmpleado] = [
            'run' => (string)($row['run'] ?? ''),
            'codigo' => (string)($row['codigo'] ?? ''),
            'empleado' => (string)($row['empleado'] ?? ''),
            'horas_semanales_cron' => $row['horas_semanales_cron'] ?? '',
            'horas_lectivas' => $row['horas_lectivas'] ?? 0,
            'horas_no_lectivas' => $row['horas_no_lectivas'] ?? 0,
            'min_colacion_diaria' => $row['min_colacion_diaria'] ?? '',
            'dias' => []
        ];
    }

    $ordenDia = (int)($row['orden'] ?? 0);
    if ($ordenDia >= 1 && $ordenDia <= 5) {
        $datosBux[$idEmpleado]['dias'][$ordenDia] = [
            'man_ini' => $row['man_ini'] ?? null,
            'man_fin' => $row['man_fin'] ?? null,
            'tar_ini' => $row['tar_ini'] ?? null,
            'tar_fin' => $row['tar_fin'] ?? null
        ];
    }
}

/* ==========================
   EXCEL
   ========================== */
$spreadsheet = new Spreadsheet();

/* ---------- Hoja 1: Informacion ---------- */
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('Información');

$sheet1->mergeCells('A1:F1');
$sheet1->setCellValue('A1', 'Información del archivo');
$sheet1->getStyle('A1:F1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '0A5F92']
    ]
]);
$sheet1->getRowDimension(1)->setRowHeight(28);

$datosInformacion = [
    ['Descargado por', $nombreDescargador],
    ['Fecha de descarga', $fechaDescarga],
    ['Hora de descarga', $horaDescarga],
    ['Colegio', $nombreColegioExportado],
    ['Cantidad de funcionarios', $totalFuncionarios],
    ['Funcionarios con más de 40 horas legales', $funcionariosMas40],
    ['Funcionarios con menos de 40 horas legales', $funcionariosMenos40],
];

$filaInfo = 3;
foreach ($datosInformacion as [$etiqueta, $valor]) {
    $sheet1->setCellValue('A'.$filaInfo, $etiqueta);
    $sheet1->setCellValue('B'.$filaInfo, $valor);
    $filaInfo++;
}

$sheet1->getStyle('A3:A9')->applyFromArray([
    'font' => ['bold' => true],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'EAEAEA']
    ],
]);
styleTableBorders($sheet1, 'A3:B9');
$sheet1->getColumnDimension('A')->setWidth(42);
$sheet1->getColumnDimension('B')->setWidth(34);
$sheet1->getColumnDimension('C')->setWidth(4);
$sheet1->getColumnDimension('D')->setWidth(18);
$sheet1->getColumnDimension('E')->setWidth(18);
$sheet1->getColumnDimension('F')->setWidth(18);

if ($logoColegioPath !== '') {
    $drawing = new Drawing();
    $drawing->setName('Logo colegio');
    $drawing->setDescription('Logo colegio');
    $drawing->setPath($logoColegioPath);
    $drawing->setHeight(95);
    $drawing->setCoordinates('D3');
    $drawing->setWorksheet($sheet1);
}

$sheet1->getStyle('A1:F9')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

/* ---------- Hoja 2: Empleados ---------- */
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Empleados');

$headers1 = [
    'ID','Código','RUN','Nombres','Apellido Paterno','Apellido Materno',
    'Colegio','ID Contrato','Horas semanales (cron)','Min colación diaria',
    'Email','Teléfono','Activo'
];
$colCount1 = count($headers1);
$lastCol1 = Coordinate::stringFromColumnIndex($colCount1);

for ($i=0; $i<$colCount1; $i++) {
    $col = Coordinate::stringFromColumnIndex($i+1);
    $sheet2->setCellValue($col.'1', $headers1[$i]);
}
styleHeader($sheet2, "A1:{$lastCol1}1");
$sheet2->getRowDimension(1)->setRowHeight(20);

$r = 2;
while ($row = $db->fetch_assoc($resEmp)) {
    $sheet2->setCellValue('A'.$r, (int)$row['id_empleado']);
    $sheet2->setCellValueExplicit('B'.$r, (string)$row['codigo'], DataType::TYPE_STRING);
    $sheet2->setCellValueExplicit('C'.$r, (string)$row['run'], DataType::TYPE_STRING);

    $sheet2->setCellValue('D'.$r, (string)$row['nombres']);
    $sheet2->setCellValue('E'.$r, (string)$row['apellido_paterno']);
    $sheet2->setCellValue('F'.$r, (string)$row['apellido_materno']);

    $sheet2->setCellValue('G'.$r, (string)($row['nom_colegio'] ?? '-'));

    $idContrato = $row['id_contrato'] ?? '';
    $sheet2->setCellValueExplicit('H'.$r, (string)$idContrato, DataType::TYPE_STRING);

    $horasCron = minutosAHoras($row['horas_semanales_cron'] ?? '');
    $sheet2->setCellValue('I'.$r, $horasCron);

    $minCol = ($row['min_colacion_diaria'] ?? '') !== '' ? (string)$row['min_colacion_diaria'] : '-';
    $sheet2->setCellValue('J'.$r, $minCol);

    $sheet2->setCellValue('K'.$r, (string)($row['email'] ?? ''));
    $sheet2->setCellValue('L'.$r, (string)($row['telefono'] ?? ''));

    $activo = ((int)($row['activo'] ?? 0) === 1) ? 'Sí' : 'No';
    $sheet2->setCellValue('M'.$r, $activo);

    $r++;
}
$lastRow1 = max(1, $r-1);

$sheet2->freezePane('A2');
$sheet2->setAutoFilter("A1:{$lastCol1}1");
for ($i=1; $i<=$colCount1; $i++) $sheet2->getColumnDimensionByColumn($i)->setAutoSize(true);
$sheet2->getStyle("A2:A{$lastRow1}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet2->getStyle("B2:C{$lastRow1}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet2->getStyle("H2:J{$lastRow1}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet2->getStyle("M2:M{$lastRow1}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
styleTableBorders($sheet2, "A1:{$lastCol1}{$lastRow1}");

/* ---------- Hoja 3 ---------- */
$sheet3 = $spreadsheet->createSheet();
$sheet3->setTitle('Horarios');

$headers2 = [
    'ID Empleado','Empleado','RUN','Colegio','ID Contrato','Día',
    'Inicio (Mañana)','Término (Mañana)','Inicio (Tarde)','Término (Tarde)'
];
$colCount2 = count($headers2);
$lastCol2 = Coordinate::stringFromColumnIndex($colCount2);

for ($i=0; $i<$colCount2; $i++) {
    $col = Coordinate::stringFromColumnIndex($i+1);
    $sheet3->setCellValue($col.'1', $headers2[$i]);
}
styleHeader($sheet3, "A1:{$lastCol2}1");
$sheet3->getRowDimension(1)->setRowHeight(20);

$r = 2;
$prevContrato = null;
$prevEmpleado = null;
while ($row = $db->fetch_assoc($resHor)) {
    $currContrato = (string)($row['id_contrato'] ?? '');
    $currEmpleado = (string)($row['id_empleado'] ?? '');

    if ($r > 2 && $currEmpleado !== $prevEmpleado) {
        $r++; // fila en blanco para separar empleados
    }

    $sheet3->setCellValue('A'.$r, (int)$row['id_empleado']);
    $sheet3->setCellValue('B'.$r, (string)$row['empleado']);
    $sheet3->setCellValueExplicit('C'.$r, (string)$row['run'], DataType::TYPE_STRING);
    $sheet3->setCellValue('D'.$r, (string)($row['nom_colegio'] ?? '-'));
    $sheet3->setCellValueExplicit('E'.$r, (string)($row['id_contrato'] ?? ''), DataType::TYPE_STRING);
    $sheet3->setCellValue('F'.$r, (string)($row['dia_nombre'] ?? ''));

    $sheet3->setCellValue('G'.$r, fmtHora($row['man_ini'] ?? null));
    $sheet3->setCellValue('H'.$r, fmtHora($row['man_fin'] ?? null));
    $sheet3->setCellValue('I'.$r, fmtHora($row['tar_ini'] ?? null));
    $sheet3->setCellValue('J'.$r, fmtHora($row['tar_fin'] ?? null));

    $prevContrato = $currContrato;
    $prevEmpleado = $currEmpleado;
    $r++;
}
$lastRow2 = max(1, $r-1);

$sheet3->freezePane('A2');
$sheet3->setAutoFilter("A1:{$lastCol2}1");
for ($i=1; $i<=$colCount2; $i++) $sheet3->getColumnDimensionByColumn($i)->setAutoSize(true);
$sheet3->getStyle("A2:A{$lastRow2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet3->getStyle("C2:C{$lastRow2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet3->getStyle("E2:J{$lastRow2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
styleTableBorders($sheet3, "A1:{$lastCol2}{$lastRow2}");

/* ---------- Hoja 4 ---------- */
$sheet4 = $spreadsheet->createSheet();
$sheet4->setTitle('descarga bux');

$headers3 = [
    'Colaborador',
    'Código de Ficha',
    'Entrada AM Lunes',
    'Salida AM Lunes',
    'Entrada PM Lunes',
    'Salida PM Lunes',
    'Entrada AM Martes',
    'Salida AM Martes',
    'Entrada PM Martes',
    'Salida PM Martes',
    'Entrada AM Miércoles',
    'Salida AM Miércoles',
    'Entrada PM Miércoles',
    'Salida PM Miércoles',
    'Entrada AM Jueves',
    'Salida AM Jueves',
    'Entrada PM Jueves',
    'Salida PM Jueves',
    'Entrada AM Viernes',
    'Salida AM Viernes',
    'Entrada PM Viernes',
    'Salida PM Viernes',
    'Total Horas Cronológicas',
    'Minutos Colación',
    'Horas Pedagógicas Lectivas',
    'Horas Cronológicas Lectivas',
    'Horas Pedagógicas No Lectivas',
    'Horas Cronológicas No Lectivas'
];
$colCount3 = count($headers3);
$lastCol3 = Coordinate::stringFromColumnIndex($colCount3);

for ($i=0; $i<$colCount3; $i++) {
    $col = Coordinate::stringFromColumnIndex($i+1);
    $sheet4->setCellValue($col.'1', $headers3[$i]);
}
styleHeader($sheet4, "A1:{$lastCol3}1");
$sheet4->getStyle("A1:{$lastCol3}1")->getFill()->getStartColor()->setRGB('C0C0C0');
$sheet4->getRowDimension(1)->setRowHeight(18);

$r = 2;
foreach ($datosBux as $row) {
    $sheet4->setCellValueExplicit('A'.$r, (string)$row['run'], DataType::TYPE_STRING);
    $sheet4->setCellValueExplicit('B'.$r, 'Ingresa Manualmente', DataType::TYPE_STRING);

    $colIndex = 3;
    for ($dia=1; $dia<=5; $dia++) {
        $horarioDia = $row['dias'][$dia] ?? [];
        foreach (['man_ini', 'man_fin', 'tar_ini', 'tar_fin'] as $campoHora) {
            $col = Coordinate::stringFromColumnIndex($colIndex);
            $sheet4->setCellValue($col.$r, fmtHoraBux($horarioDia[$campoHora] ?? null));
            $colIndex++;
        }
    }

    $horasCronologicas = minutosAHoras($row['horas_semanales_cron'] ?? '');
    $sheet4->setCellValue('W'.$r, $horasCronologicas);

    $minColacion = ($row['min_colacion_diaria'] ?? '') !== '' ? (int)$row['min_colacion_diaria'] : '';
    $sheet4->setCellValue('X'.$r, $minColacion);

    $minLectivas = (int)($row['horas_lectivas'] ?? 0);
    $minNoLectivas = (int)($row['horas_no_lectivas'] ?? 0);
    $sheet4->setCellValue('Y'.$r, minutosAPedagogicas($minLectivas));
    $sheet4->setCellValue('Z'.$r, minutosAHoras($minLectivas));
    $sheet4->setCellValue('AA'.$r, minutosAPedagogicas($minNoLectivas));
    $sheet4->setCellValue('AB'.$r, minutosAHoras($minNoLectivas));

    $r++;
}
$lastRow3 = max(1, $r-1);

$sheet4->freezePane('A2');
$sheet4->setAutoFilter("A1:{$lastCol3}1");
$widths3 = [
    'A' => 15, 'B' => 19,
    'C' => 21, 'D' => 19, 'E' => 21, 'F' => 19,
    'G' => 22, 'H' => 20, 'I' => 22, 'J' => 20,
    'K' => 25, 'L' => 23, 'M' => 25, 'N' => 23,
    'O' => 22, 'P' => 20, 'Q' => 22, 'R' => 20,
    'S' => 23, 'T' => 21, 'U' => 23, 'V' => 21,
    'W' => 29, 'X' => 20, 'Y' => 33, 'Z' => 33, 'AA' => 36, 'AB' => 37
];
foreach ($widths3 as $col => $width) {
    $sheet4->getColumnDimension($col)->setWidth($width);
}
$sheet4->getStyle("A2:B{$lastRow3}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet4->getStyle("C2:AB{$lastRow3}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet4->getStyle("A1:{$lastCol3}{$lastRow3}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
styleTableBorders($sheet4, "A1:{$lastCol3}{$lastRow3}");

$spreadsheet->setActiveSheetIndex(0);

/* ==========================
   DESCARGA
   ========================== */
while (ob_get_level()) { ob_end_clean(); }

$filename = "funcionario_horarios_" . $nombreColegioArchivo . "_" . date("Y_m_d") . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
