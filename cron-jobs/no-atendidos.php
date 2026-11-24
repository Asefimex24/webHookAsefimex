<?php

// Se agrega visualización de errores para el cronjob
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Límite de ejecución
set_time_limit(0);

// Mostrar mensaje de inicio de proceso en cron-job
echo 'Proceso ejecutado el: ' . date('Y-m-d H:i:s') . "\n";

date_default_timezone_set("America/Mexico_City");

require_once __DIR__ . '/../getEnv.php';

// Validar que las variables de entorno se carguen correctamente
if (empty($_ENV['HOST']) || empty($_ENV['LABSMOBILE_USER']) || empty($_ENV['LABSMOBILE_TOKEN'])) {
    die("Error: Variables de entorno no cargadas en CLI.");
}

$dsn = "mysql:host=" . $_ENV['HOST'] . ";dbname=" . $_ENV['DB'] . ";charset=utf8mb4";
$pdo = new PDO($dsn, $_ENV['USERDB'], $_ENV['PASSWORD'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
$pdo->exec("SET time_zone = '-06:00'");

// Verificar límite diario
$hoy = date('Y-m-d');
$enviadosHoy = $pdo->query("SELECT COUNT(*) FROM no_atendidos WHERE estado='enviado' AND DATE(enviado_en) = '$hoy'")->fetchColumn();

if ($enviadosHoy >= 8000) {
    die("Límite diario de 10,000 mensajes alcanzado.\n");
}

// Tomar los siguientes 800 mensajes pendientes
$stmt = $pdo->query("SELECT * FROM no_atendidos WHERE estado = 'pendiente' ORDER BY idRegistro ASC LIMIT 800");
$lote = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($lote) {

    // Credenciales de LabsMobile desde .env
    $labsUser  = $_ENV['LABSMOBILE_USER'];  // Tu correo de cuenta LabsMobile
    $labsToken = $_ENV['LABSMOBILE_TOKEN']; // Tu Token API generado en el panel
    $authBase64 = base64_encode($labsUser . ':' . $labsToken);



    // Contador para controlar pausas cada 30 mensajes
    $contador = 1;

    foreach ($lote as $tarea) {

        $celularLimpio = preg_replace('/\D/', '', $tarea['telefono']);

        // Validar que el número tenga exactamente 10 dígitos
        if (mb_strlen($celularLimpio) == 10) {

            echo 'Telefono del prospecto: ' . $tarea['telefono'] . '<br>';

            try {
                // Para SMS en México el formato internacional es 52 + 10 dígitos (sin el 1)
                $destinatario = "52" . $celularLimpio;

                // Payload para la API JSON de LabsMobile
                $payload = [
                    "message" => 'Estimado ' . $tarea['nombreProspecto'] . ', cuentas con una solicitud de crédito en trámite con Asefimex. ¿Deseas continuar? da click en el siguiente enlace para confirmar: https://api.whatsapp.com/send?phone=529612550377&text=retomar+tramite',
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

                // Marcar como enviado en la cola de mensajes
                $update = $pdo->prepare("UPDATE no_atendidos SET estado = 'enviado', enviado_en = NOW() WHERE idRegistro = ?");
                $update->execute([$tarea['idRegistro']]);

                // Guardar historial (ajustado a +52 para SMS; cambia el nombre de la tabla si creaste una nueva para SMS)
                // $sql = "INSERT INTO mensajes_whatsapp (remitente, mensaje, direccion) VALUES (?, ?, 'saliente')";
                // $stmtInsert = $pdo->prepare($sql);
                // $stmtInsert->execute(['+52' . $celularLimpio, $messageBody]);
            } catch (Exception $e) {
                echo "Error al enviar SMS a " . $celularLimpio . ": " . $e->getMessage() . "\n";
                $update = $pdo->prepare("UPDATE no_atendidos SET estado = 'fallido', observación='Error al enviar' WHERE idRegistro = ?");
                $update->execute([$tarea['idRegistro']]);
            }
        } else {
            // Marcar como fallido por número inválido
            echo "Número inválido para el cliente ID " . $tarea['idRegistro'] . ": " . $tarea['telefono'] . "\n";
            $update = $pdo->prepare("UPDATE no_atendidos SET estado = 'fallido', enviado_en = NOW(), observacion='Número inválido' WHERE idRegistro = ?");
            $update->execute([$tarea['idRegistro']]);
            continue;
        }

        if ($contador % 50 == 0) {
            sleep(1);
        }

        $contador++;
    }

    echo "Lote de " . count($lote) . " mensajes procesado.\n";
} else {
    echo "No hay mensajes pendientes en la cola.\n";
}
