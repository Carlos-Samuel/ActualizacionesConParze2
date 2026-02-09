<?php
header('Content-Type: application/json');

require_once 'Connection.php';
require_once 'ConnectionParametrizacion.php';

try {
    $con = Connection::getInstance()->getConnection();
    $connParametrizacion = ConnectionParametrizacion::getInstance()->getConnection();

    // 1) Obtener el parámetro EMPRESA (emprcod separados por ;)
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
    
    // Si no hay empresas parametrizadas, devolver lista vacía (status 200)
    if (count($empresasCod) === 0) {
        http_response_code(200);
        echo json_encode([
            'statusCode' => 200,
            'mensaje'    => 'No hay empresas parametrizadas vigentes.',
            'subgrupos'    => []
        ]);
        exit;
    }



    // 2) Armar consulta con IN dinámico sobre e.grpcod (los códigos parametrizados)
    // Nota: el JOIN se mantiene por empid/emprid.
    $placeholders = implode(',', array_fill(0, count($empresasCod), '?'));
    $sql = "SELECT 
                e.emprnom  AS empnom,
                g.grpnom   AS grpnom,
                s.subnom   AS subnom,
                e.emprcod  AS empcod,
                g.grpcod   AS grpcod,
                s.subid    AS subid
            FROM insubgrupo s
            INNER JOIN ingrupos g ON g.grpid = s.grpid
            INNER JOIN tbl_empresa e ON e.emprid = s.empid
            WHERE s.empid IS NOT NULL
             AND s.grpid IS NOT NULL
             AND e.emprcod IN ($placeholders)
            ORDER BY e.emprnom ASC, g.grpnom ASC, s.subnom ASC";

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

      $subgrupos = [];
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $subgrupos[] = [
                'empnom'  => $row['empnom'],
                'grpnom'  => $row['grpnom'],
                'subnom'  => $row['subnom'],
                'empcod'  => $row['empcod'],
                'grpcod'  => $row['grpcod'],
                'subid'   => $row['subid']
            ];
        }
    }

    http_response_code(200);
    echo json_encode([
        'statusCode' => 200,
        'mensaje'    => 'Subgrupos listados correctamente.',
        'subgrupos'  => $subgrupos
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'statusCode' => 500,
        'mensaje'    => 'Error al listar sub grupos: ' . $e->getMessage()
    ]);
}
