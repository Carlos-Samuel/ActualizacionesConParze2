<?php
// Parámetros configurables
$horasReporte = 3;
$minutosReintento = 15;

// Ruta del archivo de estado
$registroPath = __DIR__ . '/estado_ejecucion.json';

// Fecha y hora actual
$ahora = new DateTime();
$fechaActual = $ahora->format('Y-m-d');
$horaActual = (int)$ahora->format('G'); 
$horaActualFormateada = $ahora->format('H') . ':00:00'; // Ejemplo: "03:00:00" 
$claveActual = $ahora->format('Y-m-d-G'); // Ejemplo: "2025-09-19-03"

// Verificar si es hora válida (múltiplo de $horasReporte)
if ($horaActual % $horasReporte !== 0) {
    echo "⏱️ Hora no válida ({$horaActual}:00). No se ejecuta el proceso.\n";
    exit;
}

// Cargar estado previo
$estado = [];
if (file_exists($registroPath)) {
    $estado = json_decode(file_get_contents($registroPath), true);

    // Limpieza automática si el archivo contiene fechas anteriores
    $primerClave = array_key_first($estado);
    if ($primerClave && strpos($primerClave, $fechaActual) !== 0) {
        echo "📅 Cambio de día detectado. Reiniciando archivo de estado...\n";
        $estado = [];
        file_put_contents($registroPath, json_encode($estado));
    }
}

// Estado actual para esta hora
$registro = $estado[$claveActual] ?? ['estado' => 'no_ejecutado', 'timestamp' => null];
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
        echo "⚠️ Proceso en progreso pero excedió {$minutosReintento} minutos. Reintentando...\n";
    }
} elseif ($estadoHora === 'error') {
    echo "🔁 Reintentando proceso tras error anterior a las {$horaActual}:00...\n";
} else {
    echo "🚀 Ejecutando proceso por primera vez a las {$horaActual}:00...\n";
}

// Registrar como "en_progreso"
$estado[$claveActual] = [
    'estado' => 'en_progreso',
    'timestamp' => $ahora->format(DateTime::ATOM)
];
file_put_contents($registroPath, json_encode($estado));

// 🔁 Aquí va tu lógica principal
$exito = ejecutarProceso(); // Simula ejecución

// Actualizar estado según resultado
$estado[$claveActual]['estado'] = $exito ? 'completado' : 'error';
file_put_contents($registroPath, json_encode($estado));

echo $exito
    ? "✅ Proceso completado exitosamente.\n"
    : "❌ Proceso falló. Se volverá a intentar en el próximo ciclo.\n";

// Simulación del proceso (reemplaza con tu lógica real)
function ejecutarProceso() {
    // Simula éxito o fallo aleatorio
    // gestorGerador
    return rand(0, 1) === 1;
}
?>