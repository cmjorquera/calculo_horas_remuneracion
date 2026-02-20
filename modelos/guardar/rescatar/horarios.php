<?php
header('Content-Type: application/json; charset=utf-8');

require_once "../../../class/conexion.php"; // ajusta si tu ruta real difiere

$id_contrato = isset($_POST['id_contrato']) ? (int)$_POST['id_contrato'] : 0;

if ($id_contrato <= 0) {
  echo json_encode(['ok' => false, 'msg' => 'id_contrato inválido']);
  exit;
}

try {
  $db = new MySQL(); // tu clase

  // Lunes(1) a Viernes(5) desde dias_semana, y LEFT JOIN a horarios_semanales
  $sql = "
    SELECT
      d.orden,
      d.nombre,
      UPPER(d.prefijo) AS dia_code,  -- LUN/MAR/MIE/JUE/VIE
      hs.man_ini, hs.man_fin, hs.tar_ini, hs.tar_fin,
      COALESCE(hs.activo, 1) AS activo
    FROM dias_semana d
    LEFT JOIN horarios_semanales hs
      ON hs.id_contrato = {$id_contrato}
     AND hs.dia = UPPER(d.prefijo)
    WHERE d.activo = 1
      AND d.orden BETWEEN 1 AND 5
    ORDER BY d.orden
  ";

  // Ajusta este método al tuyo (consulta/query/etc.)
  $rows = $db->consulta($sql);

  echo json_encode([
    'ok' => true,
    'id_contrato' => $id_contrato,
    'dias' => $rows ?: []
  ]);
} catch (Exception $e) {
  echo json_encode(['ok' => false, 'msg' => 'Error servidor']);
}
