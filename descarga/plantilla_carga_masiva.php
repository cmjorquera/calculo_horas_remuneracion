<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Plantilla');

$headers = [
    'RUN',
    'Nombre',
    'Apellido paterno',
    'Apellido materno',
    'Genero',
    'Telefono',
    'Observacion',
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
];

$sheet->fromArray($headers, null, 'A1');
$sheet->freezePane('A2');
$lastColumn = Coordinate::stringFromColumnIndex(count($headers));
$sheet->setAutoFilter('A1:' . $lastColumn . '1');

$sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '004E8C'],
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'D9E2EF'],
        ],
    ],
]);

$sheet->getStyle('H1:' . $lastColumn . '1')->getFill()->getStartColor()->setRGB('0070C0');
$sheet->getRowDimension(1)->setRowHeight(24);

$widths = [
    'A' => 16,
    'B' => 24,
    'C' => 24,
    'D' => 24,
    'E' => 16,
    'F' => 18,
    'G' => 42,
    'H' => 21,
    'I' => 19,
    'J' => 21,
    'K' => 19,
    'L' => 22,
    'M' => 20,
    'N' => 22,
    'O' => 20,
    'P' => 25,
    'Q' => 23,
    'R' => 25,
    'S' => 23,
    'T' => 22,
    'U' => 20,
    'V' => 22,
    'W' => 20,
    'X' => 23,
    'Y' => 21,
    'Z' => 23,
    'AA' => 21,
];

foreach ($widths as $column => $width) {
    $sheet->getColumnDimension($column)->setWidth($width);
}

$sheet->getStyle('A2:' . $lastColumn . '500')->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'E5E7EB'],
        ],
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true,
    ],
]);

$validation = $sheet->getCell('E2')->getDataValidation();
$validation->setType(DataValidation::TYPE_LIST);
$validation->setErrorStyle(DataValidation::STYLE_STOP);
$validation->setAllowBlank(true);
$validation->setShowDropDown(true);
$validation->setShowErrorMessage(true);
$validation->setErrorTitle('Genero invalido');
$validation->setError('Selecciona Masculino, Femenino u Otro.');
$validation->setFormula1('"Masculino,Femenino,Otro"');

for ($row = 2; $row <= 500; $row++) {
    $sheet->getCell('E' . $row)->setDataValidation(clone $validation);
}

$timeOptionsSheet = $spreadsheet->createSheet();
$timeOptionsSheet->setTitle('Opciones');
$timeOptionRow = 1;
for ($hour = 0; $hour <= 23; $hour++) {
    for ($minute = 0; $minute <= 55; $minute += 5) {
        $timeOptionsSheet->setCellValueExplicit(
            'A' . $timeOptionRow,
            sprintf('%02d:%02d', $hour, $minute),
            DataType::TYPE_STRING
        );
        $timeOptionRow++;
    }
}
$timeOptionsSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
$spreadsheet->addNamedRange(new NamedRange('ListaHoras', $timeOptionsSheet, '$A$1:$A$' . ($timeOptionRow - 1)));

$timeValidation = $sheet->getCell('H2')->getDataValidation();
$timeValidation->setType(DataValidation::TYPE_LIST);
$timeValidation->setErrorStyle(DataValidation::STYLE_STOP);
$timeValidation->setAllowBlank(true);
$timeValidation->setShowDropDown(true);
$timeValidation->setShowErrorMessage(true);
$timeValidation->setErrorTitle('Hora inválida');
$timeValidation->setError('Selecciona una hora de la lista.');
$timeValidation->setFormula1('=ListaHoras');

$firstTimeColumn = Coordinate::columnIndexFromString('H');
$lastTimeColumn = Coordinate::columnIndexFromString('AA');
for ($columnIndex = $firstTimeColumn; $columnIndex <= $lastTimeColumn; $columnIndex++) {
    $column = Coordinate::stringFromColumnIndex($columnIndex);
    for ($row = 2; $row <= 500; $row++) {
        $sheet->getCell($column . $row)->setDataValidation(clone $timeValidation);
    }
}

$instructionSheet = $spreadsheet->createSheet();
$instructionSheet->setTitle('Instrucciones');
$instructionSheet->fromArray([
    ['Carga masiva de funcionarios'],
    ['1. Mantén los encabezados de la hoja Plantilla sin modificar.'],
    ['2. Completa una fila por funcionario.'],
    ['3. RUN, Nombre y Apellido paterno son obligatorios.'],
    ['4. Genero acepta Masculino, Femenino u Otro.'],
    ['5. Completa las columnas de horario seleccionando horas en formato HH:MM. Los totales los calcula el sistema.'],
    ['6. Guarda el archivo como .xlsx antes de cargarlo al sistema.'],
], null, 'A1');
$instructionSheet->getColumnDimension('A')->setWidth(90);
$instructionSheet->getStyle('A1')->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 14,
        'color' => ['rgb' => '004E8C'],
    ],
]);
$instructionSheet->getStyle('A2:A7')->getAlignment()->setWrapText(true);

$spreadsheet->setActiveSheetIndex(0);

while (ob_get_level() > 0) {
    ob_end_clean();
}

$filename = 'plantilla_carga_masiva_' . date('Y_m_d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
