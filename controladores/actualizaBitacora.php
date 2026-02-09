<?php
header('Content-Type: application/json');

require_once 'ConnectionParametrizacion.php';

try {
    $con = ConnectionParametrizacion::getInstance()->getConnection();

    $id_bitacora = $_POST['id_bitacora'] ?? null;
    $tipo_actualizacion = $_POST['tipo_actualizacion'] ?? null;

    $cantidad_registros_enviados = $_POST['cantidad_registros_enviados'] ?? null;
    $tamaño_del_archivo = $_POST['tamaño_del_archivo'] ?? null;
    $resultado_del_envio = $_POST['resultado_del_envio'] ?? null;
    $descripcion_error = $_POST['descripcion_error'] ?? null;    
    $parametros_usados = $_POST['parametros_usados'] ?? null;    
    $satisfactorio = $_POST['satisfactorio'] ?? null;
    $ruta_archivo = $_POST['ruta_archivo'] ?? null;
    $archivo_borrado = $_POST['archivo_borrado'] ?? null;

    //Validación de los campos obligatorios
    if (!$id_bitacora  ||  !$tipo_actualizacion ) {
        http_response_code(400);
        echo json_encode([
            'statusCode' => 400,
            'mensaje' => 'El campo id_bitacora y tipo_actualización son  obligatorios.'
        ]);
        exit;
    }
    if ($tipo_actualizacion !== 'Archivo' && $tipo_actualizacion !== 'Resultado' && $tipo_actualizacion !== 'Error') {
        http_response_code(404);
        echo json_encode([
            'statusCode' => 4040,
            'mensaje' => 'El campo tipo_actualización no es válido.'
        ]);
        exit;
    }
    if ($tipo_actualizacion === 'Archivo'){
            $con->begin_transaction();

            $stmt1 = $con->prepare("UPDATE bitacora  SET cantidad_registros_enviados = ?, tamaño_del_archivo = ?, resultado_del_envio = ? , descripcion_error = ? , parametros_usados = ?,  fecha_hora_de_fin = now(), satisfactorio = ?, ruta_archivo = ?, archivo_borrado = ? WHERE id_bitacora = ? ");

            $stmt1->bind_param("issssisii", $cantidad_registros_enviados, $tamaño_del_archivo, $resultado_del_envio, $descripcion_error, $parametros_usados, $satisfactorio, $ruta_archivo, $archivo_borrado, $id_bitacora  ); 
            $stmt1->execute();

            $con->commit();

            http_response_code(200);
            echo json_encode([
                'statusCode' => 200,
                'mensaje' => 'Bitacora Actualizada correctamente.'
            ]);
    }   elseif ($tipo_actualizacion === 'Resultado'){
            $con->begin_transaction();

            $stmt2 = $con->prepare("UPDATE bitacora  SET cantidad_registros_enviados = ?, tamaño_del_archivo = ?, resultado_del_envio = ? , descripcion_error = ? , parametros_usados = ?,  fecha_hora_de_fin = now(), satisfactorio = ? WHERE id_bitacora = ? ");

            $stmt2->bind_param("issssii", $cantidad_registros_enviados, $tamaño_del_archivo, $resultado_del_envio, $descripcion_error, $parametros_usados, $satisfactorio, $id_bitacora  ); 
            $stmt2->execute();

            $con->commit();

            http_response_code(200);
            echo json_encode([
                'statusCode' => 200,
                'mensaje' => 'Bitacora Actualizada correctamente.'
            ]);
    }
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
