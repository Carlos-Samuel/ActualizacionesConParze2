<?php
header('Content-Type: application/json');

require_once 'ConnectionParametrizacion.php';

try {
    $con = ConnectionParametrizacion::getInstance()->getConnection();

    $codigo = $_POST['codigo'] ?? null;
    $descripcion = $_POST['descripcion'] ?? null;
    $valor = $_POST['valor'] ?? null;

    if (!$codigo) {
        http_response_code(400);
        echo json_encode([
            'statusCode' => 400,
            'mensaje' => 'El campo "codigo" es obligatorio.'
        ]);
        exit;
    }

    $con->begin_transaction();

    $stmt1 = $con->prepare("UPDATE parametros SET vigente = FALSE, fecha_modificacion = NOW() WHERE codigo = ? AND vigente = TRUE");
    $stmt1->bind_param("s", $codigo);
    $stmt1->execute();

    $stmt2 = $con->prepare("INSERT INTO parametros (codigo, descripcion, valor, vigente, fecha_creacion, fecha_modificacion) VALUES (?, ?, ?, TRUE, NOW(), NOW())");
    $stmt2->bind_param("sss", $codigo, $descripcion, $valor);
    $stmt2->execute();

    $con->commit();

    http_response_code(200);
    echo json_encode([
        'statusCode' => 200,
        'mensaje' => 'Parámetro guardado correctamente.'
    ]);
} catch (Exception $e) {
    if (isset($con) && $con->errno === 0) {
        $con->rollback();
    }
    http_response_code(500);
    echo json_encode([
        'statusCode' => 500,
        'mensaje' => 'Error general: ' . $e->getMessage()
    ]);
}
