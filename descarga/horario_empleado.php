<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

require_once "../class/conexion.php";
require_once "../PDF/fpdf.php";

/* ==========================
   VALIDACIÓN
   ========================== */
$id_contrato = isset($_GET['id_contrato']) ? (int)$_GET['id_contrato'] : 0;
if ($id_contrato <= 0) {
    die("Contrato inválido");
}

/* ==========================
   FUNCIONES AUXILIARES
   ========================== */

function pdf_txt($txt) {
    if ($txt === null) return '';
    $txt = (string)$txt;
    $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $txt);
    return $out !== false ? $out : utf8_decode($txt);
}

function fmtHora($h) {
    if (!$h) return '-';
    $h = (string)$h;
    return substr($h, 0, 5);
}

function minutosAHoras($valor) {
    if ($valor === '' || $valor === null) return '-';

    $valor = trim((string)$valor);

    // si viene como "40:00"
    if (strpos($valor, ':') !== false) {
        $p = explode(':', $valor);
        $hh = str_pad((string)((int)($p[0] ?? 0)), 2, '0', STR_PAD_LEFT);
        $mm = str_pad((string)((int)($p[1] ?? 0)), 2, '0', STR_PAD_LEFT);
        return $hh . ':' . $mm;
    }

    // si viene como minutos "2400"
    if (ctype_digit($valor)) {
        $min = (int)$valor;
        $h = intdiv($min, 60);
        $m = $min % 60;
        return str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string)$m, 2, '0', STR_PAD_LEFT);
    }

    return $valor;
}

function safe_filename($txt) {
    $txt = (string)$txt;
    $txt = iconv('UTF-8', 'ASCII//TRANSLIT', $txt);
    $txt = preg_replace('/[^A-Za-z0-9_\- ]/', '', $txt);
    $txt = preg_replace('/\s+/', '_', trim($txt));
    return $txt ?: 'Horario';
}

/* ==========================
   CONEXIÓN
   ========================== */
// Si tu clase MySQL ya trae credenciales por defecto, puedes usar: $db = new MySQL();
// Como tú lo estabas usando con credenciales, lo mantengo:
$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");

/* ==========================
   DATOS EMPLEADO + COLEGIO
   ========================== */
$sql = "
SELECT 
    e.nombres,
    e.apellido_paterno,
    e.apellido_materno,
    e.run,
    e.id_colegio,
    co.nom_colegio,
    c.horas_semanales_cron,
    c.min_colacion_diaria,
    c.observacion
FROM contratos_empleado c
INNER JOIN empleados e ON e.id_empleado = c.id_empleado
LEFT JOIN colegio co ON co.id_colegio = e.id_colegio
WHERE c.id_contrato = $id_contrato
LIMIT 1
";

$res = $db->consulta($sql);
$info = $db->fetch_assoc($res);

if (!$info) {
    die("No se encontraron datos");
}

$nombreCompleto = trim($info['nombres']." ".$info['apellido_paterno']." ".$info['apellido_materno']);
$run        = trim((string)$info['run']);
$colegio    = trim((string)$info['nom_colegio']);
$idColegio  = (int)$info['id_colegio'];

$horasCron   = minutosAHoras($info['horas_semanales_cron'] ?? '');
$minColacion = trim((string)($info['min_colacion_diaria'] ?? ''));
$observacion = trim((string)($info['observacion'] ?? ''));

if ($minColacion === '') $minColacion = '-';
if ($observacion === '') $observacion = '-';

/* ==========================
   HORARIO
   ========================== */
$sqlHorario = "
SELECT d.nombre,
       hs.man_ini, hs.man_fin,
       hs.tar_ini, hs.tar_fin
FROM dias_semana d
LEFT JOIN horarios_semanales hs
  ON hs.id_contrato = $id_contrato
 AND hs.dia = UPPER(d.prefijo)
WHERE d.orden BETWEEN 1 AND 5
ORDER BY d.orden
";

$resHorario = $db->consulta($sqlHorario);

/* ==========================
   PDF PERSONALIZADO
   ========================== */
class PDF extends FPDF {

    public $colegio = '';
    public $escudo = '';

    function Header() {

        // Escudo
        if ($this->escudo && file_exists($this->escudo)) {
            $this->Image($this->escudo, 10, 10, 18);
        }

        // Título
        $this->SetFont('Arial','B',16);
        $this->Cell(0,10,pdf_txt('Detalle de Horario'),0,1,'C');

        // Colegio
        $this->SetFont('Arial','',10);
        if ($this->colegio !== '') {
            $this->Cell(0,6,pdf_txt($this->colegio),0,1,'C');
        }

        // Línea
        $this->Ln(4);
        $this->SetDrawColor(200,200,200);
        $this->Line(10,$this->GetY(),200,$this->GetY());
        $this->Ln(6);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(80,80,80);
        $this->Cell(0,5,pdf_txt('Página '.$this->PageNo().'/{nb}'),0,0,'C');
    }
}

/* ==========================
   CREAR PDF (IMPORTANTE: setear variables ANTES de AddPage)
   ========================== */
$pdf = new PDF();
$pdf->AliasNbPages();

/* Escudo automático (ANTES del AddPage) */
$png = "../imagenes/colegios/colegio_".$idColegio.".png";
$jpg = "../imagenes/colegios/colegio_".$idColegio.".jpg";
$pdf->escudo  = file_exists($png) ? $png : (file_exists($jpg) ? $jpg : '');
$pdf->colegio = $colegio;

$pdf->AddPage();

/* ==========================
   DATOS EMPLEADO
   ========================== */
$pdf->SetTextColor(0,0,0);

$pdf->SetFont('Arial','B',11);
$pdf->Cell(35,7,pdf_txt('Empleado:'),0,0);
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,7,pdf_txt($nombreCompleto),0,1);

$pdf->SetFont('Arial','B',11);
$pdf->Cell(35,7,pdf_txt('RUN:'),0,0);
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,7,pdf_txt($run !== '' ? $run : '-'),0,1);

$pdf->SetFont('Arial','B',11);
$pdf->Cell(35,7,pdf_txt('CARGO:'),0,0);
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,7,pdf_txt($run !== '' ? $run : '-'),0,1);
$pdf->Ln(6);

/* ==========================
   TABLA HORARIO
   ========================== */
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,8,pdf_txt('Esquema semanal'),0,1);

$pdf->SetFillColor(230,230,230);

$wDia = 35;
$wCol = 38;

$pdf->SetFont('Arial','B',10);

$pdf->Cell($wDia,8,pdf_txt('Día'),1,0,'C',true);
$pdf->Cell($wCol,8,pdf_txt('Inicio (Mañana)'),1,0,'C',true);
$pdf->Cell($wCol,8,pdf_txt('Término (Mañana)'),1,0,'C',true);
$pdf->Cell($wCol,8,pdf_txt('Inicio (Tarde)'),1,0,'C',true);
$pdf->Cell($wCol,8,pdf_txt('Término (Tarde)'),1,1,'C',true);

$pdf->SetFont('Arial','',10);
$fill = false;

while($row = $db->fetch_assoc($resHorario)) {

    // alternado suave
    if ($fill) $pdf->SetFillColor(245,245,245);
    else       $pdf->SetFillColor(255,255,255);

    $pdf->Cell($wDia,8,pdf_txt($row['nombre'] ?? ''),1,0,'L',true);
    $pdf->Cell($wCol,8,pdf_txt(fmtHora($row['man_ini'] ?? null)),1,0,'C',true);
    $pdf->Cell($wCol,8,pdf_txt(fmtHora($row['man_fin'] ?? null)),1,0,'C',true);
    $pdf->Cell($wCol,8,pdf_txt(fmtHora($row['tar_ini'] ?? null)),1,0,'C',true);
    $pdf->Cell($wCol,8,pdf_txt(fmtHora($row['tar_fin'] ?? null)),1,1,'C',true);

    $fill = !$fill;
}

$pdf->Ln(8);

/* ==========================
   DATOS CONTRATO
   ========================== */
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,8,pdf_txt('Datos del contrato'),0,1);

$pdf->SetFont('Arial','',10);

$pdf->Cell(60,7,pdf_txt('Horas semanales (cron):'),0,0);
$pdf->Cell(0,7,pdf_txt($horasCron),0,1);

$pdf->Cell(60,7,pdf_txt('Min. colación diaria:'),0,0);
$pdf->Cell(0,7,pdf_txt($minColacion),0,1);

$pdf->Ln(3);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,7,pdf_txt('Observación:'),0,1);

$pdf->SetFont('Arial','',10);
$pdf->MultiCell(0,6,pdf_txt($observacion));

/* ==========================
   OUTPUT
   ========================== */
$nombreArchivo = 'Horario_' . safe_filename($nombreCompleto) . '_Contrato_' . $id_contrato . '.pdf';
$pdf->Output("D", $nombreArchivo);
exit;
