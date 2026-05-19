<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
];

$sheet->fromArray($headers, null, 'A1');
$sheet->freezePane('A2');
$sheet->setAutoFilter('A1:G1');

$sheet->getStyle('A1:G1')->applyFromArray([
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

$sheet->getRowDimension(1)->setRowHeight(24);

$widths = [
    'A' => 16,
    'B' => 24,
    'C' => 24,
    'D' => 24,
    'E' => 16,
    'F' => 18,
    'G' => 42,
];

foreach ($widths as $column => $width) {
    $sheet->getColumnDimension($column)->setWidth($width);
}

$sheet->getStyle('A2:G500')->applyFromArray([
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

$instructionSheet = $spreadsheet->createSheet();
$instructionSheet->setTitle('Instrucciones');
$instructionSheet->fromArray([
    ['Carga masiva de funcionarios'],
    ['1. Mantén los encabezados de la hoja Plantilla sin modificar.'],
    ['2. Completa una fila por funcionario.'],
    ['3. RUN, Nombre y Apellido paterno son obligatorios.'],
    ['4. Genero acepta Masculino, Femenino u Otro.'],
    ['5. Guarda el archivo como .xlsx antes de cargarlo al sistema.'],
], null, 'A1');
$instructionSheet->getColumnDimension('A')->setWidth(90);
$instructionSheet->getStyle('A1')->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 14,
        'color' => ['rgb' => '004E8C'],
    ],
]);
$instructionSheet->getStyle('A2:A6')->getAlignment()->setWrapText(true);

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
