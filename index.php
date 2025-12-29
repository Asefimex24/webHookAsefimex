<?php
    require_once('getEnv.php');

$dsn = "mysql:host=" . $_ENV['HOST'] . ";dbname=" . $_ENV['DB'] . ";charset=utf8";
$pdo = new PDO($dsn, $_ENV['USER'], $_ENV['PASSWORD']);
$mensajes = $pdo->query("SELECT * FROM mensajes_whatsapp ORDER BY fecha_recibido DESC")->fetchAll();


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
                        <th>Mensaje</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mensajes as $m): ?>
                        <tr>
                            <td><strong><?php echo str_replace('whatsapp:', '', $m['remitente']); ?></strong></td>
                            <td><?php echo htmlspecialchars($m['mensaje']); ?></td>
                            <td class="text-muted"><?php echo $m['fecha_recibido']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-success"
                                    data-bs-toggle="modal"
                                    data-bs-target="#replyModal"
                                    data-num="<?php echo $m['remitente']; ?>">
                                    Responder
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
                            <h5 class="modal-title">Responder a <span id="displayNum"></span></h5>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="to" id="inputNum">
                            <textarea name="message" class="form-control" placeholder="Escribe tu mensaje aquí..." required></textarea>
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

<script>
// Script para pasar el número al modal
const replyModal = document.getElementById('replyModal');
replyModal.addEventListener('show.bs.modal', event => {
  const button = event.relatedTarget;
  const num = button.getAttribute('data-num');
  document.getElementById('displayNum').textContent = num;
  document.getElementById('inputNum').value = num;
});
</script>