<?php

set_time_limit(0);
echo 'proceso ejecutado el: ' . date('Y-m-d H:i:s') . "\n";

date_default_timezone_set("America/Mexico_City");


require_once __DIR__ . '/../getEnv.php';

$dsn = "mysql:host=" . $_ENV['HOST'] . ";dbname=" . $_ENV['DB'] . ";charset=utf8mb4";
$pdo = new PDO($dsn, $_ENV['USER'], $_ENV['PASSWORD']);
$pdo->exec("SET time_zone = '-06:00'");

$lote = 400;

for ($contador = 1; $contador <= 400; $contador++) {

    // Marcar como enviado
    $update = $pdo->prepare("INSERT INTO test_cron(comentario) values('prueba cron # $contador')");
    $update->execute();
    if ($contador % 10 == 0) {
        // Pausa tras cada mensaje  
        echo 'Pausa de mensaje cronjob # '.$contador;  
        sleep(1);
    }
}
