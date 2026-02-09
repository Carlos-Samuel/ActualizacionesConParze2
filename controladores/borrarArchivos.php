<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $fechaInicio = $_POST['fechaInicio'] ?? '';
    $fechaFin    = $_POST['fechaFin'] ?? '';

    $reDate = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($reDate, $fechaInicio) || !preg_match($reDate, $fechaFin)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Fechas inválidas']);
        exit;
    }

    require_once __DIR__ . '/ConnectionParametrizacion.php';
    $con = ConnectionParametrizacion::getInstance()->getConnection();

    $sql = "SELECT id_bitacora, ruta_archivo
            FROM bitacora
            WHERE fecha_ejecucion BETWEEN ? AND ?
              AND archivo_borrado = 0
              AND ruta_archivo IS NOT NULL
              AND ruta_archivo <> ''";

    $stmt = $con->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Error preparando consulta: ' . $con->error);
    }
    $stmt->bind_param('ss', $fechaInicio, $fechaFin);
    $stmt->execute();
    $res = $stmt->get_result();

    $baseDir = realpath(__DIR__ . '/../exports');
    if ($baseDir === false) {
        throw new RuntimeException('No se pudo resolver la carpeta exports');
    }

    $total = 0;
    $borrados = 0;
    $noEncontrados = 0;
    $errores = 0;
    $detalles = [];

    // Prepara update
    $upd = $con->prepare("UPDATE bitacora SET archivo_borrado = 1 WHERE id_bitacora = ?");
    if (!$upd) {
        throw new RuntimeException('Error preparando UPDATE: ' . $con->error);
    }

    while ($row = $res->fetch_assoc()) {
        $total++;
        $id   = (int)$row['id_bitacora'];
        $ruta = trim((string)$row['ruta_archivo']);

        // Solo nombre de archivo para evitar traversal
        $fileName = basename($ruta);
        $fullPath = $baseDir . DIRECTORY_SEPARATOR . $fileName;

        $status = 'ok';

        try {
            $fullReal = realpath($fullPath);
            if ($fullReal !== false && str_starts_with($fullReal, $baseDir)) {
                if (is_file($fullReal)) {
                    // borrar archivo
                    if (@unlink($fullReal)) {
                        $borrados++;
                    } else {
                        $errores++;
                        $status = 'error_unlink';
                    }
                } else {
                    $noEncontrados++;
                    $status = 'no_encontrado';
                }
            } else {
                $noEncontrados++;
                $status = 'fuera_de_exports';
            }

            $upd->bind_param('i', $id);
            $upd->execute();

        } catch (Throwable $t) {
            $errores++;
            $status = 'excepcion: ' . $t->getMessage();
        }

        $detalles[] = [
            'id' => $id,
            'archivo' => $fileName,
            'status' => $status,
        ];
    }

    echo json_encode([
        'ok' => true,
        'total' => $total,
        'borrados' => $borrados,
        'no_encontrados' => $noEncontrados,
        'errores' => $errores
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
