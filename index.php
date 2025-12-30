<?php
date_default_timezone_set("America/Mexico_City");
require_once('getEnv.php');
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

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel WhatsApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Mensajes de WhatsApp</h2>
            <button class="btn btn-primary" onclick="location.reload()">Actualizar</button>
        </div>

        <div class="table-responsive bg-white shadow-sm p-3 rounded">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Remitente</th>
                        <th>Último Mensaje</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-mensajes">
                    <?php foreach ($mensajes as $m): ?>
                        <tr>
                            <td><strong><?php echo $m['remitente']; ?></strong></td>
                            <td><?php echo htmlspecialchars($m['mensaje']); ?></td>
                            <td class="text-muted"><?php echo $m['fecha_recibido']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#replyModal" data-num="<?php echo $m['remitente']; ?>">
                                    Ver / Responder
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="modal fade" id="replyModal" tabindex="-1">
                <div class="modal-dialog">
                    <form action="enviar_respuesta.php" method="POST" class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Historial de mensajes con: <span id="displayNum"></span></h5>
                        </div>
                        <div class="modal-body">
                            <div id="chat-history" class="mb-3 p-3 border rounded bg-white"
                                style="max-height: 300px; overflow-y: auto; display: flex; flex-direction: column;">
                            </div>

                            <input type="hidden" name="to" id="inputNum">
                            <textarea name="message" class="form-control" placeholder="Escribe tu respuesta..." required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Enviar WhatsApp</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/getMessages.js"></script>
<script src="js/modal.js"></script>