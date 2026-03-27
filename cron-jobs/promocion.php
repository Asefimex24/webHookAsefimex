<?php

//script que permite enviar mensajes del programa de de lealtad a los clientes
set_time_limit(0);
echo 'proceso ejecutado el: ' . date('Y-m-d H:i:s') . "\n";

date_default_timezone_set("America/Mexico_City");

require_once __DIR__ . '/twilio/sdk/src/Twilio/autoload.php';

use Twilio\Rest\Client;
include "config.php";


$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$pdo = new PDO($dsn, $usr, $psd);
$pdo->exec("SET time_zone = '-06:00'");

// Verificar límite diario (opcional pero recomendado)
$hoy = date('Y-m-d');

$enviadosHoy = $pdo->query("SELECT COUNT(*) FROM promocion WHERE estado='enviado' AND DATE(enviado_en) = '$hoy'")->fetchColumn();

if ($enviadosHoy >= 4000) {
    die("Límite diario de 4,000 mensajes alcanzado.");
}

// Tomar los siguientes 800 mensajes pendientes
$stmt = $pdo->query("SELECT * FROM promocion WHERE estado = 'pendiente' ORDER BY idRegistro ASC LIMIT 800");
$lote = $stmt->fetchAll();



if ($lote) {

    //cargar los datos de cuenta twilio
    $sid = $sidtw;
    $token = $tokentw;
    $twilio = new Client($sid, $token);

    //contador para validar el envio de 30 mensajes y poner delay de 1 seg.
    $contador=1;

    foreach ($lote as $tarea) {

        // Validar que el número tenga exactamente 10 dígitos
        if (mb_strlen($tarea['celular']) == 10) {

            //revisar la plantilla de twilio ya que no se pudo enviar el primer mensaje
            try {
                //generar y enviar el mensaje de whatsapp usando la plantilla de twilio
                $twilio->messages->create(
                    "whatsapp:+521" . $tarea['celular'], // Destinatario
                    [
                        "from" => "whatsapp:+5219612049936", // Tu número de Twilio
                        "contentSid" => "HX00862e317c53ece6070b4f0e822b09f1", // Tu SID de plantilla de contenido
                    ]
                );


                // Marcar como enviado en la cola de mensajes
                $update = $pdo->prepare("UPDATE promocion SET estado = 'enviado', enviado_en = NOW() WHERE idRegistro = ?");
                $update->execute([$tarea['idRegistro']]);



                
                //componer el mensaje para guardar en la tabla de mensajes_whatsapp
                $messageBody = '¡Beneficio Especia para tíl!
Realiza tu pago del 27 al 30 de marzo de 2026 y participa:

Sorteo de $12,000 que será abonado directamente a tu crédito.
Fecha del sorteo 10 de Abril 2026.

Es tu oportunidad de avanzar más rápido, reducir tu saldo y seguir creciendo con nosotros. 
No dejes pasar esta oportunidad, ponte al corriente y participa.
¡Haz tu pago dentro de las fechas y asegura tu lugar en el sorteo!';


                //guardar en tabla de mensajes_whatsapp para poder ver el historial de mensajes que responden los clientes.
                $sql = "INSERT INTO mensajes_whatsapp (remitente, mensaje, direccion) VALUES (?, ?, 'saliente')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['+521' . $tarea['celular'], $messageBody]);
            } catch (Exception $e) {

                //si hay un error al enviar el mensaje, marcar como fallido
                echo "Error al enviar mensaje a " . $tarea['celular'] . ": " . $e->getMessage() . "\n";
                $update = $pdo->prepare("UPDATE promocion SET estado = 'fallido' WHERE idRegistro = ?");
                $update->execute([$tarea['idRegistro']]);
            }
        } else {

            // Marcar como fallido por número inválido
            echo "Número inválido para el cliente ID " . $tarea['idRegistro'] . ": " . $tarea['celular'] . "\n";
            $update = $pdo->prepare("UPDATE promocion SET estado = 'fallido', enviado_en = NOW() WHERE idRegistro = ?");
            $update->execute([$tarea['idRegistro']]);
            continue;
        }


                
        if($contador%30==0){        
            // Pausa tras cada 30 mensajes    
            sleep(1); 
        }

        $contador++;
    }

    echo "Lote de " . count($lote) . " mensajes procesado.";
} else {
    echo "No hay mensajes pendientes en la cola.";
}
