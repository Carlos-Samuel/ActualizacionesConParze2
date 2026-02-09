<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'ConnectionParametrizacion.php';
require_once 'Connection.php';
require_once 'bootstrap.php';

require_once 'functions/generacionReporte.php';

mysqli_report(MYSQLI_REPORT_OFF);

function respond_ok(array $data): void {
    echo json_encode([
        'ok'   => true,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function respond_error(string $message, int $http = 500): void {
    http_response_code($http);
    echo json_encode([
        'ok'      => false,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $conParam = ConnectionParametrizacion::getInstance()->getConnection();
    $conParam->set_charset('utf8mb4');

    $conProd  = Connection::getInstance()->getConnection();
    $conProd->set_charset('utf8mb4');

    // 1) Parámetros (igual que generarReporteInventario)
    $pEmp = getValorVigenteParametro($conParam, "EMPRESA");
    $pBod = getValorVigenteParametro($conParam, "BODEGA");
    $pPre = getValorVigenteParametro($conParam, "PRECIOS");

    if (!$pEmp || !isset($pEmp['valor'])) throw new RuntimeException("No hay parámetro vigente para EMPRESA");
    if (!$pBod || !isset($pBod['valor'])) throw new RuntimeException("No hay parámetro vigente para BODEGA");
    if (!$pPre || !isset($pPre['valor'])) throw new RuntimeException("No hay parámetro vigente para PRECIOS");

    $emp = (string)$pEmp['valor'];
    $bod = (string)$pBod['valor'];
    $pre = (string)$pPre['valor'];

    // 2) DBF filtrado para sacar codes (igual que generarReporteInventario)
    $dbfData = leerDbfFiltrado($bod);
    $codes   = $dbfData['codes'] ?? [];

    if (empty($codes)) {
        respond_ok([]); // tabla vacía
    }

    // 3) Obtener productos por código (lo que pediste)
    // NOTA: $conParam no lo usa esa función, pero lo respetamos en la firma.
    $productosMap = obtenerProductosPorCodigo($conProd, $codes, $conParam, $emp, $pre);

    // 4) Armar salida para DataTable
    // productosMap viene indexado por procod con: subid, proprecio
    $rows = [];
    foreach ($productosMap as $procod => $p) {
        $und = $p['undcod'] ?? ''; // si decides devolver undcod en la función
        $codigo_parze = trim($procod . ($und !== '' ? "-$und" : ''));

        $rows[] = [
            'pronom'        => $p['pronom'] ?? null,
            'presentacion'  => $und,
            'codigo_parze'  => $codigo_parze,
            'precio'        => $p['proprecio'] ?? null,
        ];
    }

    respond_ok($rows);

} catch (Throwable $e) {
    respond_error($e->getMessage(), 500);
}
