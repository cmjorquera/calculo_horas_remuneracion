<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

require_once __DIR__ . "/../../class/conexion.php";
require_once __DIR__ . "/../../class/funciones.php";

$db = new MySQL("qaseduc_calculo_horario", "qaseduc_ucomun", "jorquera86;");

$idEmpleado = (int)($_POST['id_empleado'] ?? 0);
$action = $_POST['action'] ?? 'get';

if ($idEmpleado === 0) {
    echo json_encode(['ok' => false, 'msg' => 'ID de empleado inválido']);
    exit;
}

if ($action === 'save') {
    // Guardar observación
    $observacion = $_POST['observacion'] ?? '';
    
    try {
        $sql = "UPDATE empleados SET observacion = ? WHERE id_empleado = ? AND id_colegio = ?";
        $stmt = $db->conexion->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error en preparación: " . $db->conexion->error);
        }
        
        $stmt->bind_param("sii", $observacion, $idEmpleado, $_SESSION["id_colegio"]);
        
        if (!$stmt->execute()) {
            throw new Exception("Error en ejecución: " . $stmt->error);
        }
        
        $stmt->close();
        
        echo json_encode(['ok' => true, 'msg' => 'Observación guardada correctamente']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => 'Error al guardar: ' . $e->getMessage()]);
    }
} else {
    // Obtener observación
    try {
        $sql = "SELECT observacion FROM empleados WHERE id_empleado = ? AND id_colegio = ?";
        $stmt = $db->conexion->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error en preparación: " . $db->conexion->error);
        }
        
        $stmt->bind_param("ii", $idEmpleado, $_SESSION["id_colegio"]);
        
        if (!$stmt->execute()) {
            throw new Exception("Error en ejecución: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            echo json_encode([
                'ok' => true,
                'observacion' => $row['observacion'] ?? ''
            ]);
        } else {
            echo json_encode([
                'ok' => true,
                'observacion' => ''
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => 'Error al obtener: ' . $e->getMessage()]);
    }
}
