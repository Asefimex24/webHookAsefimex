<?php
date_default_timezone_set("America/Mexico_City");
require_once('../getEnv.php');
require_once '../vendor/twilio/sdk/src/Twilio/autoload.php';

use Twilio\Rest\Client;

$dsn = "mysql:host=" . $_ENV['HOST'] . ";dbname=" . $_ENV['DB'] . ";charset=utf8mb4";
$pdo = new PDO($dsn, $_ENV['USER'], $_ENV['PASSWORD']);
$pdo->exec("SET time_zone = '-06:00'");
// 1. Verificar límite diario (opcional pero recomendado)
$hoy = date('Y-m-d');

$enviadosHoy = $pdo->query("SELECT COUNT(*) FROM cola_mensajes WHERE estado='enviado' AND DATE(enviado_en) = '$hoy'")->fetchColumn();

if ($enviadosHoy >= 250) {
    die("Límite diario de 250 mensajes alcanzado.");
}

// 2. Tomar los siguientes 100 mensajes pendientes
$stmt = $pdo->query("SELECT * FROM cola_mensajes WHERE estado = 'pendiente' ORDER BY id ASC LIMIT 250");
$lote = $stmt->fetchAll();

if ($lote) {

    //cargar los datos de cuenta twilio
    $sid = $_ENV['TWILIO_ACCOUNT_SID'];
    $token = $_ENV['TWILIO_AUTH_TOKEN'];
    $twilio = new Client($sid, $token);

    $contador = 0;

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

                //guardar en tabla de mensajes_whatsapp para poder ver el historial de mensajes que responden los clientes.
                $messageBody = 'Estimado(a) ' . $tarea['nombreCliente'] . ', Asefimex le informa que el pago de su crédito #' . $tarea['idCredito'] . ' vence el próximo ' . $tarea['proxPago'] . '.
                                Le sugerimos realizar su pago puntualmente para mantener sus beneficios. Una vez realizado, 
                                por favor envíe su comprobante a su ejecutivo asignado. ¡Gracias!';

                $sql = "INSERT INTO mensajes_whatsapp (remitente, mensaje, direccion) VALUES (?, ?, 'saliente')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['+521' . $tarea['telefono'], $messageBody]);
                
            } catch (Exception $e) {
                $update = $pdo->prepare("UPDATE cola_mensajes SET estado = 'fallido' WHERE id = ?");
                $update->execute([$tarea['id']]);
            }
        } else {
            // Marcar como fallido por número inválido
            $update = $pdo->prepare("UPDATE cola_mensajes SET estado = 'fallido', enviado_en = NOW() WHERE id = ?");
            $update->execute([$tarea['id']]);
            continue;
        }
        //aumentar el contador
        $contador++;
        if ($contador % 20 == 0) {
            sleep(1); // Pausa tras cada 20 elementos
        }
    }

    echo "Lote de " . count($lote) . " mensajes procesado.";
} else {
    echo "No hay mensajes pendientes en la cola.";
}
