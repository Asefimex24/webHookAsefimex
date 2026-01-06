<?php
date_default_timezone_set("America/Mexico_City");
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

foreach ($mensajes as $m): 

    if(mb_strlen($m['mensaje'], "UTF-8") > 30){
        $muestraMensaje = mb_substr($m['mensaje'], 0, 70, "UTF-8") . ' ...';
    } else {
        $muestraMensaje = $m['mensaje'];
    }
?>
    

    <tr>
        <td><strong><?php echo $m['remitente']; ?></strong></td>
        <td><a href="" data-bs-toggle="modal" data-bs-target="#replyModal" 
                    data-num="<?php echo $m['remitente']; ?>"><?php echo htmlspecialchars($muestraMensaje); ?></a></td>
        <td class="text-muted small"><?php echo $m['fecha_recibido']; ?></td>
        <!-- <td>
            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#replyModal" 
                    data-num="<?php #echo $m['remitente']; ?>">
                Ver / Responder
            </button>
        </td> -->
    </tr>
<?php 
endforeach;
 ?>