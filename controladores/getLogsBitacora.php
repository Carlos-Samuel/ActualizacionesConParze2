<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

require_once 'ConnectionParametrizacion.php';

$id = isset($_GET['id_bitacora']) ? (int)$_GET['id_bitacora'] : 0;
if ($id <= 0) {
    echo json_encode(['ok' => true, 'rows' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$conParam = ConnectionParametrizacion::getInstance()->getConnection();

$sql = "SELECT id_bitacora_log, id_bitacora, descripcion_paso, momento_de_registro
        FROM bitacora_log
        WHERE id_bitacora = ?
        ORDER BY momento_de_registro ASC, id_bitacora_log ASC";
$stmt = $conParam->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $conParam->error], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $stmt->error], JSON_UNESCAPED_UNICODE);
    $stmt->close();
    exit;
}

$stmt->bind_result($rid, $rbit, $rdesc, $rmomento);
$rows = [];
while ($stmt->fetch()) {
    $rows[] = [
        'id_bitacora_log'     => $rid,
        'id_bitacora'         => $rbit,
        'descripcion_paso'    => $rdesc,
        'momento_de_registro' => $rmomento,
    ];
}
$stmt->close();

echo json_encode(['ok' => true, 'rows' => $rows], JSON_UNESCAPED_UNICODE);
