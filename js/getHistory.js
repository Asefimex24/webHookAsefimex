document.addEventListener('DOMContentLoaded', () => {

    const contactItems = document.querySelectorAll('.contact-list');
    const chatHeaderPhone = document.getElementById('chat-contact-phone');
    //validar si el chat tiene alguna imagen de perfil, si no tiene, colocar una imagen por defecto
    const chatHeaderImg = document.getElementById('chat-contact-img');
    const chatBody = document.getElementById('chat-body');

    contactItems.forEach(item => {

        item.addEventListener('click', function (event) {

            //limpiar el input del ultimo chat, en caso de que el usuario haya escrito algo
            document.querySelector('.chat-footer input').value = '';

            //Obtener datos del contacto clickeado
            const phone = event.target.closest('.contact-item').getAttribute('data-phone');
            // const name = this.querySelector('h6').innerText;
            // const imgUrl = this.querySelector('img').src;

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
        const chatBody = document.getElementById('chat-body');

        // 1. Mostrar estado de carga
        chatBody.innerHTML = `
        <div class="d-flex justify-content-center align-items-center h-100">
            <div class="spinner-border text-success" role="status"></div>
        </div>`;

        // 2. Hacer la petición al backend
        fetch(`response/getHistoryPhone.php?phone=${encodeURIComponent(phone)}`)
            .then(response => response.json())
            .then(messages => {
                chatBody.innerHTML = ''; // Limpiar spinner

                if (messages.length === 0) {
                    chatBody.innerHTML = '<div class="text-center text-muted my-auto">No hay mensajes previos.</div>';
                    return;
                }

                // 3. Renderizar cada mensaje
                messages.forEach(msg => {
                    const msgDiv = document.createElement('div');
                    // Asignamos la clase según si es 'sent' o 'received'
                    msgDiv.className = `message msg-${msg.type}`;
                    msgDiv.innerHTML = `
                    ${msg.text}
                    <span class="msg-time">${msg.time}</span>
                `;
                    chatBody.appendChild(msgDiv);
                });

                // 4. Scroll al fondo automático
                chatBody.scrollTop = chatBody.scrollHeight;
            })
            .catch(error => {
                console.error('Error:', error);
                chatBody.innerHTML = '<div class="text-center text-danger">Error al cargar mensajes.</div>';
            });
    }

});