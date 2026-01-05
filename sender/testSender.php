  <?php
  //ejemplo para mandar mensajes de recordatorio de pagos
date_default_timezone_set("America/Mexico_City");
require_once('../getEnv.php');
require_once '../vendor/twilio/sdk/src/Twilio/autoload.php';

use Twilio\Rest\Client;   

   //cargar los datos de cuenta twilio
    $sid = $_ENV['TWILIO_ACCOUNT_SID'];
    $token = $_ENV['TWILIO_AUTH_TOKEN'];
    $twilio = new Client($sid, $token);
 
     // Configuración de Twilio
  $plantillaVariables = [
                "1" => "Ever Lopez", // Variable {{1}}
                "2" => "123456789",     // Variable {{2}}
                "3" => "2026-01-05"       // Variable {{3}}
            ];

            try {
                $twilio->messages->create(
                    "whatsapp:+5219615792121", // Destinatario
                    [
                        "from" => "whatsapp:+5219612049936", // Tu número de Twilio
                        "contentSid" => "HXcdd5acc673aebc5833ddfc05bcb1354c", // Tu SID de plantilla
                        "contentVariables" => json_encode($plantillaVariables) // Variables de la plantilla
                    ]
                );


            } catch (Exception $e) {
                echo "Error al enviar mensaje a 5219615792121:" . $e->getMessage() . "\n";
            }