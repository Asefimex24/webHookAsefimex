<?php

/**
 * Script de prueba: envía UN mensaje de WhatsApp con la plantilla de "no atendidos"
 * (misma lógica que cron-jobs/no-atendidos-wtsp.php, con las variables {{1}} nombre y {{2}} teléfono).
 * No consulta ni modifica la base de datos.
 *
 * Uso CLI:      php test-wtsp.php [telefono10digitos] ["NOMBRE PROSPECTO"]
 * Uso browser:  test-wtsp.php?telefono=9615792121&nombre=EVER%20LOPEZ%20MARTINEZ
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set("America/Mexico_City");

require_once __DIR__ . '/getEnv.php';

use Twilio\Rest\Client;

$nl = PHP_SAPI === 'cli' ? "\n" : "<br>";

// Datos de prueba (se pueden sobreescribir por argumento o query string)
$telefono = $argv[1] ?? $_GET['telefono'] ?? '9615792121';
$nombre   = $argv[2] ?? $_GET['nombre']   ?? 'EVER LOPEZ MARTINEZ';

// Validar variables de entorno necesarias
foreach (['TWILIO_ACCOUNT_SID', 'TWILIO_AUTH_TOKEN', 'SID_TEMP_NO_ATENDIDOS'] as $var) {
    if (empty($_ENV[$var])) {
        die("Error: falta la variable de entorno $var" . $nl);
    }
}

$celularLimpio = preg_replace('/\D/', '', $telefono);
if (mb_strlen($celularLimpio) != 10) {
    die("Error: el teléfono debe tener 10 dígitos. Recibido: $telefono" . $nl);
}

$nombre       = str_replace('Ã‘', 'Ñ', $nombre);
$destinatario = "whatsapp:+521" . $celularLimpio;
$variables    = [
    "1" => $nombre,   // {{1}} nombre del prospecto
    "2" => $telefono, // {{2}} teléfono para la validación
];

echo "Enviando a: $destinatario" . $nl;
echo "Plantilla:  " . $_ENV['SID_TEMP_NO_ATENDIDOS'] . $nl;
echo "Variables:  " . json_encode($variables, JSON_UNESCAPED_UNICODE) . $nl;

try {
    $twilio = new Client($_ENV['TWILIO_ACCOUNT_SID'], $_ENV['TWILIO_AUTH_TOKEN']);

    $message = $twilio->messages->create(
        $destinatario,
        [
            "from"             => "whatsapp:+5219612049936",
            "contentSid"       => $_ENV['SID_TEMP_NO_ATENDIDOS'],
            "contentVariables" => json_encode($variables),
        ]
    );

    echo "Mensaje creado. SID: {$message->sid} | Estado inicial: {$message->status}" . $nl;

    // Consultar el estado unos segundos después para ver si fue entregado o falló
    sleep(5);
    $estado = $twilio->messages($message->sid)->fetch();
    echo "Estado tras 5 seg: {$estado->status}" . $nl;
    if ($estado->errorCode) {
        echo "Error Twilio {$estado->errorCode}: {$estado->errorMessage}" . $nl;
    }

    if (!empty($_ENV['CONTENT_BODY_NO_ATENDIDOS'])) {
        echo "Cuerpo que se guardaría en historial: " . $_ENV['CONTENT_BODY_NO_ATENDIDOS'] . $nl;
    }
} catch (Exception $e) {
    echo "Error al enviar WhatsApp: " . $e->getMessage() . $nl;
}
