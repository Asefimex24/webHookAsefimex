<?php
//script para realizar envio de notificaciones de recordatorios de pago a los clientes
//mediante whatsapp usando plantillas de twilio
//ejecutar este script cada 10 minutos mediante cron job

date_default_timezone_set("America/Mexico_City");
require_once 'twilio/sdk/src/Twilio/autoload.php';

use Twilio\Rest\Client;

$dsn = "mysql:host=localhost;dbname=u547829635_wbhMensajeriaW;charset=utf8mb4";
$pdo = new PDO($dsn, 'u547829635_asefimex_msj', '@S3f1m3x2025Hook');
$pdo->exec("SET time_zone = '-06:00'");

// 1. Verificar límite diario (opcional pero recomendado)
$hoy = date('Y-m-d');

$enviadosHoy = $pdo->query("SELECT COUNT(*) FROM cola_mensajes WHERE estado='enviado' AND DATE(enviado_en) = '$hoy'")->fetchColumn();

if ($enviadosHoy >= 250) {
    die("Límite diario de 250 mensajes alcanzado.");
}

// 2. Tomar los siguientes 100 mensajes pendientes
$stmt = $pdo->query("SELECT * FROM cola_mensajes WHERE estado = 'pendiente' ORDER BY id ASC LIMIT 20");
$lote = $stmt->fetchAll();

if ($lote) {

    //cargar los datos de cuenta twilio
    $sid = $_ENV['AC85d0cf9fe68f8fd08c96341d4553ea51'];
    $token = $_ENV['cbaa3d4fe2d48ea341bee2fceb78fbc2'];
    $twilio = new Client($sid, $token);

    foreach ($lote as $tarea) {

        // Validar que el número tenga exactamente 10 dígitos
        if (mb_strlen($tarea['telefono'], "UTF-8") == 10) {

            // Mapeamos tus columnas a las variables de la plantilla {{1}}, {{2}}, {{3}}
            $plantillaVariables = [
                "1" => $tarea['nombreCliente'], // Variable {{1}}
                "2" => $tarea['idCredito'],     // Variable {{2}}
                "3" => $tarea['proxPago']       // Variable {{3}}
            ];

            try {
                $twilio->messages->create(
                    "whatsapp:+521" . $tarea['telefono'], // Destinatario
                    [
                        "from" => "whatsapp:+5219612049936", // Tu número de Twilio
                        "contentSid" => "HXcdd5acc673aebc5833ddfc05bcb1354c", // Tu SID de plantilla
                        "contentVariables" => json_encode($plantillaVariables) // Variables de la plantilla
                    ]
                );

                // Marcar como enviado
                $update = $pdo->prepare("UPDATE cola_mensajes SET estado = 'enviado', enviado_en = NOW() WHERE id = ?");
                $update->execute([$tarea['id']]);

                //estructura del mensaje para guardar en la tabla de mensajes_whatsapp
                $messageBody = 'Estimado(a) ' . $tarea['nombreCliente'] . ', Asefimex le informa que el pago de su crédito #' . $tarea['idCredito'] . ' vence el próximo ' . $tarea['proxPago'] . '.
                                Le sugerimos realizar su pago puntualmente para mantener sus beneficios. Una vez realizado, 
                                por favor envíe su comprobante a su ejecutivo asignado. ¡Gracias!';

                //guardar en tabla de mensajes_whatsapp para poder ver el historial de mensajes que responden los clientes.
                $sql = "INSERT INTO mensajes_whatsapp (remitente, mensaje, direccion) VALUES (?, ?, 'saliente')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['+521' . $tarea['telefono'], $messageBody]);
                
            } catch (Exception $e) {
                // En caso de error, marcar como fallido
                $update = $pdo->prepare("UPDATE cola_mensajes SET estado = 'fallido' WHERE id = ?");
                $update->execute([$tarea['id']]);
            }
        } else {
            // Marcar como fallido por número inválido
            $update = $pdo->prepare("UPDATE cola_mensajes SET estado = 'fallido', enviado_en = NOW() WHERE id = ?");
            $update->execute([$tarea['id']]);
            continue;
        }
            sleep(1); // Pausa tras cada mensaje
    }

    echo "Lote de " . count($lote) . " mensajes procesado.";
} else {
    echo "No hay mensajes pendientes en la cola.";
}
