<?php
require_once('getEnv.php');

// Recibir datos de Twilio
$from = $_POST['From'] ?? '';
$body = $_POST['Body'] ?? '';
$sid = $_POST['MessageSid'] ?? '';

if (!empty($from) && !empty($body)) {
    $dsn = "mysql:host=" . $_ENV['HOST'] . ";dbname=" . $_ENV['DB'] . ";charset=utf8";
    $pdo = new PDO($dsn, $_ENV['USER'], $_ENV['PASSWORD']);

    $sql = "INSERT INTO mensajes_whatsapp (whatsapp_sid, remitente, mensaje) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sid, $from, $body]);
}

// Twilio espera una respuesta XML (aunque sea vacía)
header("Content-Type: text/xml");
echo "<Response></Response>";