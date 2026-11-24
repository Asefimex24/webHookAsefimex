<?php

date_default_timezone_set("America/Mexico_City");
require_once __DIR__ . '/getEnv.php';


// Credenciales de LabsMobile desde .env
$labsUser  = $_ENV['LABSMOBILE_USER'];  // Tu correo de cuenta LabsMobile
$labsToken = $_ENV['LABSMOBILE_TOKEN']; // Tu Token API generado en el panel
$authBase64 = base64_encode($labsUser . ':' . $labsToken);


// Para SMS en México el formato internacional es 52 + 10 dígitos (sin el 1)
$destinatario = "529615792121";

// Payload para la API JSON de LabsMobile   <a href="https://www.ejemplo.com">este enlace</a>.
$payload = [
    "message" => 'Estimado ' . 'EVER LOPEZ MARTINEZ' . ', cuentas con una solicitud de crédito en trámite con Asefimex. ¿Deseas continuar? da click en el siguiente enlace para confirmar: https://api.whatsapp.com/send?phone=529612550377&text=retomar+tramite',
    "tpoa"    => "Sender", // Remitente (en México suele sobreescribirse por número corto local)
    "ucs2"    => 0,        // 0 = GSM estándar (160 chars), 1 = Unicode con acentos/emojis (70 chars)
    "recipient" => [
        [
            "msisdn" => $destinatario
        ]
    ]
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => "https://api.labsmobile.com/json/send",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => "",
    CURLOPT_MAXREDIRS      => 10,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST  => "POST",
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        "Authorization: Basic " . $authBase64,
        "Cache-Control: no-cache",
        "Content-Type: application/json"
    ],
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

if ($curlError) {
    throw new Exception("Error cURL: " . $curlError);
}

$result = json_decode($response, true);

// LabsMobile responde con code "0" cuando el SMS fue aceptado
if ($httpCode !== 200 || !isset($result['code']) || $result['code'] !== "0") {
    $errorMsg = $result['message'] ?? $response;
    throw new Exception("API LabsMobile Error (" . ($result['code'] ?? $httpCode) . "): " . $errorMsg);
}
