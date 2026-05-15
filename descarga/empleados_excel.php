<?php
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(0);

require_once __DIR__ . "/../class/conexion.php";
require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

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
ORDER BY co.nom_colegio ASC, e.apellido_paterno ASC, e.apellido_materno ASC, e.nombres ASC
";
$resEmp = $db->consulta($sqlEmp);

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

/* ---------- Hoja 1 ---------- */
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('Empleados');

$headers1 = [
    'ID','Código','RUN','Nombres','Apellido Paterno','Apellido Materno',
    'Colegio','ID Contrato','Horas semanales (cron)','Min colación diaria',
    'Email','Teléfono','Activo'
];
$colCount1 = count($headers1);
$lastCol1 = Coordinate::stringFromColumnIndex($colCount1);

for ($i=0; $i<$colCount1; $i++) {
    $col = Coordinate::stringFromColumnIndex($i+1);
    $sheet1->setCellValue($col.'1', $headers1[$i]);
}
styleHeader($sheet1, "A1:{$lastCol1}1");
$sheet1->getRowDimension(1)->setRowHeight(20);

$r = 2;
while ($row = $db->fetch_assoc($resEmp)) {
    $sheet1->setCellValue('A'.$r, (int)$row['id_empleado']);
    $sheet1->setCellValueExplicit('B'.$r, (string)$row['codigo'], DataType::TYPE_STRING);
    $sheet1->setCellValueExplicit('C'.$r, (string)$row['run'], DataType::TYPE_STRING);

    $sheet1->setCellValue('D'.$r, (string)$row['nombres']);
    $sheet1->setCellValue('E'.$r, (string)$row['apellido_paterno']);
    $sheet1->setCellValue('F'.$r, (string)$row['apellido_materno']);

    $sheet1->setCellValue('G'.$r, (string)($row['nom_colegio'] ?? '-'));

    $idContrato = $row['id_contrato'] ?? '';
    $sheet1->setCellValueExplicit('H'.$r, (string)$idContrato, DataType::TYPE_STRING);

    $horasCron = minutosAHoras($row['horas_semanales_cron'] ?? '');
    $sheet1->setCellValue('I'.$r, $horasCron);

    $minCol = ($row['min_colacion_diaria'] ?? '') !== '' ? (string)$row['min_colacion_diaria'] : '-';
    $sheet1->setCellValue('J'.$r, $minCol);

    $sheet1->setCellValue('K'.$r, (string)($row['email'] ?? ''));
    $sheet1->setCellValue('L'.$r, (string)($row['telefono'] ?? ''));

    $activo = ((int)($row['activo'] ?? 0) === 1) ? 'Sí' : 'No';
    $sheet1->setCellValue('M'.$r, $activo);

    $r++;
}
$lastRow1 = max(1, $r-1);

$sheet1->freezePane('A2');
$sheet1->setAutoFilter("A1:{$lastCol1}1");
for ($i=1; $i<=$colCount1; $i++) $sheet1->getColumnDimensionByColumn($i)->setAutoSize(true);
$sheet1->getStyle("A2:A{$lastRow1}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet1->getStyle("B2:C{$lastRow1}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet1->getStyle("H2:J{$lastRow1}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet1->getStyle("M2:M{$lastRow1}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
styleTableBorders($sheet1, "A1:{$lastCol1}{$lastRow1}");

/* ---------- Hoja 2 ---------- */
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Horarios');

$headers2 = [
    'ID Empleado','Empleado','RUN','Colegio','ID Contrato','Día',
    'Inicio (Mañana)','Término (Mañana)','Inicio (Tarde)','Término (Tarde)'
];
$colCount2 = count($headers2);
$lastCol2 = Coordinate::stringFromColumnIndex($colCount2);

for ($i=0; $i<$colCount2; $i++) {
    $col = Coordinate::stringFromColumnIndex($i+1);
    $sheet2->setCellValue($col.'1', $headers2[$i]);
}
styleHeader($sheet2, "A1:{$lastCol2}1");
$sheet2->getRowDimension(1)->setRowHeight(20);

$r = 2;
$prevContrato = null;
$prevEmpleado = null;
while ($row = $db->fetch_assoc($resHor)) {
    $currContrato = (string)($row['id_contrato'] ?? '');
    $currEmpleado = (string)($row['id_empleado'] ?? '');

    if ($r > 2 && $currEmpleado !== $prevEmpleado) {
        $r++; // fila en blanco para separar empleados
    }

    $sheet2->setCellValue('A'.$r, (int)$row['id_empleado']);
    $sheet2->setCellValue('B'.$r, (string)$row['empleado']);
    $sheet2->setCellValueExplicit('C'.$r, (string)$row['run'], DataType::TYPE_STRING);
    $sheet2->setCellValue('D'.$r, (string)($row['nom_colegio'] ?? '-'));
    $sheet2->setCellValueExplicit('E'.$r, (string)($row['id_contrato'] ?? ''), DataType::TYPE_STRING);
    $sheet2->setCellValue('F'.$r, (string)($row['dia_nombre'] ?? ''));

    $sheet2->setCellValue('G'.$r, fmtHora($row['man_ini'] ?? null));
    $sheet2->setCellValue('H'.$r, fmtHora($row['man_fin'] ?? null));
    $sheet2->setCellValue('I'.$r, fmtHora($row['tar_ini'] ?? null));
    $sheet2->setCellValue('J'.$r, fmtHora($row['tar_fin'] ?? null));

    $prevContrato = $currContrato;
    $prevEmpleado = $currEmpleado;
    $r++;
}
$lastRow2 = max(1, $r-1);

$sheet2->freezePane('A2');
$sheet2->setAutoFilter("A1:{$lastCol2}1");
for ($i=1; $i<=$colCount2; $i++) $sheet2->getColumnDimensionByColumn($i)->setAutoSize(true);
$sheet2->getStyle("A2:A{$lastRow2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet2->getStyle("C2:C{$lastRow2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet2->getStyle("E2:J{$lastRow2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
styleTableBorders($sheet2, "A1:{$lastCol2}{$lastRow2}");

/* ---------- Hoja 3 ---------- */
$sheet3 = $spreadsheet->createSheet();
$sheet3->setTitle('descarga bux');

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
    $sheet3->setCellValue($col.'1', $headers3[$i]);
}
styleHeader($sheet3, "A1:{$lastCol3}1");
$sheet3->getStyle("A1:{$lastCol3}1")->getFill()->getStartColor()->setRGB('C0C0C0');
$sheet3->getRowDimension(1)->setRowHeight(18);

$r = 2;
foreach ($datosBux as $row) {
    $sheet3->setCellValueExplicit('A'.$r, (string)$row['run'], DataType::TYPE_STRING);
    $sheet3->setCellValueExplicit('B'.$r, (string)$row['codigo'], DataType::TYPE_STRING);

    $colIndex = 3;
    for ($dia=1; $dia<=5; $dia++) {
        $horarioDia = $row['dias'][$dia] ?? [];
        foreach (['man_ini', 'man_fin', 'tar_ini', 'tar_fin'] as $campoHora) {
            $col = Coordinate::stringFromColumnIndex($colIndex);
            $sheet3->setCellValue($col.$r, fmtHoraBux($horarioDia[$campoHora] ?? null));
            $colIndex++;
        }
    }

    $horasCron = minutosAHoras($row['horas_semanales_cron'] ?? '');
    $sheet3->setCellValue('W'.$r, $horasCron === '-' ? '' : $horasCron);

    $minColacion = ($row['min_colacion_diaria'] ?? '') !== '' ? (int)$row['min_colacion_diaria'] : '';
    $sheet3->setCellValue('X'.$r, $minColacion);

    $minLectivas = (int)($row['horas_lectivas'] ?? 0);
    $minNoLectivas = (int)($row['horas_no_lectivas'] ?? 0);
    $sheet3->setCellValue('Y'.$r, minutosAPedagogicas($minLectivas));
    $sheet3->setCellValue('Z'.$r, minutosAHoras($minLectivas));
    $sheet3->setCellValue('AA'.$r, minutosAPedagogicas($minNoLectivas));
    $sheet3->setCellValue('AB'.$r, minutosAHoras($minNoLectivas));

    $r++;
}
$lastRow3 = max(1, $r-1);

$sheet3->freezePane('A2');
$sheet3->setAutoFilter("A1:{$lastCol3}1");
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
    $sheet3->getColumnDimension($col)->setWidth($width);
}
$sheet3->getStyle("A2:B{$lastRow3}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet3->getStyle("C2:AB{$lastRow3}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet3->getStyle("A1:{$lastCol3}{$lastRow3}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
styleTableBorders($sheet3, "A1:{$lastCol3}{$lastRow3}");

/* ==========================
   DESCARGA
   ========================== */
while (ob_get_level()) { ob_end_clean(); }

$filename = "empleados_horarios_" . date("Ymd_His") . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
