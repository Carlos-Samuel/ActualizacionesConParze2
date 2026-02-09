<?php
header('Content-Type: application/json');

require_once 'ConnectionParametrizacion.php';

try {
    $con = ConnectionParametrizacion::getInstance()->getConnection();
    
    $tipo_de_cargue = $_POST['tipo_de_cargue'] ?? null;
    $fecha_ejecucion = $_POST['fecha_ejecucion'] ?? null;
    $hora_ejecucion = $_POST['hora_ejecucion'] ?? null;     
    $origen_del_proceso = $_POST['origen_del_proceso'] ?? null;
    
    //Validación de los campos obligatorios
    if (!$tipo_de_cargue || !$origen_del_proceso || !$fecha_ejecucion || !$hora_ejecucion)  {
        http_response_code(400);
        echo json_encode([
            'statusCode' => 400,
            'mensaje' => 'Los campos Tipo de Cargue, Origen, fecha y hora de ejecución son obligatorios.'
        ]);
        exit;
    }
    
    $con->begin_transaction();

    $stmt1 = $con->prepare("INSERT INTO bitacora (tipo_de_cargue, fecha_ejecucion, hora_ejecucion, origen_del_proceso) VALUES(?, ?, ?, ?)");

    $stmt1->bind_param("ssss", $tipo_de_cargue, $fecha_ejecucion, $hora_ejecucion, $origen_del_proceso, ); 
    $stmt1->execute();

    $id_bitacora = $con->insert_id;

    $con->commit();

        http_response_code(200);
    echo json_encode([
        'statusCode' => 200,
        'id_bitacora' => $id_bitacora,
        'mensaje' => 'Bitacora Registrada correctamente.'
        
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