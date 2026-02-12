<?php

set_time_limit(0);
echo 'proceso ejecutado el: ' . date('Y-m-d H:i:s') . "\n";

date_default_timezone_set("America/Mexico_City");


include "config.php";

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$pdo = new PDO($dsn, $usr, $psd);
$pdo->exec("SET time_zone = '-06:00'");

$lote = 400;

for ($contador = 1; $contador <= 400; $contador++) {

    // Marcar como enviado
    $update = $pdo->prepare("INSERT INTO test_cron(comentario) values('prueba cron # $contador')");
    $update->execute();
    if ($contador % 10 == 0) {
        // Pausa tras cada mensaje    
        sleep(1);
    }
}
