<?php


date_default_timezone_set("America/Mexico_City");

//se agrega visualización de errores para el cronjob
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//liminte de ejecucion
set_time_limit(0);
//mostrar mensaje de inicio de proceso en cron-job
echo 'proceso ejecutado el: ' . date('Y-m-d H:i:s') . "\n";


require_once __DIR__ . '/../getEnv.php';

use Twilio\Rest\Client;

//validar que las variables de entorno se carguen correctamente.
$variablesRequeridas = [
    'HOST',
    'DB',
    'USERDB',
    'TWILIO_ACCOUNT_SID',
    'TWILIO_AUTH_TOKEN',
    'TWILIO_WHATSAPP_NUMBER',
    'SID_TEMP_PROMOCION',
    'CONTENT_BODY_PROMOCION',
];

foreach ($variablesRequeridas as $variable) {
    if (empty($_ENV[$variable])) {
        die("Error: la variable de entorno '$variable' no está definida o está vacía.\n");
    }
}


$dsn = "mysql:host=" . $_ENV['HOST'] . ";dbname=" . $_ENV['DB'] . ";charset=utf8mb4";
$pdo = new PDO($dsn, $_ENV['USERDB'], $_ENV['PASSWORD']);
$pdo->exec("SET time_zone = '-06:00'");

// Verificar límite diario (opcional pero recomendado)
$hoy = date('Y-m-d');

$enviadosHoy = $pdo->query("SELECT COUNT(*) FROM promocion WHERE estado='enviado' AND DATE(enviado_en) = '$hoy'")->fetchColumn();

if ($enviadosHoy >= 10000) {
    die("Límite diario de 10,000 mensajes alcanzado.");
}

// Tomar los siguientes 800 mensajes pendientes
$stmt = $pdo->query("SELECT * FROM promocion WHERE estado = 'pendiente' ORDER BY idRegistro ASC LIMIT 800");
$lote = $stmt->fetchAll();


if ($lote) {

    //cargar los datos de cuenta twilio
    $sid = $_ENV['TWILIO_ACCOUNT_SID'];
    $token = $_ENV['TWILIO_AUTH_TOKEN'];
    $twilio = new Client($sid, $token);

    //contador para validar el envio de 30 mensajes y poner delay de 1 seg.
    $contador = 1;

    foreach ($lote as $tarea) {

        // Validar que el número tenga exactamente 10 dígitos
        if (mb_strlen($tarea['celular']) == 10) {

            //revisar la plantilla de twilio ya que no se pudo enviar el primer mensaje
            try {
                //generar y enviar el mensaje de whatsapp usando la plantilla de twilio
                $twilio->messages->create(
                    "whatsapp:+521" . $tarea['celular'], // Destinatario
                    [
                        "from" => "whatsapp:" . $_ENV['TWILIO_WHATSAPP_NUMBER'], // Tu número de Twilio
                        "contentSid" => $_ENV['SID_TEMP_PROMOCION'], // Tu SID de plantilla de contenido
                    ]
                );


                // Marcar como enviado en la cola de mensajes
                $update = $pdo->prepare("UPDATE promocion SET estado = 'enviado', enviado_en = NOW() WHERE idRegistro = ?");
                $update->execute([$tarea['idRegistro']]);

                //componer el mensaje para guardar en la tabla de mensajes_whatsapp
                $messageBody = $_ENV['CONTENT_BODY_PROMOCION'];


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



        if ($contador % 30 == 0) {
            // Pausa tras cada 30 mensajes    
            sleep(1);
        }

        $contador++;
    }

    echo "Lote de " . count($lote) . " mensajes procesado.";
} else {
    echo "No hay mensajes pendientes en la cola.";
}
