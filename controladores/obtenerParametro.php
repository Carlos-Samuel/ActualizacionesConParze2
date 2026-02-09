<?php
header('Content-Type: application/json');

require_once 'ConnectionParametrizacion.php';

try {

    $con = ConnectionParametrizacion::getInstance()->getConnection();

    $codigo = $_POST['codigo'] ?? null;

    if ($codigo) {
        $stmt = $con->prepare("SELECT * FROM parametros WHERE codigo = ? AND vigente = TRUE");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $res = $stmt->get_result();

        $numRows = $res->num_rows;

        if ($numRows === 0) {
            http_response_code(200);
            echo json_encode([
                'statusCode' => 404,
                'mensaje' => "No existe ningún parámetro vigente con el código proporcionado."
            ]);
        } elseif ($numRows > 1) {
            http_response_code(200);
            echo json_encode([
                'statusCode' => 409,
                'mensaje' => "Existen múltiples parámetros vigentes con el mismo código, revise la tabla."
            ]);
        } else {
            $parametro = $res->fetch_assoc();
            http_response_code(200);
            echo json_encode([
                'statusCode' => 200,
                'mensaje' => "Parámetro encontrado correctamente.",
                'parametro' => $parametro
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'statusCode' => 400,
            'mensaje' => "Código no proporcionado."
        ]);
    }
    exit;

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'statusCode' => 500,
        'mensaje' => "Error general: " . $e->getMessage()
    ]);
}
