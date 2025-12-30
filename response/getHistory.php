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
        echo "<div class='mb-2 p-2 rounded shadow-sm' style='background-color: #e2f7cb; border-left: 4px solid #25d366;'>";
echo "  <div class='d-flex justify-content-between'>";
echo "    <small class='text-muted' style='font-size: 0.7rem;'>$remitente</small>";
echo "    <small class='text-muted' style='font-size: 0.7rem;'>" . $h['fecha_recibido']. "</small>";
echo "  </div>";
echo "  <div class='mt-1'>" . htmlspecialchars($h['mensaje']) . "</div>";
echo "</div>";
    }
}