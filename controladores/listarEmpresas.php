<?php
header('Content-Type: application/json');


require_once 'Connection.php';

try {
    $con = Connection::getInstance()->getConnection();

    $sql = "SELECT emprcod, emprnom
            FROM tbl_empresa
            ORDER BY emprnom ASC";

    $res = $con->query($sql);

    $empresas = [];
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $empresas[] = [
                'emprcod' => $row['emprcod'],
                'emprnom' => $row['emprnom']
            ];
        }
    }

    http_response_code(200);
    echo json_encode([
        'statusCode' => 200,
        'mensaje'    => 'Empresas listadas correctamente.',
        'empresas'   => $empresas
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'statusCode' => 500,
        'mensaje'    => 'Error al listar empresas: ' . $e->getMessage()
    ]);
}
