document.addEventListener('DOMContentLoaded', () => {

    const contactItems = document.querySelectorAll('.contact-list');
    const chatHeaderPhone = document.getElementById('chat-contact-phone');
    //validar si el chat tiene alguna imagen de perfil, si no tiene, colocar una imagen por defecto
    const chatHeaderImg = document.getElementById('chat-contact-img');
    const chatBody = document.getElementById('chat-body');

    contactItems.forEach(item => {
        item.addEventListener('click', function (event) {

            // 1. Obtener datos del contacto clickeado
            const phone = event.target.closest('.contact-item').getAttribute('data-phone');
            const name = this.querySelector('h6').innerText;
            const imgUrl = this.querySelector('img').src;

            console.log(phone);
            //Agregar el numero de telefono al header del chat
            chatHeaderPhone.innerText = phone;

            
            // Quitar la clase 'active' de todos los contactos
            document.querySelectorAll('.contact-item').forEach(c => c.classList.remove('active'));
            // Agregarlo al que acabamos de clickear
            event.target.closest('.contact-item').classList.add('active');

            // 4. Cargar mensajes (Simulación de una petición a BD o API)
            loadMessages(phone);
        });
    });

    function loadMessages(phone) {
        // Aquí normalmente harías un fetch() a tu servidor

        // Por ahora, limpiaremos el chat y pondremos un mensaje de carga
        chatBody.innerHTML = '<div class="text-center my-auto">Cargando mensajes...</div>';

        // Simulación de carga de datos
        setTimeout(() => {
            chatBody.innerHTML = ''; // Limpiar el "cargando"

            // Ejemplo de estructura de mensajes que recibirías
            const mockMessages = [
                { text: "Hola, vi su anuncio del número " + phone, time: "10:00 AM", type: "received" },
                { text: "¡Hola! En qué podemos ayudarte.", time: "10:05 AM", type: "sent" }
            ];

            mockMessages.forEach(msg => {
                const msgDiv = document.createElement('div');
                msgDiv.className = `message msg-${msg.type}`;
                msgDiv.innerHTML = `
                    ${msg.text}
                    <span class="msg-time">${msg.time}</span>
                `;
                chatBody.appendChild(msgDiv);
            });

            // Scroll automático al final
            chatBody.scrollTop = chatBody.scrollHeight;
        }, 500);
    }
});