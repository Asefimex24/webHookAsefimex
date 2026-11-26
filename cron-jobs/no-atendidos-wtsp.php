<?php

// Se agrega visualización de errores para el cronjob
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Límite de ejecución
set_time_limit(0);

// Mostrar mensaje de inicio de proceso en cron-job
echo 'Proceso ejecutado el: ' . date('Y-m-d H:i:s') . "\n";

date_default_timezone_set("America/Mexico_City");

require_once __DIR__ . '/../getEnv.php';

use Twilio\Rest\Client;

// Validar que las variables de entorno se carguen correctamente
if (empty($_ENV['HOST']) || empty($_ENV['TWILIO_ACCOUNT_SID']) || empty($_ENV['TWILIO_AUTH_TOKEN'])) {
    die("Error: Variables de entorno no cargadas en CLI.");
}

$dsn = "mysql:host=" . $_ENV['HOST'] . ";dbname=" . $_ENV['DB'] . ";charset=utf8mb4";
$pdo = new PDO($dsn, $_ENV['USERDB'], $_ENV['PASSWORD'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
$pdo->exec("SET time_zone = '-06:00'");

// Verificar límite diario
$hoy = date('Y-m-d');
$enviadosHoy = $pdo->query("SELECT COUNT(*) FROM no_atendidos WHERE estado='enviado' AND DATE(enviado_en) = '$hoy'")->fetchColumn();

if ($enviadosHoy >= 8000) {
    die("Límite diario de 8,000 mensajes alcanzado.\n");
}

// Tomar los siguientes 800 mensajes pendientes
$stmt = $pdo->query("SELECT * FROM no_atendidos WHERE estado = 'pendiente' ORDER BY idRegistro ASC LIMIT 800");
$lote = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($lote) {

    // Cargar los datos de cuenta Twilio desde .env
    $sid    = $_ENV['TWILIO_ACCOUNT_SID'];
    $token  = $_ENV['TWILIO_AUTH_TOKEN'];
    $twilio = new Client($sid, $token);

    // SID de la plantilla aprobada en Twilio para este mensaje
    $contentSid = $_ENV['SID_TEMP_NO_ATENDIDOS']; // <-- Reemplazar con el SID de tu plantilla

    // Contador para controlar pausas cada 50 mensajes
    $contador = 1;

    foreach ($lote as $tarea) {

        $celularLimpio = preg_replace('/\D/', '', $tarea['telefono']);

        // Validar que el número tenga exactamente 10 dígitos
        if (mb_strlen($celularLimpio) == 10) {

            echo 'Telefono del prospecto: ' . $tarea['telefono'] . '<br>';

            try {
                // Para WhatsApp en México el formato internacional es whatsapp:+521 + 10 dígitos
                $destinatario = "whatsapp:+521" . $celularLimpio;
                $nombre = str_replace('Ã‘', 'Ñ', $tarea['nombreProspecto']);

                // Enviar mensaje de WhatsApp usando plantilla de Twilio
                $twilio->messages->create(
                    $destinatario,
                    [
                        "from" => "whatsapp:+5219612049936",
                        "contentSid" => $contentSid,
                        "contentVariables" => json_encode([
                            "1" => $nombre // Corresponde a {{1}} en tu plantilla de Twilio
                        ])
                    ]
                );

                // Marcar como enviado en la cola de mensajes
                $update = $pdo->prepare("UPDATE no_atendidos SET estado = 'enviado', enviado_en = NOW() WHERE idRegistro = ?");
                $update->execute([$tarea['idRegistro']]);

                // Guardar historial (descomentar si deseas registrar en mensajes_whatsapp)
                // $messageBody = 'Estimado ' . $nombre . ', cuentas con una solicitud de crédito en trámite con Asefimex. ¿Deseas continuar? da click en el siguiente enlace para confirmar: https://api.whatsapp.com/send?phone=529612550377&text=retomar+tramite';
                // $sql = "INSERT INTO mensajes_whatsapp (remitente, mensaje, direccion) VALUES (?, ?, 'saliente')";
                // $stmtInsert = $pdo->prepare($sql);
                // $stmtInsert->execute(['+521' . $celularLimpio, $messageBody]);

            } catch (Exception $e) {
                echo "Error al enviar WhatsApp a " . $celularLimpio . ": " . $e->getMessage() . "\n";
                $update = $pdo->prepare("UPDATE no_atendidos SET estado = 'fallido', observacion='Error al enviar' WHERE idRegistro = ?");
                $update->execute([$tarea['idRegistro']]);
            }
        } else {
            // Marcar como fallido por número inválido
            echo "Número inválido para el cliente ID " . $tarea['idRegistro'] . ": " . $tarea['telefono'] . "\n";
            $update = $pdo->prepare("UPDATE no_atendidos SET estado = 'fallido', enviado_en = NOW(), observacion='Número inválido' WHERE idRegistro = ?");
            $update->execute([$tarea['idRegistro']]);
            continue;
        }

        if ($contador % 50 == 0) {
            sleep(1);
        }

        $contador++;
    }

    echo "Lote de " . count($lote) . " mensajes procesado.\n";
} else {
    echo "No hay mensajes pendientes en la cola.\n";
}
