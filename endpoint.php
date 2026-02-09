<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once 'generacionReporte.php';

$modo = $_GET['modo'] ?? $_POST['modo'] ?? 'completo';
$res = generarReporteInventario('cambios', ['debug' => true]);
// o: generarReporteInventario('completo', ['debug'=>true]);

if (!$res['ok']) {
    var_dump($res); // Ver 'error' y 'trace'
} else {
    echo "Archivo: {$res['filename']}\n";
    // Si activaste debug, revisa también:
    // print_r($res['trace']);
}

