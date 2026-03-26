document.addEventListener('DOMContentLoaded', () => {
    const contactItems = document.querySelectorAll('.contact-item');
    const chatBody = document.querySelector('.chat-body d-flex flex-column');
    const chatHeaderName = document.querySelector('.chat-header d-flex align-items-center');

    contactItems.forEach(item => {
        item.addEventListener('click', function() {
            // 1. Estética: Quitar clase 'active' de otros y ponerla en este
            contactItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');

            // 2. Obtener el teléfono del atributo data
            const phone = this.getAttribute('data-phone');
            chatHeaderName.innerText = '+' + phone; // Actualizar nombre en el header

            // 3. Llamar a la base de datos
            loadMessages(phone);
        });
    });

    async function loadMessages(phone) {
        chatBody.innerHTML = '<div class="text-center mt-5"><div class="spinner-border text-success"></div></div>';

        try {
            const response = await fetch(`response/getHistory.php?remitente==${phone}`);
            const messages = await response.json();

            chatBody.innerHTML = ''; // Limpiar el spinner

            messages.forEach(msg => {
                // Determinar si el mensaje es enviado o recibido
                // Ajusta 'tipo' según tu columna en la DB (ej: 'sent' o 'received')
                const isSent = msg.tipo === 'sent'; 
                const msgClass = isSent ? 'msg-sent' : 'msg-received';

                const messageHtml = `
                    <div class="message ${msgClass}">
                        ${msg.texto}
                        <span class="msg-time">${msg.hora}</span>
                    </div>
                `;
                chatBody.innerHTML += messageHtml;
            });

            // 4. Scroll automático al final
            chatBody.scrollTop = chatBody.scrollHeight;

        } catch (error) {
            console.error('Error cargando mensajes:', error);
            chatBody.innerHTML = '<p class="text-danger text-center">Error al cargar el historial.</p>';
        }
    }
});