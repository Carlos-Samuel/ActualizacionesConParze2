<?php
header('Content-Type: application/json');

require_once 'ConnectionParametrizacion.php';


    $con = ConnectionParametrizacion::getInstance()->getConnection();
    
    $fechaInicio = $_POST['fechaInicio'] ?? null;
    $fechaFin = $_POST['fechaFin'] ?? null;
    
    //Validación fechas obligatorios
        if (!$fechaInicio || !$fechaFin) { 
        //http_response_code(400);
        echo json_encode([
            'data' => [],
            'error' => 'Fechas obligatorias'
        ]);
        exit;
    }
    $where = "WHERE fecha_ejecucion  BETWEEN ? AND ?";
    $params = [$fechaInicio, $fechaFin];
    $types = "ss";


    $sql = ("SELECT 
                id_bitacora,
                tipo_de_cargue, 
                fecha_ejecucion, 
                hora_ejecucion, 
                origen_del_proceso, 
                reintento,
                cantidad_registros_enviados, 
                tamaño_del_archivo, 
                resultado_del_envio, 
                descripcion_error, 
                parametros_usados, 
                fecha_hora_de_inicio, 
                fecha_hora_de_fin, 
                satisfactorio, 
                ruta_archivo, 
                archivo_borrado 
    FROM bitacora 
    $where
    ORDER BY fecha_hora_de_inicio DESC");


    //WHERE (fecha_ejecución >= ? AND fecha_ejecion <= ?)
    
$query = $con->prepare($sql);

$query->bind_param($types, ...$params);
$query->execute();
$result = $query->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(['data' => $data]);
