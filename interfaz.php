<?php
declare(strict_types=1);

require_once 'generacionReporte.php';

// Puedes parametrizar esto:
$modo = 'cambios'; // 'completo' o 'cambios'

// Llamada directa a la función:
$result = generarReporteInventario($modo);

if (!$result['ok']) {
    // Manejo de error en tu flujo
    error_log('Error generando reporte: ' . $result['error']);
    echo "FALLÓ: " . $result['error'] . PHP_EOL;
    exit(1);
}

// Uso del resultado:
echo "OK modo={$result['mode']}, rows={$result['rows']}, diff_rows={$result['diff_rows']}\n";
echo "CSV generado: {$result['filename']}\n";

// Si quieres JSON aquí:
# echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
