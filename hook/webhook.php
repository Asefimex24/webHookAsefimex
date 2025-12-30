<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set("America/Mexico_City");

require "../getEnv.php";

// Recibir datos de Twilio
$from = $_POST['From'] ?? '';
$body = $_POST['Body'] ?? '';
$sid = $_POST['MessageSid'] ?? ''; 

if($from!=""){

    $from = substr($from,9);
}

if (!empty($from) && !empty($body)) {

    $dsn = "mysql:host=" . $_ENV['HOST'] . ";dbname=" . $_ENV['DB'] . ";charset=utf8mb4";
    $pdo = new PDO($dsn, $_ENV['USER'], $_ENV['PASSWORD']);
    $pdo->exec("SET time_zone = '-06:00'");

    $sql = "INSERT INTO mensajes_whatsapp (whatsapp_sid, remitente, mensaje) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sid, $from, $body]);
}

// Twilio espera una respuesta XML (aunque sea vacía)
header("Content-Type: text/xml");
echo "<Response></Response>";