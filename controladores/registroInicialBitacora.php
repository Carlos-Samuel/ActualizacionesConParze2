<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

require_once 'functions/bitacoraFunctions.php';

try {
    $conParam = ConnectionParametrizacion::getInstance()->getConnection();

    $id = crear_bitacora($conParam, $_POST['tipo_de_cargue'] ?? 'DELTA', 'Manual', 0);

    echo json_encode(['ok' => true, 'id_bitacora' => $id], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
