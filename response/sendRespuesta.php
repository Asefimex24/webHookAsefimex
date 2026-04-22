<?php
date_default_timezone_set("America/Mexico_City");
require_once '../getEnv.php';
require_once './vendor/twilio/sdk/src/Twilio/autoload.php';

use Twilio\Rest\Client;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $to = $_POST['to']; // Ejemplo: whatsapp:+521...
    $messageBody = $_POST['message'];

    // Enviar vía Twilio
    $sid = "TU_ACCOUNT_SID";
    $token = "TU_AUTH_TOKEN";
    $twilio = new Client($sid, $token);

    try {
        $twilio->messages->create($to, [
            "from" => "whatsapp:+TU_NUMERO_TWILIO",
            "body" => $messageBody
        ]);

        // Guardar en Base de Datos como 'saliente'
        $dsn = "mysql:host=" . $_ENV['HOST'] . ";dbname=" . $_ENV['DB'] . ";charset=utf8";
        $pdo = new PDO($dsn, $_ENV['USER'], $_ENV['PASSWORD']);
        $pdo->exec("SET time_zone = '-06:00'");

        $sql = "INSERT INTO mensajes_whatsapp (remitente, mensaje, direccion) VALUES (?, ?, 'saliente')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$to, $messageBody]);

        header("Location: index.php?status=enviado");
    } catch (Exception $e) {
        echo "Error al enviar: " . $e->getMessage();
    }
}
