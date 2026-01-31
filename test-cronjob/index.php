<?php

//script que permite enviar mensajes del programa de de lealtad a los clientes
echo 'proceso ejecutado el: ' . date('Y-m-d H:i:s') . "\n";

date_default_timezone_set("America/Mexico_City");


$dsn = "mysql:host=localhost;dbname=u547829635_wbhMensajeriaW;charset=utf8mb4";
$pdo = new PDO($dsn, 'u547829635_asefimex_msj', '@S3f1m3x2025Hook');
$pdo->exec("SET time_zone = '-06:00'");


for($i=0; $i<100; $i++){

  $guardar = $pdo->query("INSERT INTO test_cron(comentario) values('comentario de prueba $i')");
  $guardar->execute();
    // Simular envío
    sleep(1);
}


