<?php
$registroPath = __DIR__ . '/estado_ejecucion.json';
$hoy = date('Y-m-d'); // Fecha actual
$horaActual = (int)date('G');
$ahora = new DateTime();

$horasReporte = 3 ;
$minutosReintento = 15 ;
// Verifica si es hora válida (múltiplo de 3)
if ($horaActual % $horasReporte !== 0) {
    echo "Hora no válida ({$horaActual}:00). No se ejecuta el proceso.\n";
    exit;
}

// Cargar estado previo
$estado = [];
if (file_exists($registroPath)) {
    $estado = json_decode(file_get_contents($registroPath), true);

    // Verificar si hay registros de fechas anteriores
    $fechaPrimeraClave = explode('-', array_key_first($estado))[0] . '-' . array_key_first($estado)[1] . '-' . array_key_first($estado)[2];

    if ($fechaPrimeraClave !== $hoy) {
        echo "📅 Detectado cambio de día. Reiniciando archivo de estado...\n";
        $estado = []; // Vacía el estado
        file_put_contents($registroPath, json_encode($estado));
    }

}

// Estado actual de esta hora
$registro = $estado[$horaActual] ?? ['estado' => 'no_ejecutado', 'timestamp' => null];
$estadoHora = $registro['estado'];
$timestamp = $registro['timestamp'] ? new DateTime($registro['timestamp']) : null;

// Decisión según estado
if ($estadoHora === 'completado') {
    echo "✅ Proceso ya completado a las {$horaActual}:00. No se repite.\n";
    exit;
}

if ($estadoHora === 'en_progreso' && $timestamp) {
    $diferencia = $timestamp->diff($ahora);
    $minutosPasados = ($diferencia->h * 60) + $diferencia->i;

    if ($minutosPasados < $minutosReintento) {
        echo "⏳ Proceso aún en progreso (iniciado hace {$minutosPasados} min). No se repite.\n";
        exit;
    } else {
        echo "⚠️ Proceso en progreso pero excedió 15 minutos. Reintentando...\n";
    }
} else {
    echo "🚀 Ejecutando proceso por primera vez o tras error a las {$horaActual}:00...\n";
}

// Registrar como "en progreso" con timestamp actual
$estado[$horaActual] = [
    'estado' => 'en_progreso',
    'timestamp' => $ahora->format(DateTime::ATOM)
];
file_put_contents($registroPath, json_encode($estado));

// 🔁 Aquí va tu lógica principal
$exito = ejecutarProceso(); // Simula ejecución

// Actualizar estado según resultado
$estado[$horaActual]['estado'] = $exito ? 'completado' : 'error';
file_put_contents($registroPath, json_encode($estado));

echo $exito
    ? "✅ Proceso completado exitosamente.\n"
    : "❌ Proceso falló. Se volverá a intentar en el próximo ciclo.\n";

// Simulación del proceso
function ejecutarProceso() {
    // Reemplaza con tu lógica real
    return rand(0, 1) === 1;
}
?>