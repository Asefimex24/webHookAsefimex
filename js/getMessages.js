function cargarMensajes() {
    fetch('../response/get_messages.php')
    
        .then(response => response.text())
        .then(html => {
            const tabla = document.getElementById('tabla-mensajes');
            // Solo actualizamos si el contenido ha cambiado para evitar parpadeos
            if (tabla.innerHTML !== html) {
                tabla.innerHTML = html;
            }
        })
        .catch(error => console.error('Error cargando mensajes:', error));
}

// Ejecutar cada 3 segundos
setInterval(cargarMensajes, 3000);

// Cargar al abrir la página por primera vez
document.addEventListener('DOMContentLoaded', cargarMensajes);