<?php
declare(strict_types=1);

require_once __DIR__ . '/../ConnectionParametrizacion.php';

function conn(): mysqli {
    return ConnectionParametrizacion::getInstance()->getConnection();
}

function crear_bitacora(mysqli $cx, $tipo_de_cargue, $origen, $reintento): int {
    $resultado     = 'Exitoso';
    $satisfactorio = 0;

    $sql = "INSERT INTO bitacora (
                tipo_de_cargue, fecha_ejecucion, hora_ejecucion, origen_del_proceso,
                resultado_del_envio, satisfactorio, reintento
            ) VALUES (
                ?, CURDATE(), CURTIME(), ?, ?, ?, ?
            )";

    $stmt = $cx->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException("Error prepare bitacora: " . $cx->error);
    }

    $stmt->bind_param('sssii', $tipo_de_cargue, $origen, $resultado, $satisfactorio, $reintento);
    if (!$stmt->execute()) {
        throw new RuntimeException("Error insert bitacora: " . $stmt->error);
    }
    $id = $cx->insert_id;
    $stmt->close();
    return (int)$id;
}

/** Función separada: registra un paso en bitacora_log */
function registrar_paso(mysqli $cx, int $id_bitacora, string $descripcion): void {
    $sql = "INSERT INTO bitacora_log (id_bitacora, descripcion_paso) VALUES (?, ?)";
    $stmt = $cx->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException("Error prepare bitacora_log: " . $cx->error);
    }
    $stmt->bind_param('is', $id_bitacora, $descripcion);
    if (!$stmt->execute()) {
        throw new RuntimeException("Error insert bitacora_log: " . $stmt->error);
    }
    $stmt->close();
}


function insertarLog($resultado) {
    $con = ConnectionParametrizacion::getInstance()->getConnection();

    $sql = "INSERT INTO log (resultado, fecha_creacion, fecha_ultima_actualizacion)
            VALUES (?, NOW(), NOW())";

    $stmt = $con->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException("Error prepare log insert: " . $con->error);
    }

    $stmt->bind_param('s', $resultado);
    if (!$stmt->execute()) {
        throw new RuntimeException("Error execute log insert: " . $stmt->error);
    }

    $id = $con->insert_id;
    $stmt->close();
    return (int)$id;
}

function actualizarLog($id, $nuevoResultado) {
    $con = ConnectionParametrizacion::getInstance()->getConnection();

    $sql = "UPDATE log 
            SET resultado = ?, fecha_ultima_actualizacion = NOW() 
            WHERE id = ?";

    $stmt = $con->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException("Error prepare log update: " . $con->error);
    }

    $stmt->bind_param('si', $nuevoResultado, $id);
    if (!$stmt->execute()) {
        throw new RuntimeException("Error execute log update: " . $stmt->error);
    }

    $filasAfectadas = $stmt->affected_rows;
    $stmt->close();
    return $filasAfectadas > 0;
}

/**
 * Verifica si existe al menos un registro DELTA en la fecha indicada.
 * @param string $fecha_ejecucion Formato 'YYYY-MM-DD'
 * @param bool $incluirManual Si es false, excluye origen_del_proceso = 'Manual'
 * @return bool
 */
function existeFullEnFecha(string $fecha_ejecucion): bool {
    $con = ConnectionParametrizacion::getInstance()->getConnection();

    $sql = "SELECT 1
            FROM bitacora
            WHERE tipo_de_cargue = 'FULL'
              AND fecha_ejecucion = ? 
              AND resultado_del_envio = 'Exitoso'
              LIMIT 1";

    $stmt = $con->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException("Error prepare existeFullEnFecha: " . $con->error);
    }

    $stmt->bind_param('s', $fecha_ejecucion);
    if (!$stmt->execute()) {
        throw new RuntimeException("Error execute existeFullEnFecha: " . $stmt->error);
    }

    $stmt->store_result();
    
    $existe = $stmt->num_rows > 0;
    $stmt->close();

    return $existe;
}

/**
 * Verifica si existe al menos un registro DELTA hoy (zona horaria America/Bogota).
 * @param bool $incluirManual Si es false, excluye origen_del_proceso = 'Manual'
 * @return bool
 */
function existeFullHoy(): bool {
    $tz   = new DateTimeZone('America/Bogota');
    $hoy  = (new DateTime('now', $tz))->format('Y-m-d');
    return existeFullEnFecha($hoy);
}

/**
 * Verifica si existe un registro DELTA exitoso en las últimas $horas horas.
 * Usa la zona horaria America/Bogota y compara contra fecha_hora_de_fin.
 *
 * @param int  $horas           Número de horas hacia atrás (>=1).
 * @return bool
 */
function existeDeltaUltimasHoras(int $horas): bool {
    if ($horas < 1) {
        throw new InvalidArgumentException("El parámetro 'horas' debe ser >= 1.");
    }

    $con = ConnectionParametrizacion::getInstance()->getConnection();

    // Ventana de tiempo [desde, hasta]
    $tz    = new DateTimeZone('America/Bogota');
    $hasta = new DateTime('now', $tz);
    $desde = (clone $hasta)->sub(new DateInterval('PT' . $horas . 'H'));

    $desdeStr = $desde->format('Y-m-d H:i:s');
    $hastaStr = $hasta->format('Y-m-d H:i:s');

    $sql = "SELECT 1
            FROM bitacora
            WHERE resultado_del_envio = 'Exitoso'
              AND fecha_hora_de_fin BETWEEN ? AND ?
            LIMIT 1";

    $stmt = $con->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException("Error prepare existeDeltaUltimasHoras: " . $con->error);
    }

    $stmt->bind_param('ss', $desdeStr, $hastaStr);
    if (!$stmt->execute()) {
        throw new RuntimeException("Error execute existeDeltaUltimasHoras: " . $stmt->error);
    }

    $stmt->store_result();
    $existe = $stmt->num_rows > 0;
    $stmt->close();

    return $existe;
}


function getValorVigenteParametro(mysqli $con, string $codigo): ?array {
    $sql = "SELECT valor
            FROM parametros
            WHERE codigo = ? AND vigente = TRUE
            ORDER BY fecha_modificacion DESC
            LIMIT 1";
    $st = $con->prepare($sql);
    if (!$st) throw new RuntimeException("Error prepare param: " . $con->error);
    $st->bind_param('s', $codigo);
    $st->execute();
    $res = $st->get_result();
    $row = $res->fetch_assoc() ?: null;
    $st->close();
    return $row;
}