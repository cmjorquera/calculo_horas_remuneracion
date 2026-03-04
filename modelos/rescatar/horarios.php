<?php
header('Content-Type: application/json; charset=utf-8');

require_once "../../class/conexion.php"; // desde modelos/rescatar/

$id_contrato = isset($_POST['id_contrato']) ? (int)$_POST['id_contrato'] : 0;
$id_empleado = isset($_POST['id_empleado']) ? (int)$_POST['id_empleado'] : 0;

if ($id_contrato <= 0 && $id_empleado <= 0) {
  echo json_encode(['ok' => false, 'msg' => 'Debe enviar id_contrato o id_empleado']);
  exit;
}

try {
  //  tu constructor exige 3 parámetros (aunque no los use)
  $db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");

  $whereHorario = "";
  $filtroUsado = "";

  if ($id_contrato > 0) {
    $cntRes = $db->consulta("
      SELECT COUNT(*) AS t
      FROM horarios_semanales
      WHERE id_contrato = {$id_contrato}
        AND activo = 1
    ");
    $cnt = (int)($db->fetch_assoc($cntRes)['t'] ?? 0);

    if ($cnt > 0) {
      $whereHorario = "hs.id_contrato = {$id_contrato}";
      $filtroUsado = "id_contrato";
    }
  }

  if ($whereHorario === "" && $id_empleado > 0) {
    $whereHorario = "hs.id_empleado = {$id_empleado}";
    $filtroUsado = "id_empleado";
  }

  if ($whereHorario === "" && $id_contrato > 0) {
    $whereHorario = "hs.id_contrato = {$id_contrato}";
    $filtroUsado = "id_contrato";
  }

  $sql = "
    SELECT
      d.orden,
      d.nombre,
      UPPER(d.prefijo) AS dia_code,
      hs.man_ini, hs.man_fin, hs.tar_ini, hs.tar_fin,
      COALESCE(hs.activo, 1) AS activo
    FROM dias_semana d
    LEFT JOIN horarios_semanales hs
      ON {$whereHorario}
     AND (
          UPPER(TRIM(hs.dia)) = UPPER(TRIM(d.prefijo))
       OR UPPER(TRIM(hs.dia)) = UPPER(TRIM(d.clave))
       OR UPPER(TRIM(hs.dia)) = UPPER(LEFT(TRIM(d.clave), 3))
       OR UPPER(LEFT(TRIM(hs.dia), 3)) = UPPER(LEFT(TRIM(d.prefijo), 3))
       OR UPPER(LEFT(TRIM(hs.dia), 3)) = UPPER(LEFT(TRIM(d.clave), 3))
     )
    WHERE d.activo = 1
      AND d.orden BETWEEN 1 AND 5
    ORDER BY d.orden
  ";

  $res = $db->consulta($sql);

  $dias = [];
  while ($row = $db->fetch_assoc($res)) {
    $dias[] = $row;
  }

  echo json_encode([
    'ok' => true,
    'dias' => $dias,
    'debug' => [
      'id_contrato' => $id_contrato,
      'id_empleado' => $id_empleado,
      'filtro' => $filtroUsado
    ]
  ]);
  exit;

} catch (Throwable $e) {
  echo json_encode(['ok' => false, 'msg' => 'Error servidor']);
  exit;
}
