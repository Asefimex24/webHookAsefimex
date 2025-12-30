<?php

require_once '../getEnv.php';

$dsn = "mysql:host=" . $_ENV['HOST'] . ";dbname=" . $_ENV['DB'] . ";charset=utf8mb4";
$pdo = new PDO($dsn, $_ENV['USER'], $_ENV['PASSWORD']);

$mensajes = $pdo->query("SELECT remitente, mensaje, fecha_recibido
FROM mensajes_whatsapp
WHERE id IN (
    SELECT MAX(id)
    FROM mensajes_whatsapp
    GROUP BY remitente
)
ORDER BY fecha_recibido DESC;")->fetchAll();

foreach ($mensajes as $m): ?>
    <tr>
        <td><strong><?php echo $m['remitente']; ?></strong></td>
        <td><?php echo htmlspecialchars($m['mensaje']); ?></td>
        <td class="text-muted small"><?php echo $m['fecha_recibido']; ?></td>
        <td>
            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#replyModal" 
                    data-num="<?php echo $m['remitente']; ?>">
                Responder
            </button>
        </td>
    </tr>
<?php 
endforeach;
 ?>