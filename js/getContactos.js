function cargarMensajes() {
    fetch('response/getContactos.php')
    
        .then(response => response.text())
        .then(html => {
            const tabla = document.getElementById('contact-list');
            // Solo actualizamos si el contenido ha cambiado para evitar parpadeos
            if (tabla.innerHTML !== html) {
                tabla.innerHTML = html;
            }
        })
        .then(() => console.log('Contactos cargados correctamente'))
        .catch(error => console.error('Error cargando contactos:', error));
}

// Ejecutar cada 3 segundos
//setInterval(cargarMensajes, 3000);

// Cargar al abrir la página por primera vez
document.addEventListener('DOMContentLoaded', cargarMensajes);