<?php
// Recibir datos de Twilio
$from = $_POST['From'] ?? '';
$body = $_POST['Body'] ?? '';
$sid = $_POST['MessageSid'] ?? '';

if (!empty($from) && !empty($body)) {
    $dsn = "mysql:host=localhost;dbname=webhookasefimex;charset=utf8";
    $pdo = new PDO($dsn, "root", "");

    $sql = "INSERT INTO mensajes_whatsapp (whatsapp_sid, remitente, mensaje) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sid, $from, $body]);
}

// Twilio espera una respuesta XML (aunque sea vacía)
header("Content-Type: text/xml");
echo "<Response></Response>";