<?php

require_once '../getEnv.php';

$dsn = "mysql:host=" . $_ENV['HOST'] . ";dbname=" . $_ENV['DB'] . ";charset=utf8mb4";
$pdo = new PDO($dsn, $_ENV['USER'], $_ENV['PASSWORD']);

$mensajes = $pdo->query("SELECT * FROM mensajes_whatsapp ORDER BY fecha_recibido DESC LIMIT 20")->fetchAll();

foreach ($mensajes as $m): ?>
    <tr>
        <td><strong><?php echo str_replace('whatsapp:', '', $m['remitente']); ?></strong></td>
        <td><?php echo htmlspecialchars($m['mensaje']); ?></td>
        <td class="text-muted small"><?php echo $m['fecha_recibido']; ?></td>
        <td>
            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#replyModal" 
                    data-num="<?php echo $m['remitente']; ?>">
                Responder
            </button>
        </td>
    </tr>
<?php endforeach; ?>