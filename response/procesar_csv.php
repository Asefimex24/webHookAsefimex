<?php
session_start();
date_default_timezone_set("America/Mexico_City");
require_once '../getEnv.php';

header('Content-Type: application/json');

function normalizarEncoding($texto) {
    $encoding = mb_detect_encoding($texto, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    if ($encoding && $encoding !== 'UTF-8') {
        return mb_convert_encoding($texto, 'UTF-8', $encoding);
    }
    return $texto;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'upload') {
    manejarUpload();
} elseif ($action === 'save') {
    manejarSave();
} else {
    echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}

function manejarUpload() {
    if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Error al subir el archivo']);
        return;
    }

    $delimitador = $_POST['delimitador'] === ';' ? ';' : ',';

    $handle = fopen($_FILES['csv']['tmp_name'], 'r');
    if (!$handle) {
        echo json_encode(['success' => false, 'error' => 'No se pudo leer el archivo']);
        return;
    }

    // Leer cabeceras
    $headers = fgetcsv($handle, 0, $delimitador);
    if (!$headers) {
        fclose($handle);
        echo json_encode(['success' => false, 'error' => 'El archivo CSV está vacío o no tiene cabeceras']);
        return;
    }

    // Normalizar cabeceras
    $headers = array_map('trim', $headers);
    // Eliminar BOM UTF-8 si existe
    $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);

    $headers = array_map('normalizarEncoding', $headers);

    $rows = [];
    while (($data = fgetcsv($handle, 0, $delimitador)) !== false) {
        $row = [];
        foreach ($headers as $i => $header) {
            $val = isset($data[$i]) ? trim($data[$i]) : '';
            $row[$header] = normalizarEncoding($val);
        }
        $rows[] = $row;
    }
    fclose($handle);

    if (empty($rows)) {
        echo json_encode(['success' => false, 'error' => 'El archivo CSV no contiene datos']);
        return;
    }

    // Guardar en sesión
    $_SESSION['csv_headers'] = $headers;
    $_SESSION['csv_rows'] = $rows;

    // Devolver solo las primeras 20 filas para preview
    $preview = array_slice($rows, 0, 20);

    echo json_encode([
        'success' => true,
        'headers' => $headers,
        'preview' => $preview,
        'totalRows' => count($rows),
        'previewRows' => count($preview)
    ]);
}

function manejarSave() {
    if (!isset($_SESSION['csv_rows']) || !isset($_SESSION['csv_headers'])) {
        echo json_encode(['success' => false, 'error' => 'No hay datos cargados. Sube el CSV primero.']);
        return;
    }

    $rawMapping = $_POST['mapping'] ?? '[]';
    $mapping = is_string($rawMapping) ? json_decode($rawMapping, true) : $rawMapping;
    if (empty($mapping)) {
        echo json_encode(['success' => false, 'error' => 'No se recibió el mapeo de columnas']);
        return;
    }

    // Verificar que todos los campos requeridos estén mapeados
    $required = ['telefono', 'nombreCliente', 'idCredito', 'proxPago'];
    foreach ($required as $campo) {
        if (empty($mapping[$campo])) {
            echo json_encode(['success' => false, 'error' => "El campo '$campo' no está mapeado"]);
            return;
        }
    }

    try {
        $dsn = "mysql:host=" . $_ENV['HOST'] . ";dbname=" . $_ENV['DB'] . ";charset=utf8mb4";
        $pdo = new PDO($dsn, $_ENV['USER'], $_ENV['PASSWORD']);
        $pdo->exec("SET time_zone = '-06:00'");

        $stmt = $pdo->prepare("INSERT INTO cola_mensajes (telefono, nombreCliente, idCredito, proxPago, estado) VALUES (?, ?, ?, ?, 'pendiente')");

        $insertados = 0;
        $errores = [];

        foreach ($_SESSION['csv_rows'] as $index => $row) {
            $telefono = preg_replace('/[^0-9]/', '', $row[$mapping['telefono']]);
            $nombre = trim($row[$mapping['nombreCliente']]);
            $idCredito = trim($row[$mapping['idCredito']]);
            $proxPago = trim($row[$mapping['proxPago']]);

            // Validaciones
            $erroresFila = [];

            if (strlen($telefono) !== 10) {
                $erroresFila[] = "Teléfono inválido ('$telefono') - debe tener 10 dígitos";
            }

            if (empty($nombre)) {
                $erroresFila[] = "Nombre del cliente vacío";
            }

            if (empty($idCredito)) {
                $erroresFila[] = "ID de crédito vacío";
            }

            if (empty($proxPago)) {
                $erroresFila[] = "Próximo pago vacío";
            }

            if (!empty($erroresFila)) {
                $errores[] = "Fila " . ($index + 2) . ": " . implode(', ', $erroresFila);
                continue;
            }

            try {
                $stmt->execute([$telefono, $nombre, $idCredito, $proxPago]);
                $insertados++;
            } catch (Exception $e) {
                $errores[] = "Fila " . ($index + 2) . ": Error al insertar - " . $e->getMessage();
            }
        }

        $totalProcesados = count($_SESSION['csv_rows']);

        // Limpiar sesión después de guardar
        unset($_SESSION['csv_headers'], $_SESSION['csv_rows']);

        echo json_encode([
            'success' => true,
            'insertados' => $insertados,
            'errores' => $errores,
            'total' => $totalProcesados
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
    }
}
