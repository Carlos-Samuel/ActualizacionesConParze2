<?php
header('Content-Type: application/json');


require_once 'ConnectionParametrizacion.php';

function obtenerBitacora($fecha_ejecucion, $hora_ejecucion): bool {
    $con = ConnectionParametrizacion::getInstance()->getConnection();

    $stmt1 = $con->prepare("SELECT *
                            FROM bitacora  
                            WHERE fecha_ejecucion = ? AND hora_ejecucion = ? AND origen_del_proceso <> 'Manual'
                            ");

    $stmt1->bind_param("ss", $fecha_ejecucion, $hora_ejecucion ); 
    $stmt1->execute();
    $result = $stmt1->get_result();
    if ($result && $result->num_rows > 0) {
        // hay registros
        return TRUE ;
    }else {
        // no hay registros
        return FALSE;
        
    }
    
}

function obtenerParametro($codigo) {
    $con = ConnectionParametrizacion::getInstance()->getConnection();

    $stmt = $con->prepare("SELECT * FROM parametros WHERE codigo = ? AND vigente = TRUE");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $res = $stmt->get_result();

    $numRows = $res->num_rows;

    if ($numRows === 0) {
          // "No existe ningún parámetro vigente con el código proporcionado."
        
          
        return -1;
    } elseif ($numRows > 1) {
        
        // "Existen múltiples parámetros vigentes con el mismo código, revise la tabla."
       
        return -1;
    } else {
        $parametro = $res->fetch_assoc();
        return $parametro['valor'];
    }
}   
function registraBitacora($tipo_de_cargue, $fecha_ejecucion, $hora_ejecucion, $reintento) {
    $con = ConnectionParametrizacion::getInstance()->getConnection();

    $origen_del_proceso = 'Automatico';
    

    $sql = "INSERT INTO bitacora (
                tipo_de_cargue, 
                fecha_ejecucion, 
                hora_ejecucion, 
                origen_del_proceso, 
                reintento 
            ) VALUES (?, ?, ?, ?, ?)";

    $stmt = $con->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException("Error prepare bitacora: " . $con->error);
    }

    $stmt->bind_param('ssssi', $tipo_de_cargue, $fecha_ejecucion, $hora_ejecucion, $origen_del_proceso, $reintento);
    if (!$stmt->execute()) {
        throw new RuntimeException("Error insert bitacora: " . $stmt->error);
    }
    $id = $con->insert_id;
    $stmt->close();
    return (int)$id;
}