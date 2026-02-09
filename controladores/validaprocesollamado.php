<?php
set_time_limit(0); // Evitar límite de tiempo de ejecución.
require_once 'validaBitacora.php';
require_once 'functions/generacionReporte.php';
require_once 'functions/bitacoraFunctions.php';
require_once 'bootstrap.php';
require_once 'ConnectionParametrizacion.php';

date_default_timezone_set(env_required('TIME_ZONE')); // Establecer zona horaria desde .env


$id_log = insertarLog('INICIO_PROCESO');

try{


    $code_horaDiaFull       = 'HORA_CARGUE_FULL';
    $code_cadaHoras         = 'FRECUENCIA_CARGUE_HORAS';
    $code_numeroReintentos  = 'REINTENTOS_API';
    $code_minutosReintento  = 'TIEMPO_ENTRE_REINTENTOS';

    $tipoDeCargue; // Tipo de cargue a validar en la bitácora

    $conParam = ConnectionParametrizacion::getInstance()->getConnection();
    $conParam->set_charset('utf8mb4');


    // 1. Obtener fecha y hora del sistema
    $fechaActual = date('Y-m-d') ;
    $horaActual = date('H:i:s'); // Hora con minutos y segundos actuales

    // 2. Constantes de configuración
    $horaDiaFull       = (string)getValorVigenteParametro($conParam, $code_horaDiaFull)['valor'];        // Ejemplo: proceso FULL a las 3 AM
    $cadaHoras         = (string)getValorVigenteParametro($conParam, $code_cadaHoras)['valor'];          // Cada cuántas horas se corre el DELTA
    $minutosReintento  = (string)getValorVigenteParametro($conParam, $code_minutosReintento)['valor'];   // Minutos entre reintentos
    $numeroReintentos  = (string)getValorVigenteParametro($conParam, $code_numeroReintentos)['valor'];   // Máximo de reintentos
    $reintento        = 0;          // Contador de reintentos

    //validar si encontró parametros

    echo $fechaActual, $horaActual, $horaDiaFull, $cadaHoras, $minutosReintento, $numeroReintentos;
    echo "\n";

    IF ($horaDiaFull == -1 || $cadaHoras == -1 || $minutosReintento == -1 || $numeroReintentos == -1){
        echo "Error: No se pudieron obtener todos los parámetros necesarios. Verifique la configuración.\n";
        exit;
    }

    // 6. Rutina principal

    if (debeEjecutarFull($horaActual, $horaDiaFull)) {
        $mensaje = "Es momento de ejecutar el proceso FULL.\n";
        echo $mensaje;
        actualizarLog($id_log, $mensaje);
        controlarEjecucion($conParam, 'FULL', $minutosReintento, $numeroReintentos);
    } elseif (debeEjecutarDelta($cadaHoras)) {
        $mensaje = "Es momento de ejecutar el proceso DELTA.\n";
        echo $mensaje;
        actualizarLog($id_log, $mensaje);
        controlarEjecucion($conParam, 'DELTA', $minutosReintento, $numeroReintentos);
    } else {
        $mensaje = "No es momento de ejecutar ningún proceso. Terminando.\n";
        echo $mensaje;
        actualizarLog($id_log, $mensaje);
    }


} catch (Exception $e) {
    // Manejo del error
    $mensaje = $e->getMessage();
    echo "Error: " . $mensaje;
    actualizarLog($id_log, $mensaje);
}

// 3. Determinar si se debe correr FULL o DELTA
function debeEjecutarFull($horaActual, $horaDiaFull) {
    if ($horaActual >= $horaDiaFull) {
        echo "Es hora de ejecutar FULL. Verificando bitácora...\n";
        return !existeFullHoy();
    }
    return false;
}

function debeEjecutarDelta($cadaHoras) {
    return !existeDeltaUltimasHoras($cadaHoras);
}

// 4. Simulación de ejecución del proceso
function ejecutarProceso($mode, $id) {
     echo "Ejecutando proceso $mode...\n";
    
    return generarReporteInventario($id, $mode);
}

// 5. Control de ejecución y reintentos
function controlarEjecucion($conParam, $tipoProceso, $minutosReintento, $numeroReintentos) {
    global $reintento;
    global $fechaActual ;
    global $horaActual ;

    $origen = "Automatico";
    
    do {
        $id_bitacora = crear_bitacora($conParam, $tipoProceso, $origen, $reintento);
        $exito = ejecutarProceso($tipoProceso,$id_bitacora);

        if ($exito) {
            echo "✅ Proceso $tipoProceso terminado correctamente.\n";
            return;
        }

        $reintento++;

        if ($reintento > $numeroReintentos) {
            echo "⚠️ Se alcanzó el número máximo de reintentos. Terminando programa.\n";
            return;
        }

        echo "❌ Error en proceso $tipoProceso. Reintento #$reintento...\n";
        
        sleep($minutosReintento * 1); // Esperar antes del siguiente intento

    } while (!$exito);
}
    
?>