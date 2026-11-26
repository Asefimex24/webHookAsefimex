<?php

/**
 * Prueba de la plantilla de WhatsApp de no-atendidos.
 * Envía UN solo mensaje al número indicado. No lee ni modifica la tabla no_atendidos.
 *
 * Uso (CLI):
 *   php cron-jobs/test-no-atendidos-wtsp.php 9611234567 "JUAN PEÑA"
 *
 * Uso (navegador):
 *   /cron-jobs/test-no-atendidos-wtsp.php?telefono=9611234567&nombre=JUAN%20PEÑA
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set("America/Mexico_City");

require_once __DIR__ . '/../getEnv.php';

use Twilio\Rest\Client;

$esCli = php_sapi_name() === 'cli';
if (!$esCli) {
    header('Content-Type: text/plain; charset=utf-8');
}

// Parámetros: teléfono y nombre
$telefono = $esCli ? ($argv[1] ?? '') : ($_GET['telefono'] ?? '');
$nombre   = $esCli ? ($argv[2] ?? '') : ($_GET['nombre'] ?? '');

if ($telefono === '') {
    die("Uso: php test-no-atendidos-wtsp.php <telefono_10_digitos> \"<nombre>\"\n");
}

// Validar variables de entorno
foreach (['TWILIO_ACCOUNT_SID', 'TWILIO_AUTH_TOKEN', 'SID_TEMP_NO_ATENDIDOS'] as $var) {
    if (empty($_ENV[$var])) {
        die("Error: falta la variable $var en el .env\n");
    }
}

// Mismo tratamiento que el script real
$celularLimpio = preg_replace('/\D/', '', $telefono);
if (strlen($celularLimpio) != 10) {
    die("Error: el teléfono debe tener 10 dígitos (recibido: '$telefono' -> '$celularLimpio')\n");
}

$nombre = str_replace('Ã‘', 'Ñ', trim($nombre));
if ($nombre === '') {
    $nombre = 'cliente';
}

$destinatario = "whatsapp:+521" . $celularLimpio;
$contentSid   = $_ENV['SID_TEMP_NO_ATENDIDOS'];

echo "Enviando prueba...\n";
echo "  Destinatario: $destinatario\n";
echo "  Plantilla:    $contentSid\n";
echo "  {{1}}:        $nombre\n\n";

try {
    $twilio = new Client($_ENV['TWILIO_ACCOUNT_SID'], $_ENV['TWILIO_AUTH_TOKEN']);

    $mensaje = $twilio->messages->create(
        $destinatario,
        [
            "from"             => "whatsapp:+5219612049936",
            "contentSid"       => $contentSid,
            "contentVariables" => json_encode(["1" => $nombre]),
        ]
    );

    echo "Twilio aceptó el mensaje.\n";
    echo "  SID:    {$mensaje->sid}\n";
    echo "  Estado: {$mensaje->status}\n\n";

    // Esperar unos segundos y consultar el estado real de entrega
    sleep(5);
    $estado = $twilio->messages($mensaje->sid)->fetch();

    echo "Estado después de 5 segundos: {$estado->status}\n";
    if ($estado->errorCode) {
        echo "  Código de error: {$estado->errorCode}\n";
        echo "  Detalle:         {$estado->errorMessage}\n";
        echo "  Más info:        https://www.twilio.com/docs/api/errors/{$estado->errorCode}\n";
    }
} catch (Exception $e) {
    echo "Error al enviar: " . $e->getMessage() . "\n";
}
