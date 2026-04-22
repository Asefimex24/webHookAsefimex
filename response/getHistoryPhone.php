<?php
// Configuración de la base de datos
date_default_timezone_set("America/Mexico_City");
require_once '../getEnv.php';

// Encabezado para que el navegador sepa que enviamos un JSON
header('Content-Type: application/json');

try {
    $dsn = "mysql:host=" . $_ENV['HOST'] . ";dbname=" . $_ENV['DB'] . ";charset=utf8mb4";
    $pdo = new PDO($dsn, $_ENV['USER'], $_ENV['PASSWORD'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Para que no duplique datos con índices numéricos
    ]);

    $phone = isset($_GET['phone']) ? $_GET['phone'] : '';

    if ($phone) {
        
        // Solo una consulta, seleccionando lo que necesitas
        $stmt = $pdo->prepare("SELECT mensaje, fecha_recibido, direccion FROM mensajes_whatsapp WHERE remitente = ? ORDER BY fecha_recibido ASC");
        $stmt->execute([$phone]);
        $rows = $stmt->fetchAll();

        $messages = [];
        foreach ($rows as $row) {
            $messages[] = [
                'text' => $row['mensaje'],
                // Formateamos la hora aquí para que el JS solo la imprima
                'time' => date('d-m-Y: H:i', strtotime($row['fecha_recibido'])),
                // Mapeamos 'direccion' a lo que espera tu CSS (msg-sent o msg-received)
                // Suponiendo que 'direccion' en tu BD es 'saliente'/'entrante' o similar
                'type' => $row['direccion']
            ];
        }

        echo json_encode($messages);
    } else {
        echo json_encode(['error' => 'No se proporcionó teléfono']);
    }
} catch (PDOException $e) {
    // Si hay error de base de datos, enviarlo como JSON también
    echo json_encode(['error' => $e->getMessage()]);
}