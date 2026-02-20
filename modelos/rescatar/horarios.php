<?php
header('Content-Type: application/json; charset=utf-8');

require_once "../../class/conexion.php"; // ✅ desde modelos/rescatar/

$id_contrato = isset($_POST['id_contrato']) ? (int)$_POST['id_contrato'] : 0;

if ($id_contrato <= 0) {
  echo json_encode(['ok' => false, 'msg' => 'id_contrato inválido']);
  exit;
}

try {
  //  tu constructor exige 3 parámetros (aunque no los use)
  $db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");

  $sql = "
    SELECT
      d.orden,
      d.nombre,
      UPPER(d.prefijo) AS dia_code,
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

  $res = $db->consulta($sql);

  $dias = [];
  while ($row = $db->fetch_assoc($res)) {
    $dias[] = $row;
  }

  echo json_encode(['ok' => true, 'dias' => $dias]);
  exit;

} catch (Throwable $e) {
  echo json_encode(['ok' => false, 'msg' => 'Error servidor']);
  exit;
}
