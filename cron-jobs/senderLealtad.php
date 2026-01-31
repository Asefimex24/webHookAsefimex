<?php

//script que permite enviar mensajes del programa de de lealtad a los clientes
echo 'proceso ejecutado el: ' . date('Y-m-d H:i:s') . "\n";

date_default_timezone_set("America/Mexico_City");

require_once __DIR__ . '/twilio/sdk/src/Twilio/autoload.php';

use Twilio\Rest\Client;

$dsn = "mysql:host=localhost;dbname=myDb;charset=utf8mb4";
$pdo = new PDO($dsn, 'usr', 'psd');
$pdo->exec("SET time_zone = '-06:00'");

// 1. Verificar límite diario (opcional pero recomendado)
$hoy = date('Y-m-d');

$enviadosHoy = $pdo->query("SELECT COUNT(*) FROM mensajes_lealtad WHERE estado='enviado' AND DATE(enviado_en) = '$hoy'")->fetchColumn();

if ($enviadosHoy >= 250) {
    die("Límite diario de 250 mensajes alcanzado.");
}

// 2. Tomar los siguientes 10 mensajes pendientes
$stmt = $pdo->query("SELECT * FROM mensajes_lealtad WHERE estado = 'pendiente' ORDER BY idCliente ASC LIMIT 25");
$lote = $stmt->fetchAll();

if ($lote) {

    //cargar los datos de cuenta twilio      
    $sid = '';
    $token = '';
    $twilio = new Client($sid, $token);

    foreach ($lote as $tarea) {

        // Validar que el número tenga exactamente 10 dígitos
        if (mb_strlen($tarea['celular']) == 10) {

            //revisar la plantilla de twilio ya que no se pudo enviar el primer mensaje
            try {
                //generar y eniar el mensaje de whatsapp usando la plantilla de twilio
                $twilio->messages->create(
                    "whatsapp:+521" . $tarea['celular'], // Destinatario
                    [
                        "from" => "whatsapp:+5219612049936", // Tu número de Twilio
                        "contentSid" => "HX8c0833fef880016ad2637f2169a13959", // Tu SID de plantilla de contenido
                    ]
                );


                // Marcar como enviado en la cola de mensajes
                $update = $pdo->prepare("UPDATE mensajes_lealtad SET estado = 'enviado', enviado_en ='" . $hoy . "' WHERE idCliente = ?");
                $update->execute([$tarea['idCliente']]);



                
                //componer el mensaje para guardar en la tabla de mensajes_whatsapp
                $messageBody = 'En Asefimex Financiera, tu bienestar y el de tu familia son nuestra prioridad. Como beneficio especial por ser parte de nuestra comunidad, te otorgamos una membresía de salud sin costo por 1 mes.
¿Qué incluye tu beneficio?
- Orientación médica, psicológica, pediátrica y nutricional telefónica.
- Disponibilidad 24/7.
- Sin cargos adicionales ni compromiso de contratación posterior.
¿Cómo activarlo? Llama sin costo al 800 099 2020 e ingresa tu folio de acceso: ME250101.
Información Legal y Contacto:
ASESORÍA FINANCIERA DE MÉXICO, S.A. DE .C.V. SOFOM ENR, Avenida central entre 13 y 14 orientes 
Atención a clientes: https://asefimex.com
Consulta nuestro Aviso de Privacidad en https://asefimex.com/aviso/. 
Si no deseas recibir más mensajes promocionales de nuestra parte, responde con la palabra "BAJA"';


                //guardar en tabla de mensajes_whatsapp para poder ver el historial de mensajes que responden los clientes.
                $sql = "INSERT INTO mensajes_whatsapp (remitente, mensaje, direccion) VALUES (?, ?, 'saliente')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['+521' . $tarea['celular'], $messageBody]);
            } catch (Exception $e) {

                //si hay un error al enviar el mensaje, marcar como fallido
                echo "Error al enviar mensaje a " . $tarea['celular'] . ": " . $e->getMessage() . "\n";
                $update = $pdo->prepare("UPDATE mensajes_lealtad SET estado = 'fallido' WHERE idCliente = ?");
                $update->execute([$tarea['idCliente']]);
            }
        } else {

            // Marcar como fallido por número inválido
            echo "Número inválido para el cliente ID " . $tarea['idCliente'] . ": " . $tarea['celular'] . "\n";
            $update = $pdo->prepare("UPDATE mensajes_lealtad SET estado = 'fallido', enviado_en = NOW() WHERE idCliente = ?");
            $update->execute([$tarea['idCliente']]);
            continue;
        }
    }

    echo "Lote de " . count($lote) . " mensajes procesado.";
} else {
    echo "No hay mensajes pendientes en la cola.";
}
