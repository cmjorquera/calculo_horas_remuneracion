<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION["id_usuario"])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

require_once __DIR__ . "/class/conexion.php";

$idEmpleado = (int)($_POST['id_empleado'] ?? 0);

if ($idEmpleado <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'ID de empleado inválido']);
    exit;
}

try {
    $db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");

    $sql = "
        SELECT c.observacion
        FROM contratos_empleado c
        INNER JOIN empleados e
            ON e.id_empleado = c.id_empleado
        WHERE c.id_empleado = ?
          AND e.id_colegio = ?
        ORDER BY
            CASE WHEN c.fecha_fin IS NULL THEN 0 ELSE 1 END,
            c.fecha_inicio DESC,
            c.id_contrato DESC
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception('No se pudo preparar la consulta.');
    }

    $idColegio = (int)($_SESSION["id_colegio"] ?? 0);
    $stmt->bind_param("ii", $idEmpleado, $idColegio);

    if (!$stmt->execute()) {
        throw new Exception('No se pudo ejecutar la consulta.');
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    echo json_encode([
        'ok' => true,
        'observacion' => $row['observacion'] ?? ''
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al obtener la observación']);
}
