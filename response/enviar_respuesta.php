<?php
date_default_timezone_set("America/Mexico_City");
require_once 'vendor/autoload.php';
use Twilio\Rest\Client;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $to = $_POST['to'];
    $messageBody = $_POST['message'];

    $sid = "TU_ACCOUNT_SID";
    $token = "TU_AUTH_TOKEN";
    $twilio = new Client($sid, $token);

    $twilio->messages->create($to, [
        "from" => "whatsapp:+14155238886",
        "body" => $messageBody
    ]);

    header("Location: index.php?enviado=1");
}