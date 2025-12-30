<?php
// get_history.php
date_default_timezone_set("America/Mexico_City");
require_once '../getEnv.php';


$remitente = $_GET['remitente'] ?? '';
if ($remitente) {
    $dsn = "mysql:host=" . $_ENV['HOST'] . ";dbname=" . $_ENV['DB'] . ";charset=utf8mb4";
    $pdo = new PDO($dsn, $_ENV['USER'], $_ENV['PASSWORD']);
    
    $stmt = $pdo->prepare("SELECT * FROM mensajes_whatsapp WHERE remitente = ? ORDER BY fecha_recibido ASC");
    $stmt->execute([$remitente]);
    $historial = $stmt->fetchAll();

    foreach ($historial as $h) {
        echo "<div class='mb-2 p-2 bg-light rounded shadow-sm'>";
        echo "<small class='text-muted d-block' style='font-size: 0.7rem;'>{$h['fecha_recibido']}</small>";
        echo "<span>" . htmlspecialchars($h['mensaje']) . "</span>";
        echo "</div>";
    }
}