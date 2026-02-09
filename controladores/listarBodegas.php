<?php
header('Content-Type: application/json');

require_once 'Connection.php';
require_once 'ConnectionParametrizacion.php';

try {
    $con = Connection::getInstance()->getConnection();
    $connParametrizacion = ConnectionParametrizacion::getInstance()->getConnection();

    $codigoParam = 'EMPRESA';
    $stmtParam = $connParametrizacion->prepare("SELECT valor 
                                FROM parametros 
                                WHERE codigo = ? AND vigente = TRUE 
                                ORDER BY fecha_modificacion DESC 
                                LIMIT 1");
    $stmtParam->bind_param("s", $codigoParam);
    $stmtParam->execute();
    $resParam = $stmtParam->get_result();

    $empresasCod = [];
    if ($resParam && $row = $resParam->fetch_assoc()) {
        $valor = trim((string)$row['valor']);
        if ($valor !== '') {
            $empresasCod = array_values(array_filter(array_map('trim', explode(';', $valor)), function($v) {
                return $v !== '';
            }));
        }
    }

    if (count($empresasCod) === 0) {
        http_response_code(200);
        echo json_encode([
            'statusCode' => 200,
            'mensaje'    => 'No hay empresas parametrizadas vigentes para filtrar bodegas.',
            'bodegas'    => []
        ]);
        exit;
    }

    // 2) Armar consulta con IN dinámico sobre e.emprcod (los códigos parametrizados)
    // Nota: el JOIN se mantiene por empid/emprid.
    $placeholders = implode(',', array_fill(0, count($empresasCod), '?'));
    $sql = "SELECT 
                b.bodcod   AS bodcod,
                b.bodnom   AS bodnom,
                e.emprcod  AS emprcod,
                e.emprnom  AS emprnom
            FROM inbodega b
            INNER JOIN tbl_empresa e ON e.emprid = b.empid
            WHERE b.empid IS NOT NULL
              AND e.emprcod IN ($placeholders)
            ORDER BY e.emprnom ASC, b.bodnom ASC";

    $stmt = $con->prepare($sql);

    // bind_param dinámico (todos como string)
    $types = str_repeat('s', count($empresasCod));
    $params = [];
    $params[] = & $types;
    foreach ($empresasCod as $k => $v) {
        $params[] = & $empresasCod[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $params);

    $stmt->execute();
    $res = $stmt->get_result();

    $bodegas = [];
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $bodegas[] = [
                'bodcod'  => $row['bodcod'],
                'bodnom'  => $row['bodnom'],
                'emprcod' => $row['emprcod'],
                'emprnom' => $row['emprnom']
            ];
        }
    }

    http_response_code(200);
    echo json_encode([
        'statusCode' => 200,
        'mensaje'    => 'Bodegas listadas correctamente.',
        'bodegas'    => $bodegas
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'statusCode' => 500,
        'mensaje'    => 'Error al listar bodegas: ' . $e->getMessage()
    ]);
}
