<?php
require 'vendor/autoload.php';

require_once 'controladores/bootstrap.php';

use XBase\TableReader;

try {

    // Abrir la tabla (ajusta la codificación si es necesario)
    $tabla = new TableReader(DBF_PATH, [
        'encoding' => 'CP1252'
    ]);

    // Encabezados
    $columnas = [];
    foreach ($tabla->getColumns() as $col) {
        $columnas[] = $col->getName();
    }

    // Imprime encabezados
    echo implode(" | ", $columnas) . PHP_EOL;
    echo "<br>";

    // Imprime cada registro en una línea
    while ($registro = $tabla->nextRecord()) {
        $valores = [];
        //if ($registro->get('procod') == '9010002') {
            foreach ($columnas as $columna) {
                $valores[] = $registro->get($columna);
            }
            echo implode(" | ", $valores) . PHP_EOL;
            echo "<br>";
        //}

    }

    $tabla->close();

} catch (Exception $e) {
    echo "Error al leer el archivo DBF: " . $e->getMessage();
}
