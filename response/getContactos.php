<?php
date_default_timezone_set("America/Mexico_City");
require_once '../getEnv.php';

$dsn = "mysql:host=" . $_ENV['HOST'] . ";dbname=" . $_ENV['DB'] . ";charset=utf8mb4";
$pdo = new PDO($dsn, $_ENV['USER'], $_ENV['PASSWORD']);

$mensajes = $pdo->query("SELECT remitente, fecha_recibido,mensaje
FROM mensajes_whatsapp
WHERE id IN (
    SELECT MAX(id)
    FROM mensajes_whatsapp
    GROUP BY remitente
)
ORDER BY fecha_recibido DESC LIMIT 30;")->fetchAll();

foreach ($mensajes as $m):

?>
    <div class="contact-item p-3 d-flex align-items-center" data-phone="<?php echo $m['remitente']; ?>">
        <img src="https://ui-avatars.com/api/?name=Cliente+Asefimex" id="chat-contact-img" class="rounded-circle me-3" width="45">
        <div class="w-100">
            <div class="d-flex justify-content-between">
                <h6 class="mb-0 text-truncate" style="max-width: 150px;"><?php echo $m['remitente']; ?></h6>
                <small class="text-muted"><?php echo date("d-M-yy", strtotime($m['fecha_recibido'])); ?></small>
            </div>
             <small class="text-muted text-truncate d-block" style="max-width: 200px;"><?php echo $m['mensaje']; ?></small> 
        </div>
    </div>


<?php
endforeach;
?>