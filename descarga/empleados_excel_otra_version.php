<?php
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

$debugLog = __DIR__ . '/_excel_debug.log';

function dbg($msg) {
    global $debugLog;
    @file_put_contents($debugLog, "[".date("Y-m-d H:i:s")."] ".$msg.PHP_EOL, FILE_APPEND);
}

register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        dbg("FATAL: ".$e['message']." | ".$e['file'].":".$e['line']);
    }
});

dbg("=== START empleados_excel.php ===");
dbg("DIR: " . __DIR__);

require_once __DIR__ . "/../class/conexion.php";

/* ==========================
   AUTOLOAD (robusto)
   ========================== */
$autoload1 = __DIR__ . "/../vendor/autoload.php";          // public_html/calculo_horas/vendor/autoload.php
$autoload2 = __DIR__ . "/../../vendor/autoload.php";       // por si el script está en subcarpeta distinta

if (file_exists($autoload1)) {
    require_once $autoload1;
    dbg("autoload OK: ".$autoload1);
} elseif (file_exists($autoload2)) {
    require_once $autoload2;
    dbg("autoload OK: ".$autoload2);
} else {
    dbg("ERROR: No se encontró autoload.php. Probé: ".$autoload1." y ".$autoload2);
    http_response_code(500);
    exit;
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;

/* ==========================
   CONEXIÓN
   ========================== */
try {
    $db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");
    dbg("DB OK");
} catch (Throwable $t) {
    dbg("DB ERROR: ".$t->getMessage());
    http_response_code(500);
    exit;
}

/* ==========================
   CONSULTA
   ========================== */
$sql = "
SELECT 
  e.id_empleado,
  e.codigo,
  e.run,
  e.nombres,
  e.apellido_paterno,
  e.apellido_materno,
  e.email,
  e.telefono,
  e.activo,
  e.id_colegio,
  co.nom_colegio
FROM empleados e
LEFT JOIN colegio co ON co.id_colegio = e.id_colegio
ORDER BY co.nom_colegio ASC, e.apellido_paterno ASC, e.apellido_materno ASC, e.nombres ASC
";

try {
    $res = $db->consulta($sql);
    dbg("Query OK");
} catch (Throwable $t) {
    dbg("Query ERROR: ".$t->getMessage());
    http_response_code(500);
    exit;
}

/* ==========================
   EXCEL
   ========================== */
try {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Empleados');

    $headers = [
        'ID','Código','RUN','Nombres','Apellido Paterno','Apellido Materno',
        'Colegio','Email','Teléfono','Activo'
    ];

    $colCount = count($headers);

    // Encabezados
for ($i=0; $i<$colCount; $i++) {
    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1);
    $sheet->setCellValue($col.'1', $headers[$i]);
}


    // Estilo encabezado
    $lastColLetter = Coordinate::stringFromColumnIndex($colCount);
    $headerRange = "A1:{$lastColLetter}1";

    $sheet->getStyle($headerRange)->applyFromArray([
        'font' => ['bold' => true],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'EAEAEA']
        ]
    ]);

    $sheet->getRowDimension(1)->setRowHeight(20);

    // Datos
    $row = 2;
    while ($r = $db->fetch_assoc($res)) {

   $sheet->setCellValue('A'.$row, (int)$r['id_empleado']);
$sheet->setCellValueExplicit('B'.$row, (string)$r['codigo'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
$sheet->setCellValueExplicit('C'.$row, (string)$r['run'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

$sheet->setCellValue('D'.$row, (string)$r['nombres']);
$sheet->setCellValue('E'.$row, (string)$r['apellido_paterno']);
$sheet->setCellValue('F'.$row, (string)$r['apellido_materno']);

$sheet->setCellValue('G'.$row, (string)($r['nom_colegio'] ?? '-'));
$sheet->setCellValue('H'.$row, (string)($r['email'] ?? ''));
$sheet->setCellValue('I'.$row, (string)($r['telefono'] ?? ''));

$activo = ((int)($r['activo'] ?? 0) === 1) ? 'Sí' : 'No';
$sheet->setCellValue('J'.$row, $activo);


        $row++;
    }

    $lastDataRow = $row - 1;

    // Congelar fila 1
    $sheet->freezePane('A2');

    // AutoFiltro
    $sheet->setAutoFilter("A1:{$lastColLetter}1");

    // AutoSize
    for ($i=1; $i<=$colCount; $i++) {
        $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
    }

    // Alineaciones
    if ($lastDataRow >= 2) {
        $sheet->getStyle("A2:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B2:C{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("J2:J{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    // Bordes
    $sheet->getStyle("A1:{$lastColLetter}{$lastDataRow}")->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'DDDDDD']
            ]
        ],
    ]);

    dbg("Excel build OK. Rows: ".$lastDataRow);

} catch (Throwable $t) {
    dbg("Excel ERROR: ".$t->getMessage());
    http_response_code(500);
    exit;
}

/* ==========================
   DESCARGA (LIMPIAR OUTPUT)
   ========================== */
while (ob_get_level()) { ob_end_clean(); }

$filename = "empleados_" . date("Ymd_His") . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');

try {
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    dbg("Download OK");
} catch (Throwable $t) {
    dbg("Writer ERROR: ".$t->getMessage());
    http_response_code(500);
}

exit;
